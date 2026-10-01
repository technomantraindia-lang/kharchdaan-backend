<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mlm_members')) {
            Schema::table('mlm_members', function (Blueprint $table): void {
                if (! Schema::hasColumn('mlm_members', 'profile_photo_path')) {
                    $table->string('profile_photo_path', 500)->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('mlm_members', 'sponsor_name_snapshot')) {
                    $table->string('sponsor_name_snapshot')->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'sponsor_relationship_status')) {
                    $table->string('sponsor_relationship_status', 20)->nullable()->default('active');
                }
                if (! Schema::hasColumn('mlm_members', 'sponsor_assigned_at')) {
                    $table->timestamp('sponsor_assigned_at')->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'kyc_status')) {
                    $table->string('kyc_status', 20)->default('pending');
                }
                if (! Schema::hasColumn('mlm_members', 'pan_number')) {
                    $table->text('pan_number')->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'pan_hash')) {
                    $table->string('pan_hash', 64)->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'aadhaar_reference')) {
                    $table->text('aadhaar_reference')->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'aadhaar_hash')) {
                    $table->string('aadhaar_hash', 64)->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'bank_account_holder_name')) {
                    $table->string('bank_account_holder_name')->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'bank_account_number')) {
                    $table->text('bank_account_number')->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'bank_account_hash')) {
                    $table->string('bank_account_hash', 64)->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'ifsc_code')) {
                    $table->string('ifsc_code', 20)->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'bank_name')) {
                    $table->string('bank_name')->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'bank_branch')) {
                    $table->string('bank_branch')->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'cancelled_cheque_path')) {
                    $table->string('cancelled_cheque_path', 500)->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'kyc_rejection_reason')) {
                    $table->text('kyc_rejection_reason')->nullable();
                }
                if (! Schema::hasColumn('mlm_members', 'kyc_verified_by')) {
                    $table->foreignId('kyc_verified_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('mlm_members', 'kyc_verified_at')) {
                    $table->timestamp('kyc_verified_at')->nullable();
                }
            });

            foreach ([
                'idx_mlm_members_created_by' => ['created_by', false],
                'idx_mlm_members_kyc_status' => ['kyc_status', false],
                'idx_mlm_members_joined_at' => ['joined_at', false],
                'uk_mlm_members_pan_hash' => ['pan_hash', true],
                'uk_mlm_members_aadhaar_hash' => ['aadhaar_hash', true],
                'uk_mlm_members_bank_account_hash' => ['bank_account_hash', true],
            ] as $indexName => [$column, $unique]) {
                if (! Schema::hasIndex('mlm_members', $indexName)) {
                    Schema::table('mlm_members', function (Blueprint $table) use ($column, $indexName, $unique): void {
                        if ($unique) {
                            $table->unique($column, $indexName);
                        } else {
                            $table->index($column, $indexName);
                        }
                    });
                }
            }
        }

        if (Schema::hasTable('users') && ! Schema::hasIndex('users', 'idx_users_mlm_phone')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index('phone', 'idx_users_mlm_phone');
            });
        }

        if (! Schema::hasTable('mlm_member_sequences')) {
            Schema::create('mlm_member_sequences', function (Blueprint $table): void {
                $table->id();
                $table->string('period', 4)->unique('uk_mlm_member_sequences_period');
                $table->unsignedInteger('next_number')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mlm_kyc_histories')) {
            Schema::create('mlm_kyc_histories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('member_id')->constrained('mlm_members')->restrictOnDelete();
                $table->string('old_status', 20)->nullable();
                $table->string('new_status', 20);
                $table->text('rejection_reason')->nullable();
                $table->string('cancelled_cheque_path', 500)->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('changed_at')->nullable();
                $table->timestamps();

                $table->index('member_id', 'idx_mlm_kyc_histories_member');
                $table->index('new_status', 'idx_mlm_kyc_histories_status');
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive. Member and KYC history must remain
        // available for future tree, income, and audit workflows.
    }
};
