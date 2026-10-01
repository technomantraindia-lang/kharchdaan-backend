<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('notifications', function (Blueprint $table) {
                if (! Schema::hasColumn('notifications', 'data')) {
                    $table->text('data')->nullable();
                }
                if (! Schema::hasColumn('notifications', 'notifiable_type')) {
                    $table->string('notifiable_type')->nullable();
                }
                if (! Schema::hasColumn('notifications', 'notifiable_id')) {
                    $table->unsignedBigInteger('notifiable_id')->nullable();
                }
                if (! Schema::hasColumn('notifications', 'read_at')) {
                    $table->timestamp('read_at')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe additive migration
    }
};
