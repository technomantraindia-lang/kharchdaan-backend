<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mlm_placement_movements')) {
            return;
        }

        Schema::table('mlm_placement_movements', function (Blueprint $table): void {
            if (! Schema::hasColumn('mlm_placement_movements', 'status')) {
                $table->string('status', 20)->default('approved');
            }
            if (! Schema::hasColumn('mlm_placement_movements', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (! Schema::hasColumn('mlm_placement_movements', 'old_path_snapshot')) {
                $table->text('old_path_snapshot')->nullable();
            }
            if (! Schema::hasColumn('mlm_placement_movements', 'new_path_snapshot')) {
                $table->text('new_path_snapshot')->nullable();
            }
            if (! Schema::hasColumn('mlm_placement_movements', 'old_depth')) {
                $table->unsignedInteger('old_depth')->nullable();
            }
            if (! Schema::hasColumn('mlm_placement_movements', 'new_depth')) {
                $table->unsignedInteger('new_depth')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive. Movement history and calculation
        // snapshots must remain available after a rollback attempt.
    }
};
