<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('woocommerce_sync_logs')) {
            Schema::create('woocommerce_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->string('entity_type', 50)->index(); // product, variation, order, customer, inventory, coupon
                $table->unsignedBigInteger('entity_id')->nullable()->index();
                $table->unsignedBigInteger('woocommerce_id')->nullable()->index();
                $table->string('direction', 50)->default('laravel_to_woocommerce')->index(); // laravel_to_woocommerce, woocommerce_to_laravel
                $table->string('action', 50)->default('sync'); // create, update, delete, sync, import
                $table->string('status', 30)->default('pending')->index(); // pending, processing, success, failed
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->text('error_message')->nullable();
                $table->integer('attempts')->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('woocommerce_sync_logs');
    }
};
