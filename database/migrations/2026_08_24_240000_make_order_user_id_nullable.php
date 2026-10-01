<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Support guest checkout orders from WooCommerce by allowing user_id to be nullable
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'user_id')) {
            try {
                DB::statement('ALTER TABLE orders MODIFY user_id BIGINT UNSIGNED NULL;');
            } catch (\Throwable $e) {
                // Fallback for sqlite/other drivers
                Schema::table('orders', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        // Additive safe migration
    }
};
