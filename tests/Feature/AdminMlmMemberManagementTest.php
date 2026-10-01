<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMlmMemberManagementTest extends TestCase
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

        $view = Permission::create(['name' => 'mlm.view', 'guard_name' => 'web']);
        $manage = Permission::create(['name' => 'mlm.manage', 'guard_name' => 'web']);
        $this->adminRole->permissions()->sync([$view->id, $manage->id]);

        $this->admin = User::create([
            'name' => 'MLM Admin',
            'email' => 'mlm-admin@example.com',
            'password' => 'password',
            'role_id' => $this->adminRole->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_add_an_existing_customer_as_an_mlm_member(): void
    {
        $customer = $this->makeCustomer('Existing Customer', 'existing@example.com', '9100000001');

        $response = $this->actingAs($this->admin)->post(route('admin.mlm.members.store'), [
            'user_id' => $customer->id,
            'member_id' => 'MEM-001',
            'name' => $customer->name,
            'mobile' => $customer->phone,
            'email' => $customer->email,
            'status' => Member::STATUS_ACTIVE,
        ]);

        $member = Member::firstOrFail();

        $response->assertRedirect(route('admin.mlm.members.show', $member));
        $this->assertSame(1, User::where('email', 'existing@example.com')->count());
        $this->assertMatchesRegularExpression('/^C[0-9]{8}$/', (string) $customer->fresh()->mlm_member_id);
        $this->assertDatabaseHas('mlm_members', [
            'user_id' => $customer->id,
            'status' => Member::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'mlm_members',
            'action' => 'create',
            'subject_id' => $member->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.mlm.members.create'))
            ->assertOk()
            ->assertSee('Add MLM Member');

        $this->actingAs($this->admin)
            ->get(route('admin.mlm.members.edit', $member))
            ->assertOk()
            ->assertSee('Edit MLM Member');

        $this->actingAs($this->admin)
            ->get(route('admin.mlm.members.show', $member))
            ->assertOk()
            ->assertSee('Network Relationships');
    }

    public function test_admin_can_create_new_member_search_filter_edit_and_toggle_status(): void
    {
        $member = $this->makeMember('MEM-SEARCH-001', 'Search Member', '9100000002');
        $inactive = $this->makeMember('MEM-INACTIVE-001', 'Inactive Member', '9100000003');
        $inactive->update(['status' => Member::STATUS_INACTIVE]);

        $this->actingAs($this->admin)
            ->get(route('admin.mlm.members.index', ['search' => 'MEM-SEARCH-001']))
            ->assertOk()
            ->assertSee('Search Member')
            ->assertDontSee('Inactive Member');

        $this->actingAs($this->admin)
            ->get(route('admin.mlm.members.index', ['status' => Member::STATUS_INACTIVE]))
            ->assertOk()
            ->assertSee('Inactive Member')
            ->assertDontSee('Search Member');

        $this->actingAs($this->admin)->put(route('admin.mlm.members.update', $member), [
            'name' => 'Updated Member',
            'mobile' => '9100000004',
            'email' => 'updated@example.com',
            'status' => Member::STATUS_ACTIVE,
            'mobile_change_reason' => 'Corrected verified customer contact number',
        ])->assertRedirect(route('admin.mlm.members.show', $member));

        $this->assertDatabaseHas('users', [
            'id' => $member->user_id,
            'mlm_member_id' => 'MEM-SEARCH-001',
            'name' => 'Updated Member',
            'phone' => '9100000004',
        ]);

        $updateLog = ActivityLog::query()
            ->where('subject_type', Member::class)
            ->where('subject_id', $member->id)
            ->where('action', 'update')
            ->latest()
            ->firstOrFail();
        $this->assertSame('MEM-SEARCH-001', $updateLog->old_values['member_id']);
        $this->assertSame('MEM-SEARCH-001', $updateLog->new_values['member_id']);

        $this->actingAs($this->admin)
            ->patch(route('admin.mlm.members.toggleStatus', $member))
            ->assertRedirect();

        $this->assertDatabaseHas('mlm_members', [
            'id' => $member->id,
            'status' => Member::STATUS_INACTIVE,
        ]);
    }

    public function test_duplicate_mobile_and_invalid_sponsor_are_rejected(): void
    {
        $existing = $this->makeMember('MEM-DUP-001', 'Duplicate One', '9100000005');

        $this->actingAs($this->admin)
            ->post(route('admin.mlm.members.store'), [
                'member_id' => 'MEM-DUP-001',
                'name' => 'Duplicate ID',
                'mobile' => '9100000009',
                'email' => 'duplicate-id@example.com',
                'status' => Member::STATUS_ACTIVE,
            ])
            ->assertSessionHasErrors('member_id');

        $this->actingAs($this->admin)
            ->post(route('admin.mlm.members.store'), [
                'member_id' => 'MEM-DUP-002',
                'name' => 'Duplicate Two',
                'mobile' => '9100000005',
                'email' => 'duplicate-two@example.com',
                'status' => Member::STATUS_ACTIVE,
            ])
            ->assertSessionHasErrors('mobile');

        $this->actingAs($this->admin)
            ->post(route('admin.mlm.members.store'), [
                'member_id' => 'MEM-SPONSOR-INVALID',
                'name' => 'Invalid Sponsor',
                'mobile' => '9100000006',
                'email' => 'invalid-sponsor@example.com',
                'sponsor_customer_id' => 'C99990001',
                'status' => Member::STATUS_ACTIVE,
            ])
            ->assertSessionHasErrors('sponsor_customer_id');

        $this->assertDatabaseHas('mlm_members', ['id' => $existing->id]);
        $this->assertDatabaseMissing('users', ['email' => 'duplicate-two@example.com']);
    }

    public function test_member_cannot_be_assigned_as_own_sponsor_or_placement_parent(): void
    {
        $member = $this->makeMember('MEM-SELF-001', 'Self Member', '9100000007');

        $payload = [
            'member_id' => 'MEM-SELF-001',
            'name' => 'Self Member',
            'mobile' => '9100000007',
            'email' => 'self@example.com',
            'sponsor_member_id' => $member->id,
            'placement_parent_id' => $member->id,
            'placement_position' => Member::POSITION_LEFT,
            'status' => Member::STATUS_ACTIVE,
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.mlm.members.update', $member), $payload)
            ->assertSessionHasErrors(['sponsor_member_id', 'placement_parent_id']);

        $this->assertDatabaseHas('mlm_members', [
            'id' => $member->id,
            'sponsor_member_id' => null,
            'placement_parent_id' => null,
        ]);
    }

    public function test_customer_cannot_access_admin_member_management(): void
    {
        $customer = $this->makeCustomer('Regular Customer', 'regular@example.com', '9100000008');

        $this->actingAs($customer)
            ->get(route('admin.mlm.members.index'))
            ->assertForbidden();

        $limitedRole = Role::create(['name' => 'Limited Admin', 'guard_name' => 'web']);
        $limitedAdmin = User::create([
            'name' => 'Limited Admin',
            'email' => 'limited-admin@example.com',
            'password' => 'password',
            'role_id' => $limitedRole->id,
            'status' => 'active',
        ]);

        $this->actingAs($limitedAdmin)
            ->get(route('admin.mlm.members.index'))
            ->assertForbidden();
    }

    private function makeCustomer(string $name, string $email, string $phone): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'phone' => $phone,
            'role_id' => $this->customerRole->id,
            'status' => 'active',
        ]);
    }

    private function makeMember(string $memberId, string $name, string $phone): Member
    {
        $user = $this->makeCustomer($name, strtolower(str_replace(' ', '-', $memberId)).'@example.com', $phone);
        $user->update(['mlm_member_id' => $memberId]);

        return Member::create([
            'user_id' => $user->id,
            'status' => Member::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
    }
}
