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

        // Helper to ensure an Idle Member exists and is configured
        $ensureIdleMember = function (string $code, string $label) use ($defaultPassword, $roleId): Member {
            $user = User::where('mlm_member_id', $code)->first();
            if (! $user) {
                $user = User::create([
                    'name' => $label,
                    'email' => strtolower($code) . '@kharchdaan.com',
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
        // Level 2 (Immediate next 3 under Nimesh):
        $idle2 = $ensureIdleMember('C25070004', 'Idle - 2');
        $idle3 = $ensureIdleMember('C25070005', 'Idle - 3');
        $idle4 = $ensureIdleMember('C25070006', 'Idle - 4');

        // Level 3 (Under Idle-2):
        $idle5 = $ensureIdleMember('C25070007', 'Idle - 5');
        $idle6 = $ensureIdleMember('C25070008', 'Idle - 6');
        $idle7 = $ensureIdleMember('C25070009', 'Idle - 7');

        // Level 3 (Under Idle-3):
        $idle8  = $ensureIdleMember('C25070010', 'Idle - 8');
        $idle9  = $ensureIdleMember('C25070011', 'Idle - 9');
        $idle10 = $ensureIdleMember('C25070012', 'Idle - 10');

        // Level 3 (Under Idle-4):
        $idle11 = $ensureIdleMember('C25070013', 'Idle - 11');
        $idle12 = $ensureIdleMember('C25070014', 'Idle - 12');
        $idle13 = $ensureIdleMember('C25070015', 'Idle - 13');

        // Place Idle 2, 3, 4 directly under Nimesh
        DB::table('mlm_members')->where('id', $idle2->id)->update([
            'placement_parent_id' => $nimesh->id,
            'placement_position' => 'left',
            'status' => 'active',
        ]);
        DB::table('mlm_members')->where('id', $idle3->id)->update([
            'placement_parent_id' => $nimesh->id,
            'placement_position' => 'middle',
            'status' => 'active',
        ]);
        DB::table('mlm_members')->where('id', $idle4->id)->update([
            'placement_parent_id' => $nimesh->id,
            'placement_position' => 'right',
            'status' => 'active',
        ]);

        // Place Idle 5, 6, 7 under Idle 2
        DB::table('mlm_members')->where('id', $idle5->id)->update(['placement_parent_id' => $idle2->id, 'placement_position' => 'left', 'status' => 'active']);
        DB::table('mlm_members')->where('id', $idle6->id)->update(['placement_parent_id' => $idle2->id, 'placement_position' => 'middle', 'status' => 'active']);
        DB::table('mlm_members')->where('id', $idle7->id)->update(['placement_parent_id' => $idle2->id, 'placement_position' => 'right', 'status' => 'active']);

        // Place Idle 8, 9, 10 under Idle 3
        DB::table('mlm_members')->where('id', $idle8->id)->update(['placement_parent_id' => $idle3->id, 'placement_position' => 'left', 'status' => 'active']);
        DB::table('mlm_members')->where('id', $idle9->id)->update(['placement_parent_id' => $idle3->id, 'placement_position' => 'middle', 'status' => 'active']);
        DB::table('mlm_members')->where('id', $idle10->id)->update(['placement_parent_id' => $idle3->id, 'placement_position' => 'right', 'status' => 'active']);

        // Place Idle 11, 12, 13 under Idle 4
        DB::table('mlm_members')->where('id', $idle11->id)->update(['placement_parent_id' => $idle4->id, 'placement_position' => 'left', 'status' => 'active']);
        DB::table('mlm_members')->where('id', $idle12->id)->update(['placement_parent_id' => $idle4->id, 'placement_position' => 'middle', 'status' => 'active']);
        DB::table('mlm_members')->where('id', $idle13->id)->update(['placement_parent_id' => $idle4->id, 'placement_position' => 'right', 'status' => 'active']);

        // 4. Place the 27 Real Distributor Leaders under Idle 5 through Idle 13
        $placeGroup = function (Member $parent, ?string $leftCode, ?string $midCode, ?string $rightCode = null): void {
            if ($leftCode) {
                $m = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', $leftCode))->first();
                if ($m) {
                    DB::table('mlm_members')->where('id', $m->id)->update([
                        'placement_parent_id' => $parent->id,
                        'placement_position' => 'left',
                        'status' => 'active',
                    ]);
                }
            }
            if ($midCode) {
                $m = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', $midCode))->first();
                if ($m) {
                    DB::table('mlm_members')->where('id', $m->id)->update([
                        'placement_parent_id' => $parent->id,
                        'placement_position' => 'middle',
                        'status' => 'active',
                    ]);
                }
            }
            if ($rightCode) {
                $m = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', $rightCode))->first();
                if ($m) {
                    DB::table('mlm_members')->where('id', $m->id)->update([
                        'placement_parent_id' => $parent->id,
                        'placement_position' => 'right',
                        'status' => 'active',
                    ]);
                }
            }
        };

        // Idle 5 (M5): Janakben Bihola, Niruben Zala, Gaurav Agarwal
        $placeGroup($idle5, 'C22010003', 'C22020001', 'C22030018');

        // Idle 6 (M6): Chetan Vasani, Niyati Raval, Mahendra Patadia
        $placeGroup($idle6, 'C22020004', 'C22020010', 'C22030036');

        // Idle 7 (M7): Juliben Vyas, Rajendrabhai Shah, Bhavnaben Shashtri
        $placeGroup($idle7, 'C22020012', 'C22020019', 'C22050007');

        // Idle 8 (M8): Meena Pattni, Nachiket Chawda, Chaula Barot
        $placeGroup($idle8, 'C22060008', 'C22070058', 'C22020013');

        // Idle 9 (M9): Haresh Patel, Om Joshi, Yash Maharaja
        $placeGroup($idle9, 'C22050015', 'C25070002', 'C24070001');

        // Idle 10 (M10): Suraj Mishra, Umesh Kalavadiya, Dharmesh Hindocha
        $placeGroup($idle10, 'C24010714', 'C23070004', 'C23060012');

        // Idle 11 (M11): Parthivbhai Vyas, Bhavna Patel, Malove Mehta
        $placeGroup($idle11, 'C22100027', 'C22090007', 'C22070072');

        // Idle 12 (M12): Harshendra Bais, Jayprakash Vyas, Molina Dave
        $placeGroup($idle12, 'C22070047', 'C22070021', 'C22050017');

        // Idle 13 (M13): Tejaskumar Raval, Kinjal Mehta, Jatin Daftari
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
