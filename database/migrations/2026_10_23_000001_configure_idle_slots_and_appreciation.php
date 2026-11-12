<?php

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Safe / idempotent:
     * - Schema changes skip existing columns.
     * - Idle members are created only if missing.
     * - Placement uses claimPlacementSlot(): if a different member already
     *   occupies (parent, position), that occupant is detached (parent/pos
     *   cleared) — never deleted — then the intended member is placed.
     *   Detached occupants are reattached later by placeGroup() where the
     *   client tree design expects them (e.g. leaders under Idle 5–13).
     */
    public function up(): void
    {
        // 1. Add idle slot and appreciation columns to mlm_members
        if (Schema::hasTable('mlm_members')) {
            Schema::table('mlm_members', function (Blueprint $table): void {
                if (! Schema::hasColumn('mlm_members', 'is_idle_slot')) {
                    $table->boolean('is_idle_slot')->default(false)->after('status');
                }
                if (! Schema::hasColumn('mlm_members', 'idle_slot_label')) {
                    $table->string('idle_slot_label', 50)->nullable()->after('is_idle_slot');
                }
                if (! Schema::hasColumn('mlm_members', 'appreciation_member_id')) {
                    $table->foreignId('appreciation_member_id')
                        ->nullable()
                        ->after('idle_slot_label')
                        ->constrained('mlm_members')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('mlm_members', 'appreciation_assigned_at')) {
                    $table->timestamp('appreciation_assigned_at')->nullable()->after('appreciation_member_id');
                }
                if (! Schema::hasColumn('mlm_members', 'appreciation_note')) {
                    $table->string('appreciation_note', 255)->nullable()->after('appreciation_assigned_at');
                }
            });
        }

        // 2. Identify Top Root: Nimesh Jagirdar (C22020008)
        $nimesh = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C22020008'))->first();
        if (! $nimesh) {
            return;
        }

        DB::table('mlm_members')->where('id', $nimesh->id)->update([
            'placement_parent_id' => null,
            'placement_position' => null,
            'sponsor_member_id' => null,
            'status' => 'active',
            'is_idle_slot' => false,
            'idle_slot_label' => null,
        ]);

        $defaultPassword = Hash::make('password');
        $customerRole = DB::table('roles')->where('name', 'Customer')->first();
        $roleId = $customerRole ? $customerRole->id : 4;

        /**
         * Place $memberId under ($parentId, $position) without violating uk_mlm_placement_slot.
         * If another member occupies the slot, detach that occupant (clear parent/pos only).
         * Never deletes members or drops the unique constraint.
         */
        $claimPlacementSlot = function (int $memberId, int $parentId, string $position): void {
            $current = DB::table('mlm_members')->where('id', $memberId)->first();
            if ($current
                && (int) $current->placement_parent_id === $parentId
                && $current->placement_position === $position) {
                DB::table('mlm_members')->where('id', $memberId)->update(['status' => 'active']);

                return;
            }

            // Free the target slot if occupied by someone else.
            $occupant = DB::table('mlm_members')
                ->where('placement_parent_id', $parentId)
                ->where('placement_position', $position)
                ->where('id', '!=', $memberId)
                ->first();

            if ($occupant) {
                DB::table('mlm_members')->where('id', $occupant->id)->update([
                    'placement_parent_id' => null,
                    'placement_position' => null,
                ]);
            }

            // Clear this member's previous slot so unique key stays clean if they move.
            DB::table('mlm_members')->where('id', $memberId)->update([
                'placement_parent_id' => null,
                'placement_position' => null,
            ]);

            DB::table('mlm_members')->where('id', $memberId)->update([
                'placement_parent_id' => $parentId,
                'placement_position' => $position,
                'status' => 'active',
            ]);
        };

        // Helper to ensure an Idle Member exists and is configured
        $ensureIdleMember = function (string $code, string $label) use ($defaultPassword, $roleId): Member {
            $user = User::where('mlm_member_id', $code)->first();
            if (! $user) {
                $user = User::create([
                    'name' => $label,
                    'email' => strtolower($code).'@kharchdaan.com',
                    'password' => $defaultPassword,
                    'plain_password' => 'password',
                    'phone' => '9099142697',
                    'role_id' => $roleId,
                    'status' => 'active',
                    'mlm_member_id' => $code,
                ]);
            } else {
                $user->update([
                    'name' => $label,
                    'status' => 'active',
                ]);
            }

            $member = Member::where('user_id', $user->id)->first();
            if (! $member) {
                $member = Member::create([
                    'user_id' => $user->id,
                    'status' => 'active',
                    'joined_at' => now(),
                    'is_idle_slot' => true,
                    'idle_slot_label' => $label,
                ]);
            } else {
                DB::table('mlm_members')->where('id', $member->id)->update([
                    'status' => 'active',
                    'is_idle_slot' => true,
                    'idle_slot_label' => $label,
                ]);
            }

            return $member->fresh();
        };

        // 3. Create / Configure the 12 Idle IDs (M2 to M13)
        $idle2 = $ensureIdleMember('C25070004', 'Idle - 2');
        $idle3 = $ensureIdleMember('C25070005', 'Idle - 3');
        $idle4 = $ensureIdleMember('C25070006', 'Idle - 4');

        $idle5 = $ensureIdleMember('C25070007', 'Idle - 5');
        $idle6 = $ensureIdleMember('C25070008', 'Idle - 6');
        $idle7 = $ensureIdleMember('C25070009', 'Idle - 7');

        $idle8 = $ensureIdleMember('C25070010', 'Idle - 8');
        $idle9 = $ensureIdleMember('C25070011', 'Idle - 9');
        $idle10 = $ensureIdleMember('C25070012', 'Idle - 10');

        $idle11 = $ensureIdleMember('C25070013', 'Idle - 11');
        $idle12 = $ensureIdleMember('C25070014', 'Idle - 12');
        $idle13 = $ensureIdleMember('C25070015', 'Idle - 13');

        // Place Idle 2, 3, 4 directly under Nimesh (frees previous occupants safely)
        $claimPlacementSlot((int) $idle2->id, (int) $nimesh->id, 'left');
        $claimPlacementSlot((int) $idle3->id, (int) $nimesh->id, 'middle');
        $claimPlacementSlot((int) $idle4->id, (int) $nimesh->id, 'right');

        // Place Idle 5–7 under Idle 2
        $claimPlacementSlot((int) $idle5->id, (int) $idle2->id, 'left');
        $claimPlacementSlot((int) $idle6->id, (int) $idle2->id, 'middle');
        $claimPlacementSlot((int) $idle7->id, (int) $idle2->id, 'right');

        // Place Idle 8–10 under Idle 3
        $claimPlacementSlot((int) $idle8->id, (int) $idle3->id, 'left');
        $claimPlacementSlot((int) $idle9->id, (int) $idle3->id, 'middle');
        $claimPlacementSlot((int) $idle10->id, (int) $idle3->id, 'right');

        // Place Idle 11–13 under Idle 4
        $claimPlacementSlot((int) $idle11->id, (int) $idle4->id, 'left');
        $claimPlacementSlot((int) $idle12->id, (int) $idle4->id, 'middle');
        $claimPlacementSlot((int) $idle13->id, (int) $idle4->id, 'right');

        // 4. Place the 27 Real Distributor Leaders under Idle 5 through Idle 13
        $placeGroup = function (Member $parent, ?string $leftCode, ?string $midCode, ?string $rightCode = null) use ($claimPlacementSlot): void {
            $map = [
                'left' => $leftCode,
                'middle' => $midCode,
                'right' => $rightCode,
            ];
            foreach ($map as $position => $code) {
                if (! $code) {
                    continue;
                }
                $m = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', $code))->first();
                if ($m) {
                    $claimPlacementSlot((int) $m->id, (int) $parent->id, $position);
                }
            }
        };

        $placeGroup($idle5, 'C22010003', 'C22020001', 'C22030018');
        $placeGroup($idle6, 'C22020004', 'C22020010', 'C22030036');
        $placeGroup($idle7, 'C22020012', 'C22020019', 'C22050007');
        $placeGroup($idle8, 'C22060008', 'C22070058', 'C22020013');
        $placeGroup($idle9, 'C22050015', 'C25070002', 'C24070001');
        $placeGroup($idle10, 'C24010714', 'C23070004', 'C23060012');
        $placeGroup($idle11, 'C22100027', 'C22090007', 'C22070072');
        $placeGroup($idle12, 'C22070047', 'C22070021', 'C22050017');
        $placeGroup($idle13, 'C22030027', 'C22030026', 'C22030019');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mlm_members')) {
            Schema::table('mlm_members', function (Blueprint $table): void {
                if (Schema::hasColumn('mlm_members', 'appreciation_member_id')) {
                    $table->dropForeign(['appreciation_member_id']);
                    $table->dropColumn('appreciation_member_id');
                }
                if (Schema::hasColumn('mlm_members', 'is_idle_slot')) {
                    $table->dropColumn(['is_idle_slot', 'idle_slot_label', 'appreciation_assigned_at', 'appreciation_note']);
                }
            });
        }
    }
};
