<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('carts')) {
            Schema::create('carts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique('user_id');
            });
        }

        if (! Schema::hasTable('cart_items')) {
            Schema::create('cart_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cart_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('var_id')->nullable();
                $table->integer('qty');
                $table->decimal('price', 10, 2);
                $table->decimal('sale_price', 10, 2)->nullable();
                $table->timestamps();
                $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('var_id')->references('id')->on('product_variations')->onDelete('set null');
            });
        }

        if (! Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->enum('type', ['fixed', 'percentage'])->default('fixed');
                $table->decimal('value', 10, 2);
                $table->decimal('min_order', 10, 2)->nullable();
                $table->decimal('max_discount', 10, 2)->nullable();
                $table->integer('usage_limit')->nullable();
                $table->integer('per_user_limit')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
                $table->index(['code', 'status']);
            });
        }

        if (! Schema::hasTable('taxes')) {
            Schema::create('taxes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('percentage', 5, 2);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->text('desc')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('shipping_methods')) {
            Schema::create('shipping_methods', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('charge', 10, 2);
                $table->decimal('min_free_order', 10, 2)->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
