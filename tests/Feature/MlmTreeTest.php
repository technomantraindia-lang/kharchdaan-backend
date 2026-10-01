<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MlmTreeTest extends TestCase
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
            'name' => 'Tree Admin',
            'email' => 'tree-admin@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        Role::whereKey($customerRole->id)->firstOrFail();
    }

    public function test_tree_screen_and_children_endpoint_show_three_placement_positions(): void
    {
        $sponsor = $this->member('TREE-SPONSOR', 'Sponsor Member', '9300000001');
        $root = $this->member('TREE-ROOT', 'Root Member', '9300000002');
        $left = $this->member('TREE-LEFT', 'Left Member', '9300000003', $sponsor, $root, Member::POSITION_LEFT);
        $middle = $this->member('TREE-MIDDLE', 'Middle Member', '9300000004', $root, $root, Member::POSITION_MIDDLE);
        $right = $this->member('TREE-RIGHT', 'Right Member', '9300000005', $sponsor, $root, Member::POSITION_RIGHT);

        $this->actingAs($this->admin)
            ->get(route('admin.mlm.tree.index'))
            ->assertOk()
            ->assertSee('TREE-ROOT')
            ->assertSee('Move Member')
            ->assertSee('Sponsor Tree and Placement Tree are separate.');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.mlm.tree.children', $root));

        $response->assertOk()
            ->assertJsonCount(3, 'slots')
            ->assertJsonPath('slots.0.position', Member::POSITION_LEFT)
            ->assertJsonPath('slots.0.member.customer_id', $left->customer_id)
            ->assertJsonPath('slots.1.member.customer_id', $middle->customer_id)
            ->assertJsonPath('slots.2.member.customer_id', $right->customer_id)
            ->assertJsonPath('slots.0.member.sponsor.customer_id', $sponsor->customer_id)
            ->assertJsonPath('slots.0.member.placement_parent.customer_id', $root->customer_id)
            ->assertJsonPath('slots.0.member.level', 1)
            ->assertJsonPath('slots.0.member.details_url', route('admin.mlm.members.show', $left));
    }

    public function test_empty_positions_are_returned_without_loading_descendants(): void
    {
        $root = $this->member('TREE-EMPTY-ROOT', 'Empty Root', '9300000010');
        $left = $this->member('TREE-EMPTY-LEFT', 'Only Left', '9300000011', $root, $root, Member::POSITION_LEFT);

        $rootResponse = $this->actingAs($this->admin)
            ->getJson(route('admin.mlm.tree.children', $root));

        $rootResponse->assertJsonPath('slots.0.member.customer_id', $left->customer_id)
            ->assertJsonPath('slots.1.member', null)
            ->assertJsonPath('slots.2.member', null);
    }

    public function test_branch_expansion_is_lazy_and_deeper_branch_has_its_own_level(): void
    {
        $root = $this->member('TREE-LAZY-ROOT', 'Lazy Root', '9300000020');
        $child = $this->member('TREE-LAZY-CHILD', 'Lazy Child', '9300000021', $root, $root, Member::POSITION_MIDDLE);
        $grandchild = $this->member('TREE-LAZY-GRAND', 'Lazy Grandchild', '9300000022', $root, $child, Member::POSITION_RIGHT);

        $rootResponse = $this->actingAs($this->admin)
            ->getJson(route('admin.mlm.tree.children', $root));

        $rootResponse->assertJsonPath('slots.1.member.customer_id', $child->customer_id)
            ->assertJsonPath('slots.1.member.has_children', true)
            ->assertJsonMissing(['customer_id' => $grandchild->customer_id]);

        $childResponse = $this->actingAs($this->admin)
            ->getJson(route('admin.mlm.tree.children', $child));

        $childResponse->assertJsonPath('slots.2.member.customer_id', $grandchild->customer_id)
            ->assertJsonPath('slots.2.member.level', 2)
            ->assertJsonPath('slots.2.member.placement_parent.customer_id', $child->customer_id);
    }

    public function test_search_returns_root_and_placement_path_for_open_location(): void
    {
        $root = $this->member('TREE-SEARCH-ROOT', 'Search Root', '9300000030');
        $branch = $this->member('TREE-BRANCH', 'Branch Member', '9300000031', $root, $root, Member::POSITION_LEFT);
        $target = $this->member('TREE-TARGET', 'Target Member', '9300000032', $root, $branch, Member::POSITION_RIGHT);

        $this->actingAs($this->admin)
            ->getJson(route('admin.mlm.tree.search', ['q' => 'Target Member']))
            ->assertOk()
            ->assertJsonPath('data.0.member.customer_id', $target->customer_id)
            ->assertJsonPath('data.0.root_id', $root->id)
            ->assertJsonPath('data.0.path.0', $root->id)
            ->assertJsonPath('data.0.path.1', $branch->id)
            ->assertJsonPath('data.0.path.2', $target->id);
    }

    public function test_tree_is_admin_only(): void
    {
        $customerRole = Role::where('name', 'Customer')->firstOrFail();
        $customer = User::create([
            'name' => 'Tree Customer',
            'email' => 'tree-customer@example.com',
            'password' => 'password',
            'role_id' => $customerRole->id,
            'status' => 'active',
        ]);

        $this->actingAs($customer)
            ->get(route('admin.mlm.tree.index'))
            ->assertForbidden();
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
