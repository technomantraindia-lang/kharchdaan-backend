<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'phone')) {
                return;
            }

            // Soft uniqueness: null phones allowed multiple times on some DBs via partial indexes;
            // application validation enforces one mobile = one ID for non-empty values.
            try {
                $table->unique('phone');
            } catch (\Throwable) {
                // Index may already exist or duplicates may block — keep app-level Rule::unique.
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropUnique(['phone']);
            } catch (\Throwable) {
                // ignore
            }
        });
    }
};
