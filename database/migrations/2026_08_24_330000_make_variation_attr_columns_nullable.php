<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_variations')) {
            if (Schema::hasColumn('product_variations', 'attr_id')) {
                try {
                    DB::statement('ALTER TABLE product_variations MODIFY attr_id BIGINT UNSIGNED NULL;');
                } catch (\Throwable $e) {
                    Schema::table('product_variations', function (Blueprint $table) {
                        $table->unsignedBigInteger('attr_id')->nullable()->change();
                    });
                }
            }

            if (Schema::hasColumn('product_variations', 'attr_val')) {
                try {
                    DB::statement('ALTER TABLE product_variations MODIFY attr_val VARCHAR(255) NULL;');
                } catch (\Throwable $e) {
                    Schema::table('product_variations', function (Blueprint $table) {
                        $table->string('attr_val')->nullable()->change();
                    });
                }
            }
        }
    }

    public function down(): void
    {
        // Safe additive migration
    }
};
