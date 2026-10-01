<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'method')) {
            try {
                DB::statement('ALTER TABLE payments MODIFY method VARCHAR(100) NOT NULL;');
            } catch (\Throwable $e) {
                // Fallback for non-mysql
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('method', 100)->change();
                });
            }
        }
    }

    public function down(): void
    {
        // Safe additive migration
    }
};
