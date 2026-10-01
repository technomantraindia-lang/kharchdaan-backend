<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('refunds')) {
            Schema::create('refunds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
                $table->string('refund_num', 50)->unique();
                $table->decimal('amount', 10, 2);
                $table->string('reason')->nullable();
                $table->string('status', 30)->default('pending')->index(); // pending, approved, rejected, completed, failed
                $table->string('gateway_refund_id')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('processed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
