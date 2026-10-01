<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('seller_state')->nullable()->after('bill_addr');
            $table->string('customer_state')->nullable()->after('seller_state');
            $table->decimal('cgst_amt', 10, 2)->default(0)->after('gst_amt');
            $table->decimal('sgst_amt', 10, 2)->default(0)->after('cgst_amt');
            $table->decimal('igst_amt', 10, 2)->default(0)->after('sgst_amt');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['seller_state', 'customer_state', 'cgst_amt', 'sgst_amt', 'igst_amt']);
        });
    }
};
