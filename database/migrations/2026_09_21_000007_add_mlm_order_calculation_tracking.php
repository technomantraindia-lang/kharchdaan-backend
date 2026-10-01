<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('orders', 'mlm_error_message')) {
                    $table->text('mlm_error_message')->nullable();
                }
                if (! Schema::hasColumn('orders', 'mlm_processed_at')) {
                    $table->timestamp('mlm_processed_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('mlm_calculation_runs')) {
            Schema::table('mlm_calculation_runs', function (Blueprint $table): void {
                if (! Schema::hasColumn('mlm_calculation_runs', 'order_id')) {
                    $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                }
                if (! Schema::hasColumn('mlm_calculation_runs', 'processing_started_at')) {
                    $table->timestamp('processing_started_at')->nullable();
                }
                if (! Schema::hasColumn('mlm_calculation_runs', 'processed_at')) {
                    $table->timestamp('processed_at')->nullable();
                }
                if (! Schema::hasColumn('mlm_calculation_runs', 'processing_time_ms')) {
                    $table->unsignedBigInteger('processing_time_ms')->nullable();
                }
                if (! Schema::hasColumn('mlm_calculation_runs', 'error_message')) {
                    $table->text('error_message')->nullable();
                }
            });

            if (! Schema::hasIndex('mlm_calculation_runs', 'uk_mlm_calculation_runs_order_id')) {
                Schema::table('mlm_calculation_runs', function (Blueprint $table): void {
                    $table->unique('order_id', 'uk_mlm_calculation_runs_order_id');
                });
            }
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive. Financial integration history is retained.
    }
};
