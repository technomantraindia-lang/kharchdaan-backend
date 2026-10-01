<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\InventoryService;
use App\Services\WooCommerce\WooCommerceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PhasesValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Role $superAdminRole;

    protected Role $adminRole;

    protected Role $customerRole;

    protected User $superAdmin;

    protected User $admin;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::firstOrCreate(['name' => 'Super Admin'], ['guard_name' => 'web']);
        $this->adminRole = Role::firstOrCreate(['name' => 'Admin'], ['guard_name' => 'web']);
        $this->customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);

        $permView = Permission::firstOrCreate(['name' => 'products.view'], ['guard_name' => 'web']);
        $permManage = Permission::firstOrCreate(['name' => 'products.create'], ['guard_name' => 'web']);
        $permUsersView = Permission::firstOrCreate(['name' => 'users.view'], ['guard_name' => 'web']);

        $this->adminRole->permissions()->sync([$permView->id, $permManage->id, $permUsersView->id]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $this->superAdminRole->id,
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'name' => 'Standard Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $this->adminRole->id,
            'status' => 'active',
        ]);

        $this->customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer@test.com',
            'password' => bcrypt('password'),
            'role_id' => $this->customerRole->id,
            'status' => 'active',
        ]);
    }

    public function test_phase_1_accidental_root_files_cleaned()
    {
        $accidentalFiles = [
            'Main Admin,',
            'Product created successfully.,',
            'Products could not be created.,',
            'All products retrieved successfully.,',
            'admin@example.com],',
            '9999999999,',
            'true,',
            'false,',
            '[required,',
            '[nullable,',
            '1,',
            ',',
        ];

        foreach ($accidentalFiles as $file) {
            $path = base_path($file);
            $this->assertFileDoesNotExist($path, "Accidental file {$file} should be removed.");
        }
    }

    public function test_phase_2_role_architecture_and_user_methods()
    {
        $this->assertTrue($this->superAdmin->isSuperAdmin());
        $this->assertTrue($this->superAdmin->isAdmin());
        $this->assertTrue($this->superAdmin->hasRole('Super Admin'));
        $this->assertTrue($this->superAdmin->hasPermission('any.random.permission'));

        $this->assertFalse($this->admin->isSuperAdmin());
        $this->assertTrue($this->admin->isAdmin());
        $this->assertTrue($this->admin->hasRole('Admin'));
        $this->assertTrue($this->admin->hasPermission('products.view'));
        $this->assertFalse($this->admin->hasPermission('woocommerce.manage'));

        $this->assertFalse($this->customer->isAdmin());
        $this->assertTrue($this->customer->isCustomer());
    }

    public function test_phase_3_customer_role_resolution()
    {
        $this->assertEquals('Customer', $this->customer->role?->name);

        $response = $this->actingAs($this->customer)->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    public function test_phase_4_permission_middleware_protection()
    {
        $this->actingAs($this->admin);

        // Admin has products.view, so /admin/products should succeed
        $responseView = $this->get(route('admin.products.index'));
        $responseView->assertStatus(200);

        // Admin does not have woocommerce.manage, so test-connection should return 403
        $responseForbidden = $this->post(route('admin.woocommerce.testConnection'));
        $responseForbidden->assertStatus(403);
    }

    public function test_phase_5_admin_user_management()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'New Staff',
            'email' => 'staff@test.com',
            'phone' => '1234567890',
            'role_id' => $this->adminRole->id,
            'status' => 'active',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'staff@test.com']);
    }

    public function test_phase_6_activity_logs()
    {
        ActivityLogService::log('create', 'products', 'Created test product');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'create',
            'module' => 'products',
            'description' => 'Created test product',
        ]);
    }

    public function test_phase_7_brand_management()
    {
        $brand = Brand::create([
            'name' => 'Test Brand',
            'slug' => 'test-brand',
            'description' => 'Brand description',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('brands', ['slug' => 'test-brand']);
    }

    public function test_phase_8_product_internal_fields()
    {
        $category = \App\Models\Category::create(['name' => 'Cat1', 'slug' => 'cat1', 'status' => 'active']);

        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST001',
            'hsn_code' => '1006',
            'category_id' => $category->id,
            'price' => 100.00,
            'cost_price' => 60.00,
            'stock_qty' => 50,
            'reserved_stock' => 10,
            'status' => 'active',
        ]);

        $this->assertEquals(40, $product->available_stock);
        $this->assertArrayNotHasKey('cost_price', $product->toArray(), 'Cost price should be hidden in public serialization');
    }

    public function test_phase_9_inventory_service_prevents_negative_stock()
    {
        $category = \App\Models\Category::create(['name' => 'Cat2', 'slug' => 'cat2', 'status' => 'active']);
        $product = Product::create([
            'name' => 'Stock Product',
            'slug' => 'stock-product',
            'sku' => 'STOCK001',
            'category_id' => $category->id,
            'price' => 50.00,
            'stock_qty' => 10,
            'status' => 'active',
        ]);

        $service = new InventoryService();
        $service->decreaseStock($product, 5, 'Sale');
        $this->assertEquals(5, $product->fresh()->stock_qty);

        $this->expectException(InvalidArgumentException::class);
        $service->decreaseStock($product, 20, 'Excessive Sale');
    }

    public function test_phase_10_woocommerce_client()
    {
        $client = new WooCommerceClient();
        $config = $client->getMaskedConfig();

        $this->assertArrayHasKey('url', $config);
        $this->assertArrayHasKey('consumer_key_masked', $config);
        $this->assertArrayHasKey('consumer_secret_masked', $config);

        $testResult = $client->testConnection();
        $this->assertIsArray($testResult);
        $this->assertArrayHasKey('success', $testResult);
    }
}
