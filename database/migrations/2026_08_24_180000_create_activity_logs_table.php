<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('action');
                $table->string('module')->default('general');
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->text('description')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->index(['module', 'action']);
                $table->index(['subject_type', 'subject_id']);
            });
        } else {
            Schema::table('activity_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('activity_logs', 'module')) {
                    $table->string('module')->default('general')->after('action');
                }
                if (! Schema::hasColumn('activity_logs', 'subject_type')) {
                    $table->string('subject_type')->nullable()->after('module');
                }
                if (! Schema::hasColumn('activity_logs', 'subject_id')) {
                    $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
                }
                if (! Schema::hasColumn('activity_logs', 'description')) {
                    $table->text('description')->nullable()->after('subject_id');
                }
                if (! Schema::hasColumn('activity_logs', 'old_values')) {
                    $table->json('old_values')->nullable()->after('description');
                }
                if (! Schema::hasColumn('activity_logs', 'new_values')) {
                    $table->json('new_values')->nullable()->after('old_values');
                }
                if (! Schema::hasColumn('activity_logs', 'ip_address')) {
                    $table->string('ip_address', 45)->nullable()->after('new_values');
                }
                if (! Schema::hasColumn('activity_logs', 'user_agent')) {
                    $table->text('user_agent')->nullable()->after('ip_address');
                }
            });
        }
    }

    public function down(): void
    {
        // Safe additive migration
    }
};
