<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'mlm_member_id')) {
                $table->string('mlm_member_id', 50)->nullable();
            }
        });

        if (! Schema::hasIndex('users', 'uk_users_mlm_member_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('mlm_member_id', 'uk_users_mlm_member_id');
            });
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'mlm_processing_status')) {
                $table->string('mlm_processing_status', 30)
                    ->nullable()
                    ->default('not_started');
            }

            if (! Schema::hasColumn('orders', 'mlm_eligible_amount')) {
                $table->decimal('mlm_eligible_amount', 12, 2)->nullable();
            }

            if (! Schema::hasColumn('orders', 'mlm_pv_processing_status')) {
                $table->string('mlm_pv_processing_status', 30)
                    ->nullable()
                    ->default('not_started');
            }

            if (! Schema::hasColumn('orders', 'mlm_formula_version')) {
                $table->string('mlm_formula_version', 50)->nullable();
            }

            if (! Schema::hasColumn('orders', 'mlm_integration_reference')) {
                $table->string('mlm_integration_reference', 100)->nullable();
            }

            if (! Schema::hasColumn('orders', 'mlm_reversal_status')) {
                $table->string('mlm_reversal_status', 30)
                    ->nullable()
                    ->default('not_applicable');
            }
        });

        $orderIndexes = [
            'idx_orders_mlm_processing_status' => 'mlm_processing_status',
            'idx_orders_mlm_pv_status' => 'mlm_pv_processing_status',
            'uk_orders_mlm_integration_ref' => 'mlm_integration_reference',
            'idx_orders_mlm_reversal_status' => 'mlm_reversal_status',
        ];

        foreach ($orderIndexes as $indexName => $column) {
            if (! Schema::hasIndex('orders', $indexName)) {
                Schema::table('orders', function (Blueprint $table) use ($indexName, $column): void {
                    if ($indexName === 'uk_orders_mlm_integration_ref') {
                        $table->unique($column, $indexName);
                    } else {
                        $table->index($column, $indexName);
                    }
                });
            }
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive. MLM integration fields are additive and
        // must not be removed automatically from an existing e-commerce database.
    }
};
