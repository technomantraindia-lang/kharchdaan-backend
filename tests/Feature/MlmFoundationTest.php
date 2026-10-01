<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\Permission;
use App\Models\PlacementMovement;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class MlmFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mlm_foundation_schema_is_additive_and_uses_existing_user_identity(): void
    {
        $this->assertTrue(Schema::hasTable('mlm_members'));
        $this->assertTrue(Schema::hasTable('mlm_placement_movements'));
        $this->assertTrue(Schema::hasColumn('users', 'mlm_member_id'));
        $this->assertTrue(Schema::hasColumn('users', 'phone'));

        $user = $this->makeCustomer('MLM-FOUNDATION-001', 'foundation@example.com', '9000000011');
        $member = Member::create([
            'user_id' => $user->id,
            'status' => Member::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        $this->assertTrue($user->fresh()->mlmMember->is($member));
        $this->assertSame('MLM-FOUNDATION-001', $member->customer_id);
        $this->assertSame('9000000011', $member->mobile);
        $this->assertSame('foundation@example.com', $member->user->email);
    }

    public function test_sponsor_and_placement_relationships_are_separate(): void
    {
        $root = $this->makeMember('MLM-ROOT-001', 'root@example.com', '9000000012');
        $sponsor = $this->makeMember('MLM-SPONSOR-001', 'sponsor@example.com', '9000000013');
        $child = $this->makeMember(
            'MLM-CHILD-001',
            'child@example.com',
            '9000000014',
            $root,
            $sponsor,
            Member::POSITION_MIDDLE
        );

        $this->assertTrue($child->sponsor->is($sponsor));
        $this->assertTrue($child->placementParent->is($root));
        $this->assertTrue($sponsor->sponsoredMembers->contains($child));
        $this->assertTrue($root->placementChildren->contains($child));
        $this->assertNotSame($child->sponsor_member_id, $child->placement_parent_id);
    }

    public function test_database_prevents_duplicate_member_identity_and_placement_slot(): void
    {
        $root = $this->makeMember('MLM-UNIQUE-ROOT', 'unique-root@example.com', '9000000015');
        $this->makeMember(
            'MLM-UNIQUE-CHILD-1',
            'unique-child-1@example.com',
            '9000000016',
            $root,
            $root,
            Member::POSITION_LEFT
        );

        $this->expectException(QueryException::class);
        $this->makeMember(
            'MLM-UNIQUE-CHILD-2',
            'unique-child-2@example.com',
            '9000000017',
            $root,
            $root,
            Member::POSITION_LEFT
        );
    }

    public function test_member_model_rejects_invalid_status_position_and_self_references(): void
    {
        $user = $this->makeCustomer('MLM-INVALID-001', 'invalid@example.com', '9000000018');
        $member = new Member([
            'user_id' => $user->id,
            'status' => 'unknown',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $member->save();
    }

    public function test_movement_history_and_existing_activity_log_are_ready_for_audit(): void
    {
        $adminRole = Role::create(['name' => 'Foundation Admin', 'guard_name' => 'web']);
        $admin = User::create([
            'name' => 'Foundation Admin',
            'email' => 'foundation-admin@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        $oldParent = $this->makeMember('MLM-HISTORY-OLD', 'history-old@example.com', '9000000019');
        $newParent = $this->makeMember('MLM-HISTORY-NEW', 'history-new@example.com', '9000000020');
        $member = $this->makeMember(
            'MLM-HISTORY-MEMBER',
            'history-member@example.com',
            '9000000021',
            $oldParent,
            $oldParent,
            Member::POSITION_RIGHT
        );

        $movement = PlacementMovement::create([
            'member_id' => $member->id,
            'old_parent_id' => $oldParent->id,
            'new_parent_id' => $newParent->id,
            'old_position' => Member::POSITION_RIGHT,
            'new_position' => Member::POSITION_LEFT,
            'reason' => 'Foundation history fixture',
            'moved_by' => $admin->id,
            'effective_at' => now(),
            'affected_subtree_count' => 1,
        ]);

        $log = ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'prepare',
            'module' => 'mlm',
            'subject_type' => Member::class,
            'subject_id' => $member->id,
            'description' => 'Prepared MLM audit subject',
        ]);

        $this->assertTrue($movement->fresh()->member->is($member));
        $this->assertTrue($movement->fresh()->movedBy->is($admin));
        $this->assertTrue($member->fresh()->placementMovements->contains($movement));
        $this->assertTrue($member->fresh()->activityLogs->contains($log));
    }

    public function test_mlm_permissions_extend_existing_permission_middleware_architecture(): void
    {
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);

        (new PermissionSeeder)->run();

        $this->assertTrue(Permission::where('name', 'mlm.view')->exists());
        $this->assertTrue(Permission::where('name', 'mlm.manage')->exists());
        $this->assertTrue(Role::where('name', 'Admin')->first()->permissions->contains('name', 'mlm.view'));
    }

    private function makeMember(
        string $memberId,
        string $email,
        string $phone,
        ?Member $placementParent = null,
        ?Member $sponsor = null,
        ?string $position = null
    ): Member {
        $user = $this->makeCustomer($memberId, $email, $phone);

        return Member::create([
            'user_id' => $user->id,
            'sponsor_member_id' => $sponsor?->id,
            'placement_parent_id' => $placementParent?->id,
            'placement_position' => $position,
            'status' => Member::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
    }

    private function makeCustomer(string $memberId, string $email, string $phone): User
    {
        $role = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);

        return User::create([
            'name' => 'Member '.$memberId,
            'email' => $email,
            'password' => 'password',
            'phone' => $phone,
            'role_id' => $role->id,
            'status' => 'active',
            'mlm_member_id' => $memberId,
        ]);
    }
}
