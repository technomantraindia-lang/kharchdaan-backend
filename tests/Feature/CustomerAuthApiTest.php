<?php

namespace Tests\Feature;

use App\Models\PersonalAccessToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected Role $customerRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);
    }

    /** 1. Register succeeds with valid inputs */
    public function test_1_customer_register_succeeds(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Customer',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'phone' => '1234567890',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user', 'access_token']]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role_id' => $this->customerRole->id,
        ]);
    }

    /** 2. Register fails with duplicate email */
    public function test_2_customer_register_fails_with_duplicate_email(): void
    {
        User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
        ]);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Duplicate Email',
            'email' => 'existing@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /** 3. Register fails with mismatched password confirmation */
    public function test_3_customer_register_fails_with_mismatched_password(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Wrong Confirmation',
            'email' => 'wrong@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /** 4. Register strictly assigns Customer role even if admin requested */
    public function test_4_customer_register_assigns_customer_role_only(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'Super Admin'], ['guard_name' => 'web']);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Malicious Admin Request',
            'email' => 'hacker@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'Super Admin',
            'role_id' => $adminRole->id,
        ]);

        $response->assertStatus(201);
        $user = User::where('email', 'hacker@example.com')->first();
        $this->assertEquals($this->customerRole->id, $user->role_id);
    }

    /** 5. Login succeeds with correct credentials */
    public function test_5_customer_login_succeeds(): void
    {
        User::create([
            'name' => 'Valid User',
            'email' => 'valid@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'valid@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user', 'access_token']]);
    }

    /** 6. Login fails with invalid password */
    public function test_6_customer_login_fails_with_wrong_password(): void
    {
        User::create([
            'name' => 'Valid User',
            'email' => 'valid@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'valid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    /** 7. Login fails with non-existent user */
    public function test_7_customer_login_fails_with_non_existent_email(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    /** 8. Login fails for inactive account */
    public function test_8_customer_login_fails_for_inactive_account(): void
    {
        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
            'status' => 'inactive',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /** 9. Generated token has expires_at set */
    public function test_9_generated_token_has_expires_at_set(): void
    {
        $user = User::create([
            'name' => 'Token User',
            'email' => 'token@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
        ]);

        $tokenData = PersonalAccessToken::generateToken($user, 'test-token');
        $this->assertNotNull($tokenData['accessToken']->expires_at);
        $this->assertTrue($tokenData['accessToken']->expires_at->isFuture());
    }

    /** 10. Expired token is rejected by findToken */
    public function test_10_expired_token_is_rejected_by_find_token(): void
    {
        $user = User::create([
            'name' => 'Expired User',
            'email' => 'expired@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
        ]);

        $tokenData = PersonalAccessToken::generateToken($user, 'test-token');
        $tokenData['accessToken']->update(['expires_at' => now()->subDay()]);

        $found = PersonalAccessToken::findToken($tokenData['plainTextToken']);
        $this->assertNull($found);
    }

    /** 11. Profile /api/v1/me returns authenticated user details */
    public function test_11_get_profile_me_returns_user(): void
    {
        $user = User::create([
            'name' => 'Profile User',
            'email' => 'profile@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
        ]);

        $tokenData = PersonalAccessToken::generateToken($user, 'test-token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenData['plainTextToken'])
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'profile@example.com');
    }

    /** 12. Profile /api/v1/me rejects unauthenticated request */
    public function test_12_get_profile_me_rejects_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/me');
        $response->assertStatus(401);
    }

    /** 13. Profile /api/v1/me rejects expired token */
    public function test_13_get_profile_me_rejects_expired_token(): void
    {
        $user = User::create([
            'name' => 'Expired Token User',
            'email' => 'expiredme@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
        ]);

        $tokenData = PersonalAccessToken::generateToken($user, 'test-token');
        $tokenData['accessToken']->update(['expires_at' => now()->subHour()]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenData['plainTextToken'])
            ->getJson('/api/v1/me');

        $response->assertStatus(401);
    }

    /** 14. Update profile /api/v1/me updates name and phone */
    public function test_14_update_profile_me_updates_name_and_phone(): void
    {
        $user = User::create([
            'name' => 'Original Name',
            'email' => 'update@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
        ]);

        $tokenData = PersonalAccessToken::generateToken($user, 'test-token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenData['plainTextToken'])
            ->putJson('/api/v1/me', [
                'name' => 'Updated Name',
                'phone' => '9876543210',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertEquals('Updated Name', $user->fresh()->name);
        $this->assertEquals('9876543210', $user->fresh()->phone);
    }

    /** 15. Logout /api/v1/logout revokes current token */
    public function test_15_logout_revokes_current_token(): void
    {
        $user = User::create([
            'name' => 'Logout User',
            'email' => 'logout@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
        ]);

        $tokenData = PersonalAccessToken::generateToken($user, 'test-token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenData['plainTextToken'])
            ->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenData['accessToken']->id,
        ]);
    }

    /** 16. Logout fails without token */
    public function test_16_logout_fails_without_token(): void
    {
        $response = $this->postJson('/api/v1/logout');
        $response->assertStatus(401);
    }

    /** 17. Valid token remains usable before expiration */
    public function test_17_valid_token_remains_usable_before_expiration(): void
    {
        $user = User::create([
            'name' => 'Valid Expiry User',
            'email' => 'validexpiry@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
        ]);

        $tokenData = PersonalAccessToken::generateToken($user, 'test-token');
        $found = PersonalAccessToken::findToken($tokenData['plainTextToken']);

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->tokenable_id);
    }
}
