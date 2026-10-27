<?php

use App\Models\Member;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add slot retention tracking columns to mlm_members
        if (Schema::hasTable('mlm_members')) {
            Schema::table('mlm_members', function (Blueprint $table): void {
                if (! Schema::hasColumn('mlm_members', 'detached_parent_id')) {
                    $table->foreignId('detached_parent_id')
                        ->nullable()
                        ->after('appreciation_note')
                        ->constrained('mlm_members')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('mlm_members', 'detached_parent_position')) {
                    $table->string('detached_parent_position', 20)->nullable()->after('detached_parent_id');
                }
                if (! Schema::hasColumn('mlm_members', 'detached_member_id')) {
                    $table->foreignId('detached_member_id')
                        ->nullable()
                        ->after('detached_parent_position')
                        ->constrained('mlm_members')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('mlm_members', 'detached_child_position')) {
                    $table->string('detached_child_position', 20)->nullable()->after('detached_member_id');
                }
            });
        }

        // 2. Restore Haresh Patel (C22050015) downlines in the live database
        $haresh = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C22050015'))->first();
        if ($haresh) {
            $childrenMap = [
                'C23050001' => 'left',   // Darshna Patel
                'C22070007' => 'middle', // Haneshbhai - Purohit Parsan
                'C22070037' => 'right',  // Ripal Vora
            ];

            foreach ($childrenMap as $code => $position) {
                $child = Member::whereHas('user', fn ($q) => $q->where('mlm_member_id', $code))->first();
                if ($child) {
                    DB::table('mlm_members')->where('id', $child->id)->update([
                        'placement_parent_id' => $haresh->id,
                        'placement_position' => $position,
                        'status' => 'active',
                        'detached_parent_id' => null,
                        'detached_parent_position' => null,
                        'detached_member_id' => null,
                        'detached_child_position' => null,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mlm_members')) {
            Schema::table('mlm_members', function (Blueprint $table): void {
                if (Schema::hasColumn('mlm_members', 'detached_member_id')) {
                    $table->dropForeign(['detached_member_id']);
                    $table->dropColumn('detached_member_id');
                }
                if (Schema::hasColumn('mlm_members', 'detached_parent_id')) {
                    $table->dropForeign(['detached_parent_id']);
                    $table->dropColumn('detached_parent_id');
                }
                if (Schema::hasColumn('mlm_members', 'detached_parent_position')) {
                    $table->dropColumn('detached_parent_position');
                }
                if (Schema::hasColumn('mlm_members', 'detached_child_position')) {
                    $table->dropColumn('detached_child_position');
                }
            });
        }
    }
};
