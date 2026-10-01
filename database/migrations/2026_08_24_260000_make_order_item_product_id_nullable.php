<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'product_id')) {
            try {
                DB::statement('ALTER TABLE order_items MODIFY product_id BIGINT UNSIGNED NULL;');
            } catch (\Throwable $e) {
                // Fallback for non-mysql
                Schema::table('order_items', function (Blueprint $table) {
                    $table->unsignedBigInteger('product_id')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        // Safe additive migration
    }
};
