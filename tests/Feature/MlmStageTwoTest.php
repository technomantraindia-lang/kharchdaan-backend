<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MlmKycHistory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Mlm\MlmMemberIdGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MlmStageTwoTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $customerRole;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $this->customerRole = Role::create(['name' => 'Customer', 'guard_name' => 'web']);

        $permissions = collect([
            'mlm.view', 'mlm.manage', 'mlm.create', 'mlm.edit', 'mlm.block',
            'mlm.kyc.view', 'mlm.kyc.review', 'mlm.activity.view',
        ])->map(fn (string $name) => Permission::create(['name' => $name, 'guard_name' => 'web']));
        $this->adminRole->permissions()->sync($permissions->pluck('id')->all());

        $this->admin = User::create([
            'name' => 'Stage Two Admin',
            'email' => 'stage-two-admin@example.com',
            'password' => 'password',
            'role_id' => $this->adminRole->id,
            'status' => 'active',
        ]);
    }

    public function test_member_ids_use_monthly_format_and_are_unique_in_sequence(): void
    {
        $generator = app(MlmMemberIdGenerator::class);
        $date = Carbon::create(2026, 9, 21);

        $ids = collect(range(1, 25))->map(fn () => $generator->next($date))->all();

        $this->assertSame('C26090001', $ids[0]);
        $this->assertSame('C26090025', $ids[24]);
        $this->assertCount(25, array_unique($ids));
        $this->assertSame('C26100001', $generator->next(Carbon::create(2026, 10, 1)));
    }

    public function test_admin_can_create_root_and_sponsored_members_with_encrypted_kyc(): void
    {
        $rootResponse = $this->actingAs($this->admin)->post(route('admin.mlm.members.store'), [
            'name' => 'Root Member',
            'mobile' => '9200000001',
            'email' => 'root-stage-two@example.com',
            'joining_date' => '2026-09-20',
            'root_member' => '1',
            'status' => Member::STATUS_PENDING,
            'kyc_status' => Member::KYC_PENDING,
            'pan_number' => 'ABCDE1234F',
            'aadhaar_reference' => '123456789012',
            'bank_account_holder_name' => 'Root Member',
            'bank_account_number' => '1234567890',
            'ifsc_code' => 'HDFC0001234',
            'bank_name' => 'Example Bank',
            'bank_branch' => 'Main Branch',
        ]);
        $root = Member::query()->whereHas('user', fn ($query) => $query->where('email', 'root-stage-two@example.com'))->firstOrFail();

        $rootResponse->assertRedirect(route('admin.mlm.members.show', $root));
        $this->assertMatchesRegularExpression('/^C[0-9]{8}$/', $root->customer_id);
        $this->assertSame($this->admin->id, $root->created_by);
        $this->assertSame('ABCDE1234F', $root->pan_number);
        $this->assertSame('123456789012', $root->aadhaar_reference);
        $this->assertSame('******234F', $root->masked_pan);
        $this->assertSame('********9012', $root->masked_aadhaar);
        $this->assertNotSame('ABCDE1234F', DB::table('mlm_members')->where('id', $root->id)->value('pan_number'));

        $this->actingAs($this->admin)->post(route('admin.mlm.members.store'), [
            'name' => 'Sponsored Member',
            'mobile' => '9200000002',
            'email' => 'sponsored-stage-two@example.com',
            'joining_date' => '2026-09-21',
            'sponsor_customer_id' => $root->customer_id,
            'status' => Member::STATUS_ACTIVE,
            'kyc_status' => Member::KYC_PENDING,
        ])->assertRedirect();

        $sponsored = Member::query()->whereHas('user', fn ($query) => $query->where('email', 'sponsored-stage-two@example.com'))->firstOrFail();
        $this->assertSame($root->id, $sponsored->sponsor_member_id);
        $this->assertSame('Root Member', $sponsored->sponsor_name_snapshot);
        $this->assertNotNull($sponsored->sponsor_assigned_at);
    }

    public function test_duplicate_email_pan_and_aadhaar_are_rejected(): void
    {
        $first = $this->createMember([
            'email' => 'duplicate-source@example.com',
            'mobile' => '9200000003',
            'pan_number' => 'BCDEF2345G',
            'aadhaar_reference' => '234567890123',
        ]);

        $this->actingAs($this->admin)->post(route('admin.mlm.members.store'), [
            'name' => 'Duplicate Email',
            'mobile' => '9200000004',
            'email' => 'duplicate-source@example.com',
            'root_member' => '1',
            'status' => Member::STATUS_ACTIVE,
            'kyc_status' => Member::KYC_PENDING,
        ])->assertSessionHasErrors('email');

        $this->actingAs($this->admin)->post(route('admin.mlm.members.store'), [
            'name' => 'Duplicate PAN',
            'mobile' => '9200000005',
            'email' => 'duplicate-pan@example.com',
            'sponsor_customer_id' => $first->customer_id,
            'status' => Member::STATUS_ACTIVE,
            'kyc_status' => Member::KYC_PENDING,
            'pan_number' => 'BCDEF2345G',
        ])->assertSessionHasErrors('pan_number');

        $this->actingAs($this->admin)->post(route('admin.mlm.members.store'), [
            'name' => 'Duplicate Aadhaar',
            'mobile' => '9200000006',
            'email' => 'duplicate-aadhaar@example.com',
            'sponsor_customer_id' => $first->customer_id,
            'status' => Member::STATUS_ACTIVE,
            'kyc_status' => Member::KYC_PENDING,
            'aadhaar_reference' => '234567890123',
        ])->assertSessionHasErrors('aadhaar_reference');
    }

    public function test_kyc_review_requires_rejection_reason_and_preserves_history(): void
    {
        $member = $this->createMember(['email' => 'kyc-review@example.com', 'mobile' => '9200000007']);

        $this->actingAs($this->admin)
            ->patch(route('admin.mlm.members.kyc.update', $member), ['kyc_status' => Member::KYC_UNDER_REVIEW])
            ->assertRedirect();
        $this->assertSame(Member::KYC_UNDER_REVIEW, $member->fresh()->kyc_status);

        $this->actingAs($this->admin)
            ->patch(route('admin.mlm.members.kyc.update', $member), ['kyc_status' => Member::KYC_REJECTED])
            ->assertSessionHasErrors('kyc_rejection_reason');

        $this->actingAs($this->admin)
            ->patch(route('admin.mlm.members.kyc.update', $member), [
                'kyc_status' => Member::KYC_REJECTED,
                'kyc_rejection_reason' => 'IFSC document is unclear',
            ])->assertRedirect();

        $this->actingAs($this->admin)
            ->patch(route('admin.mlm.members.kyc.update', $member), ['kyc_status' => Member::KYC_APPROVED])
            ->assertRedirect();

        $member = $member->fresh();
        $this->assertSame(Member::KYC_APPROVED, $member->kyc_status);
        $this->assertSame($this->admin->id, $member->kyc_verified_by);
        $this->assertNotNull($member->kyc_verified_at);
        $this->assertGreaterThanOrEqual(3, MlmKycHistory::where('member_id', $member->id)->count());
        $this->assertDatabaseHas('mlm_kyc_histories', [
            'member_id' => $member->id,
            'new_status' => Member::KYC_REJECTED,
            'rejection_reason' => 'IFSC document is unclear',
        ]);
    }

    public function test_blocked_members_cannot_be_selected_as_new_sponsors(): void
    {
        $sponsor = $this->createMember(['email' => 'blocked-sponsor@example.com', 'mobile' => '9200000008']);
        $sponsor->update(['status' => Member::STATUS_BLOCKED]);

        $this->actingAs($this->admin)->post(route('admin.mlm.members.store'), [
            'name' => 'Blocked Sponsor Child',
            'mobile' => '9200000009',
            'email' => 'blocked-sponsor-child@example.com',
            'sponsor_customer_id' => $sponsor->customer_id,
            'status' => Member::STATUS_ACTIVE,
            'kyc_status' => Member::KYC_PENDING,
        ])->assertSessionHasErrors('sponsor_customer_id');
    }

    public function test_kyc_view_is_restricted_without_sensitive_permission(): void
    {
        $member = $this->createMember([
            'email' => 'restricted-kyc@example.com',
            'mobile' => '9200000010',
            'pan_number' => 'CDEFG3456H',
        ]);

        $view = Permission::where('name', 'mlm.view')->firstOrFail();
        $this->adminRole->permissions()->sync([$view->id]);
        $this->admin->refresh();

        $this->actingAs($this->admin)
            ->get(route('admin.mlm.members.show', $member))
            ->assertOk()
            ->assertSee('Sensitive KYC data is restricted')
            ->assertDontSee('CDEFG3456H');

        $this->actingAs($this->admin)
            ->get(route('admin.mlm.members.kyc', $member))
            ->assertForbidden();
    }

    public function test_kyc_submission_requires_sensitive_permission(): void
    {
        $manage = Permission::where('name', 'mlm.manage')->firstOrFail();
        $this->adminRole->permissions()->sync([$manage->id]);
        $this->admin->refresh();

        $this->actingAs($this->admin)
            ->post(route('admin.mlm.members.store'), [
                'name' => 'Unauthorized KYC Member',
                'mobile' => '9200000011',
                'email' => 'unauthorized-kyc@example.com',
                'root_member' => '1',
                'status' => Member::STATUS_PENDING,
                'kyc_status' => Member::KYC_PENDING,
                'pan_number' => 'DEFGH4567J',
            ])
            ->assertForbidden();
    }

    private function createMember(array $overrides = []): Member
    {
        $data = array_merge([
            'name' => 'Stage Two Member',
            'mobile' => '9299999999',
            'email' => 'stage-two-member-'.uniqid().'@example.com',
            'joining_date' => '2026-09-21',
            'root_member' => '1',
            'status' => Member::STATUS_ACTIVE,
            'kyc_status' => Member::KYC_PENDING,
        ], $overrides);

        $this->actingAs($this->admin)->post(route('admin.mlm.members.store'), $data)->assertRedirect();

        return Member::query()->whereHas('user', fn ($query) => $query->where('email', $data['email']))->firstOrFail();
    }
}
