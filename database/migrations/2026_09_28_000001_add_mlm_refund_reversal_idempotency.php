<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mlm_income_ledgers')) {
            return;
        }

        Schema::table('mlm_income_ledgers', function (Blueprint $table): void {
            if (! Schema::hasColumn('mlm_income_ledgers', 'source_refund_id')) {
                $table->foreignId('source_refund_id')->nullable()->constrained('refunds')->nullOnDelete();
            }
            if (! Schema::hasColumn('mlm_income_ledgers', 'reversal_event_key')) {
                $table->string('reversal_event_key', 128)->nullable();
            }
            if (! Schema::hasColumn('mlm_income_ledgers', 'ledger_entry_key')) {
                $table->string('ledger_entry_key', 128)->nullable();
            }
        });

        DB::table('mlm_income_ledgers')
            ->whereNull('ledger_entry_key')
            ->orderBy('id')
            ->eachById(function (object $ledger): void {
                DB::table('mlm_income_ledgers')
                    ->where('id', $ledger->id)
                    ->update(['ledger_entry_key' => 'legacy-ledger-'.$ledger->id]);
            });

        if (Schema::hasIndex('mlm_income_ledgers', 'uk_mlm_income_ledger_line')) {
            Schema::table('mlm_income_ledgers', function (Blueprint $table): void {
                if (! Schema::hasIndex('mlm_income_ledgers', 'idx_mlm_income_ledger_calc_run')) {
                    $table->index('calculation_run_id', 'idx_mlm_income_ledger_calc_run');
                }
                $table->dropUnique('uk_mlm_income_ledger_line');
            });
        }

        if (! Schema::hasIndex('mlm_income_ledgers', 'uk_mlm_ledger_entry_key')) {
            Schema::table('mlm_income_ledgers', function (Blueprint $table): void {
                $table->unique('ledger_entry_key', 'uk_mlm_ledger_entry_key');
            });
        }

        if (! Schema::hasIndex('mlm_income_ledgers', 'uk_mlm_ledger_reversal_event')) {
            Schema::table('mlm_income_ledgers', function (Blueprint $table): void {
                $table->unique('reversal_event_key', 'uk_mlm_ledger_reversal_event');
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: financial reversal history must remain available.
    }
};
