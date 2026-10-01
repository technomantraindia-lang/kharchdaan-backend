<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'plain_password')) {
                $table->string('plain_password')->nullable()->after('password');
            }
            if (! Schema::hasColumn('users', 'staff_code')) {
                $table->string('staff_code')->nullable()->unique()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'plain_password')) {
                $table->dropColumn('plain_password');
            }
            if (Schema::hasColumn('users', 'staff_code')) {
                $table->dropColumn('staff_code');
            }
        });
    }
};
