<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('addresses')) {
            Schema::create('addresses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->enum('type', ['billing', 'shipping'])->default('billing');
                $table->string('fname');
                $table->string('lname')->nullable();
                $table->string('address');
                $table->string('apt')->nullable();
                $table->string('city');
                $table->string('state');
                $table->string('zip');
                $table->string('country')->default('India');
                $table->string('phone');
                $table->boolean('default')->default(false);
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_num')->unique();
                $table->unsignedBigInteger('user_id');
                $table->decimal('subtotal', 10, 2);
                $table->decimal('discount', 10, 2)->default(0);
                $table->decimal('gst_amt', 10, 2)->default(0);
                $table->decimal('ship_charge', 10, 2)->default(0);
                $table->decimal('total', 10, 2);
                $table->unsignedBigInteger('coupon_id')->nullable();
                $table->unsignedBigInteger('ship_id')->nullable();
                $table->enum('status', ['pending', 'processing', 'packed', 'shipped', 'delivered', 'cancelled', 'failed', 'refunded'])->default('pending');
                $table->enum('pay_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
                $table->string('tracking_num')->nullable();
                $table->string('courier')->nullable();
                $table->longText('admin_note')->nullable();
                $table->longText('bill_addr')->nullable();
                $table->longText('ship_addr')->nullable();
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('set null');
                $table->foreign('ship_id')->references('id')->on('shipping_methods')->onDelete('set null');
                $table->index(['order_num', 'status', 'pay_status']);
            });
        }

        if (! Schema::hasTable('coupon_usages')) {
            Schema::create('coupon_usages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('coupon_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('order_id');
                $table->timestamps();
                $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('var_id')->nullable();
                $table->integer('qty');
                $table->decimal('price', 10, 2);
                $table->decimal('gst_pct', 5, 2)->default(0);
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
                $table->foreign('var_id')->references('id')->on('product_variations')->onDelete('set null');
            });
        }

        if (! Schema::hasTable('order_status_history')) {
            Schema::create('order_status_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->enum('status', ['pending', 'processing', 'packed', 'shipped', 'delivered', 'cancelled', 'failed', 'refunded']);
                $table->longText('note')->nullable();
                $table->timestamps();
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->decimal('amount', 10, 2);
                $table->enum('method', ['cod', 'bank_transfer', 'online', 'razorpay'])->default('cod');
                $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
                $table->string('txn_id')->nullable();
                $table->longText('resp')->nullable();
                $table->timestamps();
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->string('inv_num')->unique();
                $table->longText('inv_data')->nullable();
                $table->timestamps();
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('addresses');
    }
};
