<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cashback_profit_pools')) {
            Schema::create('cashback_profit_pools', function (Blueprint $table): void {
                $table->id();
                $table->string('pool_reference', 100)->unique('uk_cashback_pools_reference');
                $table->decimal('approved_available_amount', 24, 18);
                $table->decimal('allocated_amount', 24, 18)->default(0);
                $table->string('status', 20)->default('active');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('admin_note')->nullable();
                $table->timestamps();

                $table->index('status', 'idx_cashback_pools_status');
            });
        }

        if (Schema::hasTable('cashback_eligibilities')) {
            Schema::table('cashback_eligibilities', function (Blueprint $table): void {
                if (! Schema::hasColumn('cashback_eligibilities', 'selected_pool_id')) {
                    $table->foreignId('selected_pool_id')->nullable()->constrained('cashback_profit_pools')->nullOnDelete();
                }
                if (! Schema::hasColumn('cashback_eligibilities', 'selected_by')) {
                    $table->foreignId('selected_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('cashback_eligibilities', 'selected_at')) {
                    $table->timestamp('selected_at')->nullable();
                }
            });

            foreach ([
                'idx_cashback_eligibilities_pool' => 'selected_pool_id',
                'idx_cashback_eligibilities_selected_at' => 'selected_at',
            ] as $indexName => $column) {
                if (! Schema::hasIndex('cashback_eligibilities', $indexName)) {
                    Schema::table('cashback_eligibilities', function (Blueprint $table) use ($indexName, $column): void {
                        $table->index($column, $indexName);
                    });
                }
            }
        }

        if (! Schema::hasTable('cashback_payout_batches')) {
            Schema::create('cashback_payout_batches', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('pool_id')->constrained('cashback_profit_pools')->restrictOnDelete();
                $table->string('batch_reference', 100)->unique('uk_cashback_batches_reference');
                $table->decimal('total_amount', 24, 18);
                $table->unsignedInteger('record_count')->default(0);
                $table->string('status', 30)->default('pending_approval');
                $table->timestamp('scheduled_payment_date')->nullable();
                $table->string('payment_reference', 150)->nullable();
                $table->string('payment_proof_path', 500)->nullable();
                $table->text('admin_note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('scheduled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index('pool_id', 'idx_cashback_batches_pool');
                $table->index('status', 'idx_cashback_batches_status');
            });
        }

        if (! Schema::hasTable('cashback_payout_batch_items')) {
            Schema::create('cashback_payout_batch_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('batch_id')->constrained('cashback_payout_batches')->restrictOnDelete();
                $table->foreignId('cashback_eligibility_id')->unique('uk_cashback_batch_item_record')->constrained('cashback_eligibilities')->restrictOnDelete();
                $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
                $table->foreignId('member_id')->constrained('mlm_members')->restrictOnDelete();
                $table->decimal('amount', 24, 18);
                $table->text('record_snapshot')->nullable();
                $table->timestamps();

                $table->index('batch_id', 'idx_cashback_batch_items_batch');
                $table->index('order_id', 'idx_cashback_batch_items_order');
                $table->index('member_id', 'idx_cashback_batch_items_member');
            });
        }

        if (! Schema::hasTable('cashback_status_histories')) {
            Schema::create('cashback_status_histories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('cashback_eligibility_id')->constrained('cashback_eligibilities')->restrictOnDelete();
                $table->foreignId('batch_id')->nullable()->constrained('cashback_payout_batches')->nullOnDelete();
                $table->string('from_status', 40)->nullable();
                $table->string('to_status', 40);
                $table->text('reason')->nullable();
                $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('metadata')->nullable();
                $table->timestamps();

                $table->index('cashback_eligibility_id', 'idx_cashback_status_history_record');
                $table->index('batch_id', 'idx_cashback_status_history_batch');
            });
        }

        if (! Schema::hasTable('cashback_action_histories')) {
            Schema::create('cashback_action_histories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('cashback_eligibility_id')->nullable()->constrained('cashback_eligibilities')->nullOnDelete();
                $table->foreignId('batch_id')->nullable()->constrained('cashback_payout_batches')->nullOnDelete();
                $table->string('action', 50);
                $table->text('reason')->nullable();
                $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('metadata')->nullable();
                $table->timestamps();

                $table->index('cashback_eligibility_id', 'idx_cashback_action_history_record');
                $table->index('batch_id', 'idx_cashback_action_history_batch');
                $table->index('action', 'idx_cashback_action_history_action');
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive. Cashback payout and audit history is retained.
    }
};
