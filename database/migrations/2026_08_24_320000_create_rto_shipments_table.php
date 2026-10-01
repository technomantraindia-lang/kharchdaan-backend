<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rto_shipments')) {
            Schema::create('rto_shipments', function (Blueprint $table) {
                $table->id();
                $table->string('rto_num', 50)->unique();
                $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
                $table->string('shipment_id', 100)->nullable();
                $table->string('reason')->nullable();
                $table->string('status', 40)->default('rto_initiated')->index(); // rto_initiated, rto_in_transit, rto_received, rto_inspected, rto_restocked, rto_damaged, rto_closed
                $table->timestamp('received_at')->nullable();
                $table->timestamp('inspected_at')->nullable();
                $table->timestamp('restocked_at')->nullable();
                $table->integer('damaged_qty')->default(0);
                $table->integer('restocked_qty')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rto_shipments');
    }
};
