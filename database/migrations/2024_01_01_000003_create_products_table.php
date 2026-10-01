<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('gst_percentage', 5, 2)->default(18);
            $table->integer('stock_qty');
            $table->integer('low_stock_qty')->default(5);
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('image')->nullable();
            $table->text('short_desc')->nullable();
            $table->longText('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_desc')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('featured')->default(false);
            $table->timestamps();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->index(['slug', 'status', 'featured']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('image');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('attr_id');
            $table->string('attr_val');
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock_qty')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('attr_id')->references('id')->on('product_attributes')->onDelete('cascade');
            $table->unique(['product_id', 'attr_id', 'attr_val']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
};
