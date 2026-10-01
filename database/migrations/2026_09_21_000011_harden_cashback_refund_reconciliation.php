<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cashback_eligibilities')) {
            Schema::table('cashback_eligibilities', function (Blueprint $table): void {
                if (! Schema::hasColumn('cashback_eligibilities', 'original_eligible_amount')) {
                    $table->decimal('original_eligible_amount', 24, 18)->nullable()->after('final_eligible_amount');
                }
                if (! Schema::hasColumn('cashback_eligibilities', 'current_eligible_amount')) {
                    $table->decimal('current_eligible_amount', 24, 18)->nullable()->after('original_eligible_amount');
                }
                if (! Schema::hasColumn('cashback_eligibilities', 'original_cashback_amount')) {
                    $table->decimal('original_cashback_amount', 24, 18)->nullable()->after('maximum_cashback_amount');
                }
                if (! Schema::hasColumn('cashback_eligibilities', 'refunded_amount')) {
                    $table->decimal('refunded_amount', 24, 18)->default(0)->after('current_eligible_amount');
                }
                if (! Schema::hasColumn('cashback_eligibilities', 'refund_status')) {
                    $table->string('refund_status', 30)->nullable()->after('status');
                }
                if (! Schema::hasColumn('cashback_eligibilities', 'last_processing_error')) {
                    $table->text('last_processing_error')->nullable()->after('refund_status');
                }
                if (! Schema::hasColumn('cashback_eligibilities', 'last_processed_at')) {
                    $table->timestamp('last_processed_at')->nullable()->after('eligibility_date');
                }
            });
            Schema::table('cashback_eligibilities', function (Blueprint $table): void {
                $table->index('refund_status', 'idx_cashback_eligibilities_refund_status');
                $table->index('last_processed_at', 'idx_cashback_eligibilities_last_processed');
            });
        }

        if (Schema::hasTable('cashback_adjustments') && ! Schema::hasColumn('cashback_adjustments', 'source_refund_id')) {
            Schema::table('cashback_adjustments', function (Blueprint $table): void {
                $table->foreignId('source_refund_id')->nullable()->constrained('refunds')->nullOnDelete();
                $table->index('source_refund_id', 'idx_cashback_adjustments_refund');
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: reconciliation and recovery history must remain available.
    }
};
