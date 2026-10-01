<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('webhook_logs')) {
            Schema::create('webhook_logs', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 50)->default('woocommerce')->index();
                $table->string('delivery_id', 100)->nullable();
                $table->string('topic', 100)->index();
                $table->string('resource', 100)->nullable();
                $table->string('event', 100)->nullable();
                $table->boolean('signature_valid')->default(true);
                $table->json('payload')->nullable();
                $table->string('status', 30)->default('received')->index(); // received, processing, processed, failed, duplicate, ignored
                $table->text('error_message')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->unique(['provider', 'delivery_id'], 'uk_provider_delivery');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
