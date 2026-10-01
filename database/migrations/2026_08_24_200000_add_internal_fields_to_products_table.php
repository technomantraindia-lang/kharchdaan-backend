<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'cost_price')) {
                $table->decimal('cost_price', 10, 2)->nullable()->after('sale_price');
            }
            if (! Schema::hasColumn('products', 'hsn_code')) {
                $table->string('hsn_code', 50)->nullable()->after('sku');
            }
            if (! Schema::hasColumn('products', 'reserved_stock')) {
                $table->integer('reserved_stock')->default(0)->after('stock_qty');
            }
            if (! Schema::hasColumn('products', 'woocommerce_id')) {
                $table->unsignedBigInteger('woocommerce_id')->nullable()->unique()->after('featured');
            }
            if (! Schema::hasColumn('products', 'woocommerce_sync_status')) {
                $table->string('woocommerce_sync_status', 50)->nullable()->after('woocommerce_id');
            }
            if (! Schema::hasColumn('products', 'woocommerce_synced_at')) {
                $table->timestamp('woocommerce_synced_at')->nullable()->after('woocommerce_sync_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['cost_price', 'hsn_code', 'reserved_stock', 'woocommerce_id', 'woocommerce_sync_status', 'woocommerce_synced_at'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
