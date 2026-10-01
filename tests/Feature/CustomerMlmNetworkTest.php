<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerMlmNetworkTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $customerRole = Role::create(['name' => 'Customer', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'mlm.view', 'guard_name' => 'web']);
        $adminRole->permissions()->sync([$permission->id]);

        $this->admin = User::create([
            'name' => 'Network Admin',
            'email' => 'network-admin@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        Role::whereKey($customerRole->id)->firstOrFail();
    }

    public function test_customer_can_open_own_network_and_level_pages_without_sensitive_fields(): void
    {
        $root = $this->member('CUSTOMER-ROOT', 'Network Owner', '9411111111');

        $this->actingAs($root->user)
            ->get(route('network.index'))
            ->assertOk()
            ->assertSee('Direct Selling Network')
            ->assertSee($root->customer_id)
            ->assertSee('Sponsor Tree')
            ->assertSee('Placement Tree')
            ->assertDontSee('9411111111')
            ->assertDontSee($root->user->email);

        $this->actingAs($root->user)
            ->get(route('network.levels'))
            ->assertOk()
            ->assertSee('Level 0')
            ->assertSee('Own Purchase')
            ->assertSee('Level 19')
            ->assertSee('Nineteenth placement level')
            ->assertSee('Not yet calculated');
    }

    public function test_customer_sponsor_tree_is_scoped_and_lazy(): void
    {
        $root = $this->member('SPONSOR-ROOT', 'Sponsor Root', '9411111120');
        $child = $this->member('SPONSOR-CHILD', 'Sponsor Child', '9411111121', $root);
        $grandchild = $this->member('SPONSOR-GRAND', 'Sponsor Grandchild', '9411111122', $child);

        $response = $this->actingAs($root->user)
            ->getJson(route('network.children', ['member' => $root, 'tree' => 'sponsor']))
            ->assertOk()
            ->assertJsonPath('tree', 'sponsor')
            ->assertJsonPath('children.0.customer_id', $child->customer_id)
            ->assertJsonMissing(['customer_id' => $grandchild->customer_id]);

        $response->assertJsonMissing(['phone' => '9411111121'])
            ->assertJsonMissing(['email' => strtolower($child->customer_id).'@example.com']);

        $this->actingAs($root->user)
            ->getJson(route('network.children', ['member' => $child, 'tree' => 'sponsor']))
            ->assertOk()
            ->assertJsonPath('children.0.customer_id', $grandchild->customer_id);
    }

    public function test_customer_placement_tree_returns_left_middle_right_and_empty_slots(): void
    {
        $root = $this->member('PLACEMENT-ROOT', 'Placement Root', '9411111130');
        $left = $this->member('PLACEMENT-LEFT', 'Placement Left', '9411111131', $root, $root, Member::POSITION_LEFT);
        $this->member('PLACEMENT-MIDDLE', 'Placement Middle', '9411111132', $root, $root, Member::POSITION_MIDDLE);

        $this->actingAs($root->user)
            ->getJson(route('network.children', ['member' => $root, 'tree' => 'placement']))
            ->assertOk()
            ->assertJsonCount(3, 'slots')
            ->assertJsonPath('slots.0.position', Member::POSITION_LEFT)
            ->assertJsonPath('slots.0.member.customer_id', $left->customer_id)
            ->assertJsonPath('slots.1.position', Member::POSITION_MIDDLE)
            ->assertJsonPath('slots.2.position', Member::POSITION_RIGHT)
            ->assertJsonPath('slots.2.member', null)
            ->assertJsonPath('slots.0.member.level', 1);
    }

    public function test_customer_cannot_open_another_members_branch_or_admin_move_endpoint(): void
    {
        $owner = $this->member('OWN-NETWORK', 'Own Network', '9411111140');
        $other = $this->member('OTHER-NETWORK', 'Other Network', '9411111141');

        $this->actingAs($owner->user)
            ->getJson(route('network.children', ['member' => $other, 'tree' => 'sponsor']))
            ->assertForbidden();

        $this->actingAs($owner->user)
            ->post(route('admin.mlm.tree.move', ['member' => $other]), [])
            ->assertForbidden();
    }

    public function test_admin_can_still_open_the_existing_tree(): void
    {
        $this->member('ADMIN-TREE-ROOT', 'Admin Tree Root', '9411111150');

        $this->actingAs($this->admin)
            ->get(route('admin.mlm.tree.index'))
            ->assertOk()
            ->assertSee('Placement Tree');
    }

    private function member(
        string $memberId,
        string $name,
        string $phone,
        ?Member $sponsor = null,
        ?Member $parent = null,
        ?string $position = null
    ): Member {
        $role = Role::where('name', 'Customer')->firstOrFail();
        $user = User::create([
            'name' => $name,
            'email' => strtolower($memberId).'@example.com',
            'password' => 'password',
            'phone' => $phone,
            'role_id' => $role->id,
            'status' => 'active',
            'mlm_member_id' => $memberId,
        ]);

        return Member::create([
            'user_id' => $user->id,
            'sponsor_member_id' => $sponsor?->id,
            'placement_parent_id' => $parent?->id,
            'placement_position' => $position,
            'status' => Member::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
    }
}
