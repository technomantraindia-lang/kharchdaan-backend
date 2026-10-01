<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. products table (check existing fields from Phase 8 & add indexes if missing)
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'woocommerce_id')) {
                $table->unsignedBigInteger('woocommerce_id')->nullable()->unique()->index();
            }
            if (! Schema::hasColumn('products', 'woocommerce_sync_status')) {
                $table->string('woocommerce_sync_status', 30)->nullable()->default('pending')->index();
            }
            if (! Schema::hasColumn('products', 'woocommerce_synced_at')) {
                $table->timestamp('woocommerce_synced_at')->nullable();
            }
        });

        // 2. product_variations table
        Schema::table('product_variations', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variations', 'woocommerce_id')) {
                $table->unsignedBigInteger('woocommerce_id')->nullable()->index();
            }
            if (! Schema::hasColumn('product_variations', 'woocommerce_sync_status')) {
                $table->string('woocommerce_sync_status', 30)->nullable()->default('pending');
            }
            if (! Schema::hasColumn('product_variations', 'woocommerce_synced_at')) {
                $table->timestamp('woocommerce_synced_at')->nullable();
            }
        });

        // 3. orders table
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'woocommerce_id')) {
                $table->unsignedBigInteger('woocommerce_id')->nullable()->unique()->index();
            }
            if (! Schema::hasColumn('orders', 'woocommerce_synced_at')) {
                $table->timestamp('woocommerce_synced_at')->nullable();
            }
            if (! Schema::hasColumn('orders', 'currency')) {
                $table->string('currency', 10)->nullable()->default('INR');
            }
            if (! Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method', 50)->nullable();
            }
            if (! Schema::hasColumn('orders', 'txn_id')) {
                $table->string('txn_id', 100)->nullable();
            }
        });

        // 4. order_items table (snapshot fields and nullable product_id support)
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'product_name')) {
                $table->string('product_name')->nullable();
            }
            if (! Schema::hasColumn('order_items', 'sku')) {
                $table->string('sku', 100)->nullable();
            }
            if (! Schema::hasColumn('order_items', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0.00);
            }
            if (! Schema::hasColumn('order_items', 'line_total')) {
                $table->decimal('line_total', 10, 2)->nullable();
            }
        });

        // 5. users table
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'woocommerce_customer_id')) {
                $table->unsignedBigInteger('woocommerce_customer_id')->nullable()->unique()->index();
            }
        });

        // 6. coupons table
        Schema::table('coupons', function (Blueprint $table) {
            if (! Schema::hasColumn('coupons', 'woocommerce_id')) {
                $table->unsignedBigInteger('woocommerce_id')->nullable()->unique()->index();
            }
        });
    }

    public function down(): void
    {
        // Safe additive migration: do not drop columns in down
    }
};
