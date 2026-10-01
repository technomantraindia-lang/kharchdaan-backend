<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('woocommerce_sync_conflicts')) {
            Schema::create('woocommerce_sync_conflicts', function (Blueprint $table) {
                $table->id();
                $table->string('entity_type', 50)->index(); // product, variation, order, inventory, customer
                $table->unsignedBigInteger('entity_id')->nullable()->index();
                $table->unsignedBigInteger('woocommerce_id')->nullable()->index();
                $table->string('field_name', 100)->nullable();
                $table->text('laravel_value')->nullable();
                $table->text('woocommerce_value')->nullable();
                $table->string('status', 30)->default('open')->index(); // open, resolved, ignored
                $table->string('resolution', 50)->nullable(); // use_laravel, use_woocommerce, manual
                $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('resolved_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('woocommerce_sync_conflicts');
    }
};
