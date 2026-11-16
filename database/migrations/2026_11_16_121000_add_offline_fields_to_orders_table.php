<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'order_channel')) {
                $table->string('order_channel', 20)->default('online')->after('payment_method');
            }
            if (! Schema::hasColumn('orders', 'special_instruction')) {
                $table->text('special_instruction')->nullable()->after('admin_note');
            }
            if (! Schema::hasColumn('orders', 'offline_remark')) {
                $table->text('offline_remark')->nullable()->after('special_instruction');
            }
            if (! Schema::hasColumn('orders', 'offline_document_path')) {
                $table->string('offline_document_path')->nullable()->after('offline_remark');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['order_channel', 'special_instruction', 'offline_remark', 'offline_document_path'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
