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

        $m1 = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C25070003'))->first();
        $m2 = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C25070004'))->first();
        $m3 = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C25070005'))->first();
        $m4 = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C25070006'))->first();
        $m12 = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C25070014'))->first();

        $hb = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C22070047'))->first();
        $pg = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C23010013'))->first();
        $dp = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C23010022'))->first();

        if (! $m1 || ! $m2 || ! $m3 || ! $m4 || ! $m12) {
            return;
        }

        $ids = array_filter([
            $nimesh->id,
            $m1->id,
            $m2->id,
            $m3->id,
            $m4->id,
            $hb?->id,
            $pg?->id,
            $dp?->id,
        ]);

        // 0. Temporarily detach all nodes involved to avoid unique constraint collisions
        DB::table('mlm_members')->whereIn('id', $ids)
            ->update(['placement_parent_id' => null, 'placement_position' => null]);

        // 1. Nimesh is Top Root (parent=null, pos=null, sponsor=null)
        DB::table('mlm_members')->where('id', $nimesh->id)->update([
            'placement_parent_id' => null,
            'placement_position' => null,
            'sponsor_member_id' => null,
        ]);

        // 2. M2, M3, M4 under Nimesh
        DB::table('mlm_members')->where('id', $m2->id)->update(['placement_parent_id' => $nimesh->id, 'placement_position' => 'left']);
        DB::table('mlm_members')->where('id', $m3->id)->update(['placement_parent_id' => $nimesh->id, 'placement_position' => 'middle']);
        DB::table('mlm_members')->where('id', $m4->id)->update(['placement_parent_id' => $nimesh->id, 'placement_position' => 'right']);

        // 3. M1 under M12 (pos=left)
        DB::table('mlm_members')->where('id', $m1->id)->update(['placement_parent_id' => $m12->id, 'placement_position' => 'left']);

        // 4. HB, PG, DP under M1
        if ($hb) {
            DB::table('mlm_members')->where('id', $hb->id)->update(['placement_parent_id' => $m1->id, 'placement_position' => 'left']);
        }
        if ($pg) {
            DB::table('mlm_members')->where('id', $pg->id)->update(['placement_parent_id' => $m1->id, 'placement_position' => 'middle']);
        }
        if ($dp) {
            DB::table('mlm_members')->where('id', $dp->id)->update(['placement_parent_id' => $m1->id, 'placement_position' => 'right']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible if needed
    }
};
