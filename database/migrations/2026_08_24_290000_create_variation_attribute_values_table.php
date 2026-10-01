<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Upgrade product_variations table columns safely without dropping existing ones
        Schema::table('product_variations', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variations', 'cost_price')) {
                $table->decimal('cost_price', 10, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('product_variations', 'reserved_stock')) {
                $table->integer('reserved_stock')->default(0)->after('stock_qty');
            }
            if (! Schema::hasColumn('product_variations', 'image')) {
                $table->string('image')->nullable()->after('reserved_stock');
            }
            if (! Schema::hasColumn('product_variations', 'weight')) {
                $table->decimal('weight', 8, 2)->nullable()->after('image');
            }
        });

        // 2. Create multi-attribute pivot table variation_attribute_values
        if (! Schema::hasTable('variation_attribute_values')) {
            Schema::create('variation_attribute_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_variation_id')->constrained('product_variations')->onDelete('cascade');
                $table->foreignId('attribute_id')->constrained('product_attributes')->onDelete('cascade');
                $table->foreignId('attribute_value_id')->constrained('attribute_values')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['product_variation_id', 'attribute_id'], 'uk_var_attr');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('variation_attribute_values');
    }
};
