<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mlm_payout_cycles')) {
            Schema::table('mlm_payout_cycles', function (Blueprint $table): void {
                if (! Schema::hasColumn('mlm_payout_cycles', 'cycle_reference')) {
                    $table->string('cycle_reference', 100)->nullable()->unique('uk_mlm_payout_cycles_reference');
                }
                if (! Schema::hasColumn('mlm_payout_cycles', 'calculation_date')) {
                    $table->timestamp('calculation_date')->nullable();
                }
                if (! Schema::hasColumn('mlm_payout_cycles', 'total_members')) {
                    $table->unsignedInteger('total_members')->default(0);
                }
                if (! Schema::hasColumn('mlm_payout_cycles', 'gross_income')) {
                    $table->decimal('gross_income', 24, 18)->default(0);
                }
                if (! Schema::hasColumn('mlm_payout_cycles', 'adjustment_amount')) {
                    $table->decimal('adjustment_amount', 24, 18)->default(0);
                }
                if (! Schema::hasColumn('mlm_payout_cycles', 'net_payable')) {
                    $table->decimal('net_payable', 24, 18)->default(0);
                }
                if (! Schema::hasColumn('mlm_payout_cycles', 'payment_proof_path')) {
                    $table->string('payment_proof_path', 500)->nullable();
                }
                if (! Schema::hasColumn('mlm_payout_cycles', 'hold_reason')) {
                    $table->text('hold_reason')->nullable();
                }
                if (! Schema::hasColumn('mlm_payout_cycles', 'on_hold_by')) {
                    $table->foreignId('on_hold_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('mlm_payout_cycles', 'on_hold_at')) {
                    $table->timestamp('on_hold_at')->nullable();
                }
            });

            Schema::table('mlm_payout_cycles', function (Blueprint $table): void {
                $table->index('calculation_date', 'idx_mlm_payout_cycles_calculation_date');
            });

            // Existing cycles predate the human-readable reference. Fill them
            // deterministically before enforcing the reference at application level.
            foreach (\App\Models\MlmPayoutCycle::query()->whereNull('cycle_reference')->get() as $cycle) {
                $cycle->forceFill([
                    'cycle_reference' => 'MLM-'.($cycle->period_start?->format('Ymd') ?? $cycle->id).'-'.($cycle->period_end?->format('Ymd') ?? $cycle->id),
                    'net_payable' => $cycle->total_amount,
                ])->saveQuietly();
            }
        }

        if (! Schema::hasTable('mlm_payout_lines')) {
            Schema::create('mlm_payout_lines', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payout_cycle_id')->constrained('mlm_payout_cycles')->restrictOnDelete();
                $table->foreignId('member_id')->constrained('mlm_members')->restrictOnDelete();
                $table->decimal('gross_income', 24, 18)->default(0);
                $table->decimal('adjustment_amount', 24, 18)->default(0);
                $table->decimal('net_payable', 24, 18)->default(0);
                $table->unsignedInteger('ledger_count')->default(0);
                $table->string('status', 30)->default('pending_approval');
                $table->timestamps();

                $table->unique(['payout_cycle_id', 'member_id'], 'uk_mlm_payout_lines_cycle_member');
                $table->index('member_id', 'idx_mlm_payout_lines_member');
                $table->index('status', 'idx_mlm_payout_lines_status');
            });
        }
    }

    public function down(): void
    {
        // Financial payout history is intentionally retained.
    }
};
