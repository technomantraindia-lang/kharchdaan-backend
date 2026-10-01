<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mlm_calculation_rules')) {
            Schema::create('mlm_calculation_rules', function (Blueprint $table): void {
                $table->id();
                $table->string('version', 50)->unique('uk_mlm_calculation_rules_version');
                $table->string('name', 150);
                $table->decimal('high_pv_rate', 24, 18);
                $table->decimal('low_pv_rate', 24, 18);
                $table->decimal('pv_divisor', 24, 18);
                $table->decimal('income_rate', 24, 18);
                $table->unsignedTinyInteger('high_level_start')->default(0);
                $table->unsignedTinyInteger('high_level_end')->default(7);
                $table->unsignedTinyInteger('low_level_start')->default(8);
                $table->unsignedTinyInteger('low_level_end')->default(19);
                $table->string('status', 20)->default('active');
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'effective_from'], 'idx_mlm_rules_status_effective');
            });
        }

        if (! DB::table('mlm_calculation_rules')->where('version', 'v1.0')->exists()) {
            DB::table('mlm_calculation_rules')->insert([
                'version' => 'v1.0',
                'name' => 'MLM PV Formula v1',
                'high_pv_rate' => '13.5',
                'low_pv_rate' => '0.75',
                'pv_divisor' => '3000',
                'income_rate' => '0.20',
                'high_level_start' => 0,
                'high_level_end' => 7,
                'low_level_start' => 8,
                'low_level_end' => 19,
                'status' => 'active',
                'effective_from' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('mlm_calculation_runs')) {
            Schema::create('mlm_calculation_runs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('purchasing_member_id')->constrained('mlm_members')->restrictOnDelete();
                $table->foreignId('rule_version_id')->constrained('mlm_calculation_rules')->restrictOnDelete();
                $table->string('source_transaction_reference', 150);
                $table->string('idempotency_key', 128)->unique('uk_mlm_calculation_runs_idempotency');
                $table->timestamp('transaction_date');
                $table->decimal('eligible_amount', 24, 18);
                $table->decimal('total_pv', 24, 18)->default(0);
                $table->decimal('total_income', 24, 18)->default(0);
                $table->string('status', 20)->default('calculated');
                $table->text('placement_path_snapshot')->nullable();
                $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('purchasing_member_id', 'idx_mlm_runs_purchasing_member');
                $table->index('source_transaction_reference', 'idx_mlm_runs_source_reference');
                $table->index('transaction_date', 'idx_mlm_runs_transaction_date');
            });
        }

        if (! Schema::hasTable('mlm_income_ledgers')) {
            Schema::create('mlm_income_ledgers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('calculation_run_id')->constrained('mlm_calculation_runs')->restrictOnDelete();
                $table->foreignId('member_id')->constrained('mlm_members')->restrictOnDelete();
                $table->foreignId('purchasing_member_id')->constrained('mlm_members')->restrictOnDelete();
                $table->foreignId('rule_version_id')->constrained('mlm_calculation_rules')->restrictOnDelete();
                $table->string('source_transaction_reference', 150);
                $table->timestamp('transaction_date');
                $table->string('income_type', 30);
                $table->unsignedTinyInteger('level');
                $table->decimal('eligible_amount', 24, 18);
                $table->decimal('pv_rate', 24, 18);
                $table->decimal('rate', 24, 18);
                $table->decimal('pv', 24, 18);
                $table->decimal('calculated_amount', 24, 18);
                $table->string('status', 20)->default('calculated');
                $table->text('placement_path_snapshot')->nullable();
                $table->timestamps();

                $table->unique(
                    ['calculation_run_id', 'member_id', 'level', 'income_type'],
                    'uk_mlm_income_ledger_line'
                );
                $table->index(['member_id', 'transaction_date'], 'idx_mlm_ledger_member_date');
                $table->index('source_transaction_reference', 'idx_mlm_ledger_source_reference');
                $table->index('status', 'idx_mlm_ledger_status');
            });
        }

        if (! Schema::hasTable('mlm_calculation_audits')) {
            Schema::create('mlm_calculation_audits', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('calculation_run_id')->constrained('mlm_calculation_runs')->restrictOnDelete();
                $table->string('event', 50);
                $table->text('payload')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('calculation_run_id', 'idx_mlm_calculation_audits_run');
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive. Financial calculations and audit rows
        // must remain available for historical review and reconciliation.
    }
};
