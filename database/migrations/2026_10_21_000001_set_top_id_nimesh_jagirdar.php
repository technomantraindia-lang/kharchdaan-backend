<?php

use App\Models\Member;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $nimesh = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C22020008'))->first();
        if (! $nimesh) {
            return;
        }

        $mCodes = [
            'C25070003','C25070004','C25070005','C25070006',
            'C25070007','C25070008','C25070009','C25070010',
            'C25070011','C25070012','C25070013','C25070014','C25070015',
        ];

        // 1. Detach and deactivate placeholder company nodes (M1 to M13)
        $mMembers = Member::whereHas('user', fn ($q) => $q->whereIn('mlm_member_id', $mCodes))->get();
        foreach ($mMembers as $m) {
            DB::table('mlm_members')->where('id', $m->id)->update([
                'placement_parent_id' => null,
                'placement_position' => null,
                'status' => 'inactive',
            ]);
        }

        // 2. Establish Top ID: Nimesh Jagirdar (single root, no placement parent, no sponsor)
        DB::table('mlm_members')->where('id', $nimesh->id)->update([
            'placement_parent_id' => null,
            'placement_position' => null,
            'sponsor_member_id' => null,
            'status' => 'active',
        ]);

        // 3. Place Top 3 Real Distributor Leaders directly under Nimesh (Level 1)
        $janak = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C22010003'))->first();
        $niru = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C22020001'))->first();
        $gaurav = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C22030018'))->first();

        if ($janak) {
            DB::table('mlm_members')->where('id', $janak->id)->update([
                'placement_parent_id' => $nimesh->id,
                'placement_position' => 'left',
                'status' => 'active',
            ]);
        }
        if ($niru) {
            DB::table('mlm_members')->where('id', $niru->id)->update([
                'placement_parent_id' => $nimesh->id,
                'placement_position' => 'middle',
                'status' => 'active',
            ]);
        }
        if ($gaurav) {
            DB::table('mlm_members')->where('id', $gaurav->id)->update([
                'placement_parent_id' => $nimesh->id,
                'placement_position' => 'right',
                'status' => 'active',
            ]);
        }

        // Helper to place direct children under specified real distributor parent
        $placeGroup = function (string $parentCode, ?string $leftCode, ?string $midCode, ?string $rightCode = null): void {
            $parent = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', $parentCode))->first();
            if (! $parent) {
                return;
            }

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

        // 4. Attach leader groups into real distributor branches (preserving all teams & downlines):
        // Janakben branch (Left pillar):
        $placeGroup('C22030035', 'C22020004', 'C22020010', 'C22030036'); // Vandna -> Chetan Vasani, Niyati Raval, Mahendra Patadia
        $placeGroup('C22050012', 'C22020012', 'C22020019', 'C22050007'); // Neelam -> Juliben Vyas, Rajendrabhai Shah, Bhavnaben Shashtri

        // Niruben branch (Middle pillar):
        $placeGroup('C22110013', 'C22060008', 'C22070058', 'C22020013'); // Nayan Dadwani -> Meena Pattni, Nachiket Chawda, Chaula Barot
        $placeGroup('C23030013', 'C22050015', 'C25070002', 'C24070001'); // Hiren Bhatt -> Haresh Patel, Om Joshi, Yash Maharaja
        $placeGroup('C22120023', 'C24010714', 'C23070004', 'C23060012'); // Dipali Nehe -> Suraj Mishra, Umesh Kalavadiya, Dharmesh Hindocha

        // Gaurav branch (Right pillar):
        $placeGroup('C24070727', 'C22100027', 'C22090007', 'C22070072'); // Prakash Sagar -> Parthivbhai Vyas, Bhavna Patel, Malove Mehta
        $placeGroup('C24070781', 'C22070047', 'C22070021', 'C22050017'); // Jagdish Patel -> Harshendra Bais, Jayprakash Vyas, Molina Dave
        $placeGroup('C24080001', 'C22030027', 'C22030026', 'C22030019'); // Dashrath Panchal -> Tejaskumar Raval, Kinjal Mehta, Jatin Daftari
        $placeGroup('C24080735', 'C23010013', 'C23010022', null);         // Sushil Vyas -> Paresh Gajjar, Dhara Patel
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible
    }
};
