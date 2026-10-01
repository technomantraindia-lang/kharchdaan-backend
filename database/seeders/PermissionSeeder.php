<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard.view',
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            'products.import',
            'products.bulk_manage',
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',
            'attributes.view',
            'attributes.manage',
            'brands.view',
            'brands.manage',
            'inventory.view',
            'inventory.adjust',
            'orders.view',
            'orders.edit',
            'orders.status',
            'orders.cancel',
            'customers.view',
            'coupons.view',
            'coupons.manage',
            'taxes.view',
            'taxes.manage',
            'shipping.view',
            'shipping.manage',
            'payments.view',
            'payments.manage',
            'invoices.view',
            'inquiries.view',
            'inquiries.manage',
            'reports.view',
            'reports.export',
            'cms.view',
            'cms.manage',
            'woocommerce.view',
            'woocommerce.manage',
            'users.view',
            'users.manage',
            'roles.view',
            'roles.manage',
            'activity_logs.view',
            'settings.view',
            'settings.manage',
            'refunds.view',
            'refunds.manage',
            'mlm.view',
            'mlm.manage',
            'mlm.create',
            'mlm.edit',
            'mlm.move',
            'mlm.block',
            'mlm.kyc.view',
            'mlm.kyc.review',
            'mlm.activity.view',
            'mlm.import',
            'mlm.export',
            'cashback.view',
            'cashback.select',
            'cashback.pool.manage',
            'cashback.approve',
            'cashback.schedule',
            'cashback.pay',
            'cashback.hold',
            'cashback.reverse',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(
                ['name' => $permName],
                ['guard_name' => 'web']
            );
        }

        // Grant standard store operations permissions to Admin role (excluding system master settings & staff management)
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminPermissionIds = Permission::whereNotIn('name', [
                'users.view', 'users.manage',
                'roles.view', 'roles.manage',
                'settings.view', 'settings.manage',
                'activity_logs.view',
            ])->pluck('id');
            $adminRole->permissions()->sync($adminPermissionIds);
        }
    }
}
