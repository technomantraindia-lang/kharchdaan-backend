<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashback_eligibilities', function (Blueprint $table) {
            if (! Schema::hasColumn('cashback_eligibilities', 'sponsor_cashback_amount')) {
                $table->decimal('sponsor_cashback_amount', 18, 2)->default(0)->after('maximum_cashback_amount');
            }
            if (! Schema::hasColumn('cashback_eligibilities', 'sponsor_member_id')) {
                $table->unsignedBigInteger('sponsor_member_id')->nullable()->after('member_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cashback_eligibilities', function (Blueprint $table) {
            if (Schema::hasColumn('cashback_eligibilities', 'sponsor_cashback_amount')) {
                $table->dropColumn('sponsor_cashback_amount');
            }
            if (Schema::hasColumn('cashback_eligibilities', 'sponsor_member_id')) {
                $table->dropColumn('sponsor_member_id');
            }
        });
    }
};
