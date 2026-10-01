<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('sub_category_id')->nullable()->after('category_id');
            $table->string('unit', 20)->default('pcs')->after('stock_qty');
            $table->integer('min_order_qty')->default(1)->after('unit');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
            $table->foreign('sub_category_id')->references('id')->on('categories')->onDelete('set null');
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->string('value');
            $table->string('slug');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->foreign('attribute_id')->references('id')->on('product_attributes')->onDelete('cascade');
            $table->unique(['attribute_id', 'slug']);
        });

        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('old_qty');
            $table->integer('change_qty');
            $table->integer('new_qty');
            $table->enum('type', ['add', 'reduce', 'set'])->default('set');
            $table->string('note')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->string('button_text')->nullable()->after('link');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('button_text');
        });

        Schema::dropIfExists('inventory_logs');
        Schema::dropIfExists('attribute_values');

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['sub_category_id']);
            $table->dropColumn(['sub_category_id', 'unit', 'min_order_qty']);
        });

        Schema::dropIfExists('brands');
    }
};
