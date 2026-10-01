<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mlm_payout_cycles')) {
            Schema::create('mlm_payout_cycles', function (Blueprint $table): void {
                $table->id();
                $table->date('period_start');
                $table->date('period_end');
                $table->string('status', 30)->default('pending_calculation');
                $table->decimal('total_amount', 24, 18)->default(0);
                $table->unsignedInteger('ledger_count')->default(0);
                $table->string('payment_reference', 150)->nullable();
                $table->timestamp('payment_date')->nullable();
                $table->text('admin_note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('processed_at')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['period_start', 'period_end'],
                    'uk_mlm_payout_cycles_period'
                );
                $table->index('status', 'idx_mlm_payout_cycles_status');
            });
        }

        if (Schema::hasTable('mlm_income_ledgers')) {
            Schema::table('mlm_income_ledgers', function (Blueprint $table): void {
                if (! Schema::hasColumn('mlm_income_ledgers', 'payout_cycle_id')) {
                    $table->foreignId('payout_cycle_id')
                        ->nullable()
                        ->constrained('mlm_payout_cycles')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('mlm_income_ledgers', 'reversal_of_id')) {
                    $table->foreignId('reversal_of_id')
                        ->nullable()
                        ->constrained('mlm_income_ledgers')
                        ->nullOnDelete();
                }
            });

            foreach ([
                'idx_mlm_ledger_payout_cycle' => ['payout_cycle_id'],
                'idx_mlm_ledger_reversal_of' => ['reversal_of_id'],
                'idx_mlm_ledger_income_type' => ['income_type'],
                'idx_mlm_ledger_level' => ['level'],
            ] as $indexName => $columns) {
                if (! Schema::hasIndex('mlm_income_ledgers', $indexName)) {
                    Schema::table('mlm_income_ledgers', function (Blueprint $table) use ($indexName, $columns): void {
                        $table->index($columns, $indexName);
                    });
                }
            }
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive. Financial ledgers and payout history
        // must remain available for reconciliation and audit.
    }
};
