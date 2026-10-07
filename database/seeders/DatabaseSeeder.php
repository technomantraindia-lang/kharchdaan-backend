<?php

namespace Database\Seeders;

use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Role;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin'], ['guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Admin'], ['guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Accounts'], ['guard_name' => 'web']);
        $customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);

        $this->call(PermissionSeeder::class);

        $subAdminRole = Role::firstOrCreate(['name' => 'Sub Admin'], ['guard_name' => 'web']);
        $limitedPermissions = \App\Models\Permission::whereIn('name', [
            'dashboard.view',
            'products.view',
            'products.create',
            'products.edit',
            'categories.view',
            'brands.view',
            'inventory.view',
            'inventory.adjust',
            'orders.view',
            'orders.edit',
            'orders.status',
            'customers.view',
            'inquiries.view',
        ])->pluck('id');
        $subAdminRole->permissions()->sync($limitedPermissions);

        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'phone' => '9876543210',
                'role_id' => $superAdminRole->id,
                'status' => 'active',
            ]
        );

        $subAdmin = User::updateOrCreate(
            ['email' => 'subadmin@example.com'],
            [
                'name' => 'Store Manager (Sub-Admin)',
                'staff_code' => 'SUB-1001',
                'password' => 'password',
                'phone' => '9876543299',
                'role_id' => $subAdminRole->id,
                'status' => 'active',
            ]
        );

        $customer = User::updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'John Doe',
                'password' => 'password',
                'phone' => '9876543211',
                'role_id' => $customerRole->id,
                'status' => 'active',
            ]
        );

        $grocery = Category::firstOrCreate(
            ['slug' => 'grocery'],
            [
                'name' => 'Grocery',
                'description' => 'Fresh grocery items',
                'status' => 'active',
            ]
        );

        $beverages = Category::firstOrCreate(
            ['slug' => 'beverages'],
            [
                'name' => 'Beverages',
                'description' => 'Various beverages',
                'status' => 'active',
            ]
        );

        $snacks = Category::firstOrCreate(
            ['slug' => 'snacks'],
            [
                'name' => 'Snacks',
                'parent_id' => $grocery->id,
                'description' => 'Snacks and munchies',
                'status' => 'active',
            ]
        );

        $brand = Brand::firstOrCreate(
            ['slug' => 'kharchdaan'],
            ['name' => 'KharchDaan', 'status' => 'active']
        );

        $package = Product::firstOrCreate(
            ['sku' => 'KD001'],
            [
                'name' => 'Direct Selling Starter Package',
                'slug' => 'direct-selling-starter-package',
                'category_id' => $grocery->id,
                'sub_category_id' => $snacks->id,
                'brand_id' => $brand->id,
                'price' => 1500.00,
                'sale_price' => 1000.00,
                'gst_percentage' => 5,
                'stock_qty' => 500,
                'low_stock_qty' => 10,
                'unit' => 'pack',
                'min_order_qty' => 1,
                'weight' => 1,
                'image' => 'images/tata-dal.jpg',
                'short_desc' => 'Direct Selling Starter Membership & Cashback Package',
                'description' => 'Comprehensive Direct Selling package with 100% conditional cashback benefits.',
                'status' => 'active',
                'featured' => true,
            ]
        );

        $wellness = Product::firstOrCreate(
            ['sku' => 'KD002'],
            [
                'name' => 'KharchDaan Wellness Pack',
                'slug' => 'kharchdaan-wellness-pack',
                'category_id' => $beverages->id,
                'price' => 2000.00,
                'sale_price' => 1800.00,
                'gst_percentage' => 5,
                'stock_qty' => 500,
                'low_stock_qty' => 5,
                'unit' => 'packet',
                'min_order_qty' => 1,
                'weight' => 0.5,
                'image' => 'images/health-wellness-ayurveda.jpg',
                'short_desc' => 'Premium wellness package',
                'description' => 'Direct Selling wellness kit.',
                'status' => 'active',
                'featured' => true,
            ]
        );

        $weightAttr = ProductAttribute::firstOrCreate(['name' => 'Weight'], ['status' => 'active']);
        $packAttr = ProductAttribute::firstOrCreate(['name' => 'Pack Size'], ['status' => 'active']);
        AttributeValue::firstOrCreate(['slug' => '1kg'], ['attribute_id' => $weightAttr->id, 'value' => '1kg', 'status' => 'active']);
        AttributeValue::firstOrCreate(['slug' => '500g'], ['attribute_id' => $weightAttr->id, 'value' => '500g', 'status' => 'active']);
        AttributeValue::firstOrCreate(['slug' => 'small'], ['attribute_id' => $packAttr->id, 'value' => 'Small', 'status' => 'active']);
        AttributeValue::firstOrCreate(['slug' => 'large'], ['attribute_id' => $packAttr->id, 'value' => 'Large', 'status' => 'active']);

        Tax::firstOrCreate(['name' => '5% GST'], ['percentage' => 5, 'status' => 'active']);
        Tax::firstOrCreate(['name' => '12% GST'], ['percentage' => 12, 'status' => 'active']);
        Tax::firstOrCreate(['name' => '18% GST'], ['percentage' => 18, 'status' => 'active']);
        Tax::firstOrCreate(['name' => '28% GST'], ['percentage' => 28, 'status' => 'active']);

        $standardShipping = ShippingMethod::firstOrCreate(
            ['name' => 'Standard Delivery'],
            [
                'charge' => 50.00,
                'min_free_order' => 500.00,
                'status' => 'active',
            ]
        );

        ShippingMethod::firstOrCreate(
            ['name' => 'Express Delivery'],
            [
                'charge' => 100.00,
                'min_free_order' => 1000.00,
                'status' => 'active',
            ]
        );

        $coupon = Coupon::firstOrCreate(
            ['code' => 'SAVE10'],
            [
                'type' => 'percentage',
                'value' => 10,
                'min_order' => 200,
                'usage_limit' => 100,
                'per_user_limit' => 2,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(3)->toDateString(),
                'status' => 'active',
            ]
        );

        Setting::updateOrCreate(['key' => 'company_name'], ['value' => 'KharchDaan.Com']);
        Setting::updateOrCreate(['key' => 'company_address'], ['value' => 'Delhi, India']);
        Setting::updateOrCreate(['key' => 'company_email'], ['value' => 'support@kharchdaan.com']);
        Setting::updateOrCreate(['key' => 'company_phone'], ['value' => '9876543210']);
        Setting::updateOrCreate(['key' => 'gst_number'], ['value' => '07AABCU9603R1Z0']);
        Setting::updateOrCreate(['key' => 'currency'], ['value' => 'INR']);
        Setting::updateOrCreate(['key' => 'order_prefix'], ['value' => 'KD']);

        $order = Order::firstOrCreate(
            ['order_num' => 'KD-10001'],
            [
                'user_id' => $customer->id,
                'subtotal' => 1000.00,
                'discount' => 0.00,
                'gst_amt' => 50.00,
                'ship_charge' => 50.00,
                'total' => 1100.00,
                'coupon_id' => null,
                'ship_id' => $standardShipping->id,
                'status' => 'processing',
                'pay_status' => 'paid',
            ]
        );

        if (! OrderItem::where('order_id', $order->id)->where('product_id', $package->id)->exists()) {
            OrderItem::create(['order_id' => $order->id, 'product_id' => $package->id, 'qty' => 1, 'price' => 1000.00, 'gst_pct' => 5]);
        }

        if (! \App\Models\OrderStatusHistory::where('order_id', $order->id)->exists()) {
            \App\Models\OrderStatusHistory::create(['order_id' => $order->id, 'status' => 'pending', 'note' => 'Order placed']);
            \App\Models\OrderStatusHistory::create(['order_id' => $order->id, 'status' => 'processing', 'note' => 'Order confirmed and processing']);
        }

        Payment::firstOrCreate(
            ['order_id' => $order->id],
            [
                'amount' => 1100.00,
                'method' => 'cod',
                'status' => 'paid',
                'txn_id' => 'TXN-INIT-10001',
            ]
        );

        Invoice::firstOrCreate(
            ['inv_num' => 'INV-10001'],
            [
                'order_id' => $order->id,
                'inv_data' => null,
            ]
        );

        Inquiry::firstOrCreate(
            ['email' => 'rahul@example.com'],
            [
                'name' => 'Rahul Sharma',
                'phone' => '9988776655',
                'product_id' => $package->id,
                'msg' => 'Inquiry regarding Direct Selling registration and membership.',
                'status' => 'pending',
            ]
        );

        Page::firstOrCreate(
            ['slug' => 'about-us'],
            [
                'title' => 'About Us',
                'content' => 'KharchDaan.Com — "तेरा तुझको अर्पण". Leading Direct Selling & 100% Cashback platform.',
                'status' => 'active',
            ]
        );

        Page::firstOrCreate(
            ['slug' => 'privacy-policy'],
            [
                'title' => 'Privacy Policy',
                'content' => 'Your privacy is important to us. We protect your personal information.',
                'status' => 'active',
            ]
        );

        $this->call(MlmSampleDataSeeder::class);
    }
}
