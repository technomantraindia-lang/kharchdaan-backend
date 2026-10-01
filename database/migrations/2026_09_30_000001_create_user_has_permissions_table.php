<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_has_permissions')) {
            Schema::create('user_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('permission_id');
                $table->primary(['user_id', 'permission_id']);
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            });
        }

        // Ensure 'Sub Admin' role exists
        Role::firstOrCreate(['name' => 'Sub Admin'], ['guard_name' => 'web']);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_has_permissions');
    }
};
