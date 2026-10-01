<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionNames = [
            'cashback.view',
            'cashback.select',
            'cashback.pool.manage',
            'cashback.approve',
            'cashback.schedule',
            'cashback.pay',
            'cashback.hold',
            'cashback.reverse',
        ];

        foreach ($permissionNames as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                ['guard_name' => 'web', 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $permissionIds = DB::table('permissions')->whereIn('name', $permissionNames)->pluck('id');
        $adminRole = DB::table('roles')->where('name', 'Admin')->value('id');
        if ($adminRole) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'role_id' => $adminRole,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        $accountsRole = DB::table('roles')->where('name', 'Accounts')->first();
        if (! $accountsRole) {
            $accountsRoleId = DB::table('roles')->insertGetId([
                'name' => 'Accounts',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $accountsRoleId = $accountsRole->id;
        }

        $accountsPermissions = DB::table('permissions')
            ->whereIn('name', ['cashback.view', 'cashback.approve', 'cashback.schedule', 'cashback.pay'])
            ->pluck('id');
        foreach ($accountsPermissions as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'role_id' => $accountsRoleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive. Permissions and role assignments are retained.
    }
};
