<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cashback_eligibilities')) {
            Schema::create('cashback_eligibilities', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('order_id')->unique('uk_cashback_eligibilities_order')->constrained('orders')->restrictOnDelete();
                $table->foreignId('member_id')->constrained('mlm_members')->restrictOnDelete();
                $table->foreignId('calculation_run_id')->nullable()->constrained('mlm_calculation_runs')->nullOnDelete();
                $table->foreignId('rule_version_id')->nullable()->constrained('mlm_calculation_rules')->nullOnDelete();
                $table->string('idempotency_key', 128)->unique('uk_cashback_eligibilities_idempotency');
                $table->decimal('final_eligible_amount', 24, 18);
                $table->decimal('maximum_cashback_amount', 24, 18)->default(0);
                $table->decimal('eligibility_threshold', 24, 18);
                $table->string('formula_version', 50)->nullable();
                $table->string('status', 40)->default('not_eligible');
                $table->text('ineligibility_reason')->nullable();
                $table->timestamp('eligibility_date')->nullable();
                $table->text('eligibility_snapshot')->nullable();
                $table->timestamps();

                $table->index('member_id', 'idx_cashback_eligibilities_member');
                $table->index('calculation_run_id', 'idx_cashback_eligibilities_calculation');
                $table->index('status', 'idx_cashback_eligibilities_status');
                $table->index('eligibility_date', 'idx_cashback_eligibilities_date');
            });
        }

        if (! Schema::hasTable('cashback_adjustments')) {
            Schema::create('cashback_adjustments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('cashback_eligibility_id')->constrained('cashback_eligibilities')->restrictOnDelete();
                $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
                $table->foreignId('member_id')->constrained('mlm_members')->restrictOnDelete();
                $table->string('adjustment_type', 40);
                $table->decimal('amount', 24, 18);
                $table->string('idempotency_key', 128)->unique('uk_cashback_adjustments_idempotency');
                $table->text('reason');
                $table->text('adjustment_snapshot')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('cashback_eligibility_id', 'idx_cashback_adjustments_eligibility');
                $table->index('order_id', 'idx_cashback_adjustments_order');
                $table->index('member_id', 'idx_cashback_adjustments_member');
                $table->index('adjustment_type', 'idx_cashback_adjustments_type');
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive. Cashback eligibility and recovery history
        // must remain available for audit and reconciliation.
    }
};
