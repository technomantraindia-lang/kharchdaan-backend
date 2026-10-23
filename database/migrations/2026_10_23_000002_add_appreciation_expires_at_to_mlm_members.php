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
        if (Schema::hasTable('mlm_members')) {
            Schema::table('mlm_members', function (Blueprint $table): void {
                if (! Schema::hasColumn('mlm_members', 'appreciation_expires_at')) {
                    $table->timestamp('appreciation_expires_at')->nullable()->after('appreciation_assigned_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mlm_members')) {
            Schema::table('mlm_members', function (Blueprint $table): void {
                if (Schema::hasColumn('mlm_members', 'appreciation_expires_at')) {
                    $table->dropColumn('appreciation_expires_at');
                }
            });
        }
    }
};
