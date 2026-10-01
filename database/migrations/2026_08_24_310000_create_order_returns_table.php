<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_returns')) {
            Schema::create('order_returns', function (Blueprint $table) {
                $table->id();
                $table->string('return_num', 50)->unique();
                $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('reason');
                $table->string('status', 40)->default('requested')->index(); // requested, approved, rejected, pickup_scheduled, received, inspected, completed
                $table->text('customer_note')->nullable();
                $table->text('admin_note')->nullable();
                $table->timestamp('restocked_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('return_items')) {
            Schema::create('return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_return_id')->constrained('order_returns')->onDelete('cascade');
                $table->foreignId('order_item_id')->nullable()->constrained('order_items')->onDelete('cascade');
                $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
                $table->integer('quantity');
                $table->string('reason')->nullable();
                $table->string('condition', 50)->nullable(); // good, damaged, missing
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('order_returns');
    }
};
