<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SponsorRegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    private Role $customerRole;

    private Member $sponsor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);

        $sponsorUser = User::create([
            'name' => 'Sponsor Leader',
            'email' => 'sponsor-leader@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
            'status' => 'active',
            'mlm_member_id' => 'C22029999',
        ]);

        $this->sponsor = Member::create([
            'user_id' => $sponsorUser->id,
            'status' => Member::STATUS_ACTIVE,
            'joined_at' => now(),
            'kyc_status' => Member::KYC_APPROVED,
            'is_idle_slot' => false,
        ]);
    }

    public function test_register_with_valid_sponsor_saves_relationship(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Referred User',
            'email' => 'referred@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'phone' => '9876500001',
            'referral_code' => 'C22029999',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.sponsor.customer_id', 'C22029999');

        $user = User::where('email', 'referred@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->mlm_member_id);
        $this->assertDatabaseHas('mlm_members', [
            'user_id' => $user->id,
            'sponsor_member_id' => $this->sponsor->id,
        ]);
    }

    public function test_register_with_invalid_sponsor_fails(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Bad Sponsor User',
            'email' => 'badsponsor@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'referral_code' => 'C99999999',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('users', ['email' => 'badsponsor@example.com']);
    }

    public function test_multiple_users_under_same_sponsor_and_referrals_api(): void
    {
        foreach (['one@example.com', 'two@example.com'] as $email) {
            $this->postJson('/api/v1/register', [
                'name' => 'Child '.$email,
                'email' => $email,
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'referral_code' => 'C22029999',
            ])->assertStatus(201);
        }

        $login = $this->postJson('/api/v1/login', [
            'email' => 'sponsor-leader@example.com',
            'password' => 'password123',
        ])->assertOk();

        $token = $login->json('data.access_token');

        $referrals = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me/referrals')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.direct_referral_count', 2);

        $this->assertCount(2, $referrals->json('data.referrals'));
    }

    public function test_blocked_sponsor_cannot_refer(): void
    {
        $this->sponsor->update(['status' => Member::STATUS_BLOCKED]);

        $this->postJson('/api/v1/register', [
            'name' => 'Blocked Sponsor Child',
            'email' => 'blocked-child@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'referral_code' => 'C22029999',
        ])->assertStatus(422);
    }
}
