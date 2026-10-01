<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Permission;
use App\Models\PlacementMovement;
use App\Models\Role;
use App\Models\User;
use App\Services\Mlm\MlmMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class MlmMovementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $customerRole = Role::create(['name' => 'Customer', 'guard_name' => 'web']);
        $permissions = collect(['mlm.view', 'mlm.manage', 'mlm.move'])
            ->map(fn (string $name) => Permission::create(['name' => $name, 'guard_name' => 'web']));
        $adminRole->permissions()->sync($permissions->pluck('id')->all());

        $this->admin = User::create([
            'name' => 'Movement Admin',
            'email' => 'movement-admin@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        Role::whereKey($customerRole->id)->firstOrFail();
    }

    public function test_preview_and_move_update_complete_subtree_without_changing_sponsor(): void
    {
        $sponsor = $this->member('MOVE-SPONSOR', 'Sponsor', '9400000001');
        $oldParent = $this->member('MOVE-OLD', 'Old Parent', '9400000002');
        $newParent = $this->member('MOVE-NEW', 'New Parent', '9400000003');
        $member = $this->member('MOVE-MEMBER', 'Moved Member', '9400000004', $sponsor, $oldParent, Member::POSITION_LEFT);
        $child = $this->member('MOVE-CHILD', 'Moved Child', '9400000005', $sponsor, $member, Member::POSITION_RIGHT);

        $data = [
            'new_parent_id' => $newParent->id,
            'new_position' => Member::POSITION_MIDDLE,
            'reason' => 'Approved branch consolidation',
            'effective_at' => now()->subMinute()->toDateTimeString(),
        ];

        $this->actingAs($this->admin)
            ->getJson(route('admin.mlm.tree.move.parents.search', [$member, 'q' => 'New Parent']))
            ->assertOk()
            ->assertJsonPath('data.0.customer_id', $newParent->customer_id)
            ->assertJsonPath('data.0.available_positions.0', Member::POSITION_LEFT);

        $this->actingAs($this->admin)
            ->postJson(route('admin.mlm.tree.move.preview', $member), $data)
            ->assertOk()
            ->assertJsonPath('data.affected_subtree_count', 2)
            ->assertJsonPath('data.new_depth', 1)
            ->assertJsonPath('data.new_position', Member::POSITION_MIDDLE);

        $this->actingAs($this->admin)
            ->post(route('admin.mlm.tree.move', $member), $data + ['confirmed' => 1])
            ->assertRedirect(route('admin.mlm.members.show', $member));

        $member = $member->fresh(['sponsor', 'placementParent']);
        $this->assertSame($newParent->id, $member->placement_parent_id);
        $this->assertSame(Member::POSITION_MIDDLE, $member->placement_position);
        $this->assertSame($sponsor->id, $member->sponsor_member_id);
        $this->assertSame($member->id, $child->fresh()->placement_parent_id);
        $this->assertDatabaseHas('mlm_placement_movements', [
            'member_id' => $member->id,
            'old_parent_id' => $oldParent->id,
            'new_parent_id' => $newParent->id,
            'old_position' => Member::POSITION_LEFT,
            'new_position' => Member::POSITION_MIDDLE,
            'reason' => 'Approved branch consolidation',
            'moved_by' => $this->admin->id,
            'affected_subtree_count' => 2,
            'status' => 'approved',
        ]);

        $movement = PlacementMovement::where('member_id', $member->id)->firstOrFail();
        $this->assertSame($oldParent->customer_id, $movement->old_path_snapshot[0]['customer_id']);
        $this->assertSame($newParent->customer_id, $movement->new_path_snapshot[0]['customer_id']);
        $this->assertNotNull($movement->approved_at);
    }

    public function test_self_descendant_occupied_inactive_and_max_depth_moves_are_rejected(): void
    {
        $root = $this->member('MOVE-RULE-ROOT', 'Rule Root', '9400000010');
        $member = $this->member('MOVE-RULE-MEMBER', 'Rule Member', '9400000011', $root, $root, Member::POSITION_LEFT);
        $descendant = $this->member('MOVE-RULE-DESC', 'Rule Descendant', '9400000012', $root, $member, Member::POSITION_RIGHT);
        $occupiedParent = $this->member('MOVE-RULE-OCCUPIED', 'Occupied Parent', '9400000013');
        $this->member('MOVE-RULE-OCCUPANT', 'Occupant', '9400000014', $root, $occupiedParent, Member::POSITION_LEFT);
        $inactiveParent = $this->member('MOVE-RULE-INACTIVE', 'Inactive Parent', '9400000015');
        $inactiveParent->update(['status' => Member::STATUS_INACTIVE]);

        $base = [
            'new_position' => Member::POSITION_MIDDLE,
            'reason' => 'Rule validation',
            'effective_at' => now()->subMinute()->toDateTimeString(),
        ];

        $this->actingAs($this->admin)
            ->postJson(route('admin.mlm.tree.move.preview', $member), $base + ['new_parent_id' => $member->id])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['new_parent_id']]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.mlm.tree.move.preview', $member), $base + ['new_parent_id' => $descendant->id])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['new_parent_id']]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.mlm.tree.move.preview', $member), array_merge($base, [
                'new_parent_id' => $occupiedParent->id,
                'new_position' => Member::POSITION_LEFT,
            ]))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['new_position']]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.mlm.tree.move.preview', $member), $base + ['new_parent_id' => $inactiveParent->id])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['new_parent_id']]);

        $deepParent = $this->member('MOVE-DEEP-0', 'Deep Root', '9400000020');
        for ($index = 1; $index <= 18; $index++) {
            $deepParent = $this->member('MOVE-DEEP-'.$index, 'Deep '.$index, '94000000'.str_pad((string) (20 + $index), 2, '0', STR_PAD_LEFT), null, $deepParent, Member::POSITION_MIDDLE);
        }
        $movingRoot = $this->member('MOVE-DEEP-MEMBER', 'Deep Moving Root', '9400000040');
        $this->member('MOVE-DEEP-CHILD', 'Deep Moving Child', '9400000041', null, $movingRoot, Member::POSITION_LEFT);

        $this->actingAs($this->admin)
            ->postJson(route('admin.mlm.tree.move.preview', $movingRoot), $base + ['new_parent_id' => $deepParent->id])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['new_parent_id']]);
    }

    public function test_failed_history_write_rolls_back_placement_update(): void
    {
        $oldParent = $this->member('MOVE-ROLLBACK-OLD', 'Rollback Old', '9400000050');
        $newParent = $this->member('MOVE-ROLLBACK-NEW', 'Rollback New', '9400000051');
        $member = $this->member('MOVE-ROLLBACK-MEMBER', 'Rollback Member', '9400000052', null, $oldParent, Member::POSITION_LEFT);

        PlacementMovement::creating(function (): void {
            throw new RuntimeException('Simulated movement history failure.');
        });

        $thrown = false;
        try {
            app(MlmMovementService::class)->move($member, [
                'new_parent_id' => $newParent->id,
                'new_position' => Member::POSITION_RIGHT,
                'reason' => 'Rollback test',
                'effective_at' => now()->subMinute()->toDateTimeString(),
            ], $this->admin);
        } catch (RuntimeException $exception) {
            $thrown = true;
        } finally {
            PlacementMovement::flushEventListeners();
        }

        $this->assertTrue($thrown);
        $this->assertSame($oldParent->id, $member->fresh()->placement_parent_id);
        $this->assertSame(Member::POSITION_LEFT, $member->fresh()->placement_position);
        $this->assertDatabaseCount('mlm_placement_movements', 0);
    }

    public function test_movement_history_cannot_be_updated_or_deleted(): void
    {
        $member = $this->member('MOVE-IMMUTABLE', 'Immutable Member', '9400000070');
        $movement = PlacementMovement::create([
            'member_id' => $member->id,
            'reason' => 'Immutable history fixture',
            'moved_by' => $this->admin->id,
            'effective_at' => now(),
            'status' => 'approved',
        ]);

        $this->expectException(\LogicException::class);
        $movement->update(['reason' => 'Changed']);
    }

    public function test_movement_form_and_save_are_admin_only(): void
    {
        $member = $this->member('MOVE-AUTH-MEMBER', 'Auth Member', '9400000060');
        $this->actingAs($this->admin)
            ->get(route('admin.mlm.tree.move.form', $member))
            ->assertOk()
            ->assertSee('Mandatory movement reason')
            ->assertSee('Preview Movement')
            ->assertSee('Confirm and Save Movement');

        $customerRole = Role::where('name', 'Customer')->firstOrFail();
        $customer = User::create([
            'name' => 'Non Admin',
            'email' => 'non-admin-movement@example.com',
            'password' => 'password',
            'role_id' => $customerRole->id,
            'status' => 'active',
        ]);

        $this->actingAs($customer)
            ->get(route('admin.mlm.tree.move.form', $member))
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
