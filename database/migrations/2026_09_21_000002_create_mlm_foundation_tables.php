<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mlm_members')) {
            Schema::create('mlm_members', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')
                    ->unique('uk_mlm_members_user_id')
                    ->constrained('users')
                    ->restrictOnDelete();
                $table->foreignId('sponsor_member_id')
                    ->nullable()
                    ->constrained('mlm_members')
                    ->nullOnDelete();
                $table->foreignId('placement_parent_id')
                    ->nullable()
                    ->constrained('mlm_members')
                    ->nullOnDelete();
                $table->string('placement_position', 10)->nullable();
                $table->string('status', 20)->default('active');
                $table->timestamp('joined_at')->nullable();
                $table->timestamps();

                $table->index('sponsor_member_id', 'idx_mlm_members_sponsor');
                $table->index('placement_parent_id', 'idx_mlm_members_placement_parent');
                $table->index('placement_position', 'idx_mlm_members_placement_position');
                $table->index('status', 'idx_mlm_members_status');
                $table->unique(
                    ['placement_parent_id', 'placement_position'],
                    'uk_mlm_placement_slot'
                );
            });
        }

        if (! Schema::hasTable('mlm_placement_movements')) {
            Schema::create('mlm_placement_movements', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('member_id')
                    ->constrained('mlm_members')
                    ->restrictOnDelete();
                $table->foreignId('old_parent_id')
                    ->nullable()
                    ->constrained('mlm_members')
                    ->nullOnDelete();
                $table->foreignId('new_parent_id')
                    ->nullable()
                    ->constrained('mlm_members')
                    ->nullOnDelete();
                $table->string('old_position', 10)->nullable();
                $table->string('new_position', 10)->nullable();
                $table->text('reason')->nullable();
                $table->foreignId('moved_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('effective_at')->nullable();
                $table->unsignedInteger('affected_subtree_count')->nullable();
                $table->timestamps();

                $table->index('member_id', 'idx_mlm_movements_member');
                $table->index('moved_by', 'idx_mlm_movements_moved_by');
                $table->index('effective_at', 'idx_mlm_movements_effective_at');
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive. MLM foundation tables must not be
        // removed automatically from an existing database.
    }
};
