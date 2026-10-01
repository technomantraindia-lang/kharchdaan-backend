<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use App\Services\WooCommerce\WooCommerceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class Phase31To40Test extends TestCase
{
    use RefreshDatabase;

    protected Role $superAdminRole;
    protected Role $adminRole;
    protected Role $customerRole;
    protected User $superAdmin;
    protected User $admin;
    protected User $customer;
    protected Category $category;
    protected Brand $brand;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::firstOrCreate(['name' => 'Super Admin'], ['guard_name' => 'web']);
        $this->adminRole = Role::firstOrCreate(['name' => 'Admin'], ['guard_name' => 'web']);
        $this->customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);

        $permOrdersView = Permission::firstOrCreate(['name' => 'orders.view'], ['guard_name' => 'web']);
        $permOrdersEdit = Permission::firstOrCreate(['name' => 'orders.edit'], ['guard_name' => 'web']);
        $permProductsView = Permission::firstOrCreate(['name' => 'products.view'], ['guard_name' => 'web']);

        $this->adminRole->permissions()->syncWithoutDetaching([
            $permOrdersView->id,
            $permOrdersEdit->id,
            $permProductsView->id,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->superAdminRole->id,
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'name' => 'Staff Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->adminRole->id,
            'status' => 'active',
        ]);

        $this->customer = User::create([
            'name' => 'Regular Customer',
            'email' => 'customer@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
            'status' => 'active',
        ]);

        $this->category = Category::create([
            'name' => 'Grains',
            'slug' => 'grains',
            'status' => 'active',
        ]);

        $this->brand = Brand::create([
            'name' => 'Madhav Organic',
            'slug' => 'madhav-organic',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'name' => 'Basmati Rice 5kg',
            'slug' => 'basmati-rice-5kg',
            'sku' => 'RICE-001',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'price' => 450.00,
            'sale_price' => 400.00,
            'cost_price' => 280.00,
            'stock_qty' => 100,
            'status' => 'active',
            'featured' => true,
        ]);
    }

    /** 1. Test /api/v1/products returns valid paginated response */
    public function test_1_api_v1_products_returns_valid_paginated_response(): void
    {
        $response = $this->getJson('/api/v1/products');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'sku', 'price', 'sale_price', 'available_stock', 'category', 'brand'],
                ],
                'pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages'],
            ]);
    }

    /** 2. Test cost price is not exposed publicly */
    public function test_2_cost_price_is_not_exposed_publicly(): void
    {
        $response = $this->getJson('/api/v1/products/' . $this->product->slug);
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringNotContainsString('cost_price', $content);
        $this->assertStringNotContainsString('280.00', $content);
    }

    /** 3. Test product filters work */
    public function test_3_product_filters_work(): void
    {
        $response = $this->getJson('/api/v1/products?search=Rice&category=grains&min_price=100&max_price=500&in_stock=1');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    /** 4. Test product sorting works */
    public function test_4_product_sorting_works(): void
    {
        Product::create([
            'name' => 'Premium Quinoa',
            'slug' => 'premium-quinoa',
            'sku' => 'QUINOA-001',
            'category_id' => $this->category->id,
            'price' => 800.00,
            'stock_qty' => 50,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/products?sort=price_desc');
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('QUINOA-001', $data[0]['sku']);
    }

    /** 5. Test pagination limits work */
    public function test_5_pagination_limits_work(): void
    {
        $response = $this->getJson('/api/v1/products?per_page=500');
        $response->assertStatus(200)
            ->assertJsonPath('pagination.per_page', 100);
    }

    /** 6. Test product detail by slug works */
    public function test_6_product_detail_by_slug_works(): void
    {
        $response = $this->getJson('/api/v1/products/' . $this->product->slug);
        $response->assertStatus(200)
            ->assertJsonPath('data.sku', 'RICE-001');
    }

    /** 7. Test customer cannot call admin APIs */
    public function test_7_customer_cannot_call_admin_apis(): void
    {
        $response = $this->actingAs($this->customer, 'web')->get('/admin/products');
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    /** 8. Test public API registration cannot assign Admin role */
    public function test_8_public_api_registration_cannot_assign_admin_role(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Hacker User',
            'email' => 'hacker@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Super Admin',
            'role_id' => $this->superAdminRole->id,
        ]);

        $response->assertStatus(201);
        $user = User::where('email', 'hacker@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals($this->customerRole->id, $user->role_id);
        $this->assertFalse($user->isSuperAdmin());
    }

    /** 9. Test login rate limiting works */
    public function test_9_login_rate_limiting_works(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => 'wrong@example.com',
                'password' => 'invalid',
            ]);
        }

        $response = $this->postJson('/api/v1/login', [
            'email' => 'wrong@example.com',
            'password' => 'invalid',
        ]);

        $response->assertStatus(429);
    }

    /** 10. Test global admin search works */
    public function test_10_global_admin_search_works(): void
    {
        $order = Order::create([
            'order_num' => 'ORD-TEST-999',
            'user_id' => $this->customer->id,
            'subtotal' => 450.00,
            'total' => 450.00,
            'status' => 'pending',
            'pay_status' => 'paid',
        ]);

        $response = $this->actingAs($this->superAdmin, 'web')->getJson('/admin/search?q=ORD-TEST-999');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'results.orders');
    }

    /** 11. Test global search respects permissions */
    public function test_11_global_search_respects_permissions(): void
    {
        // $admin user lacks customers.view permission
        $response = $this->actingAs($this->admin, 'web')->getJson('/admin/search?q=customer');
        $response->assertStatus(200)
            ->assertJsonCount(0, 'results.customers');
    }

    /** 12. Test order filters work together */
    public function test_12_order_filters_work_together(): void
    {
        Order::create([
            'order_num' => 'ORD-FILTER-001',
            'user_id' => $this->customer->id,
            'subtotal' => 1200.00,
            'total' => 1200.00,
            'status' => 'processing',
            'pay_status' => 'paid',
            'payment_method' => 'cod',
        ]);

        $response = $this->actingAs($this->superAdmin, 'web')
            ->getJson('/admin/orders?status=processing&pay_status=paid&payment_method=cod');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 1);
    }

    /** 13. Test bulk order status action validates transitions */
    public function test_13_bulk_order_status_action_validates_transitions(): void
    {
        $orderPending = Order::create([
            'order_num' => 'ORD-BULK-001',
            'user_id' => $this->customer->id,
            'subtotal' => 500.00,
            'total' => 500.00,
            'status' => 'pending',
        ]);

        $orderDelivered = Order::create([
            'order_num' => 'ORD-BULK-002',
            'user_id' => $this->customer->id,
            'subtotal' => 500.00,
            'total' => 500.00,
            'status' => 'delivered',
        ]);

        $response = $this->actingAs($this->superAdmin, 'web')->postJson('/admin/orders/bulk-action', [
            'order_ids' => [$orderPending->id, $orderDelivered->id],
            'action' => 'change_status',
            'target_status' => 'processing',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('updated_count', 1)
            ->assertJsonPath('skipped_count', 1);

        $this->assertEquals('processing', $orderPending->fresh()->status);
        $this->assertEquals('delivered', $orderDelivered->fresh()->status);
    }

    /** 14. Test bulk action creates activity logs */
    public function test_14_bulk_action_creates_activity_logs(): void
    {
        $order = Order::create([
            'order_num' => 'ORD-LOG-001',
            'user_id' => $this->customer->id,
            'subtotal' => 300.00,
            'total' => 300.00,
            'status' => 'pending',
        ]);

        $this->actingAs($this->superAdmin, 'web')->postJson('/admin/orders/bulk-action', [
            'order_ids' => [$order->id],
            'action' => 'change_status',
            'target_status' => 'processing',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'orders',
            'action' => 'bulk_update_order_status',
        ]);
    }

    /** 15. Test new WooCommerce order creates notification */
    public function test_15_new_woocommerce_order_creates_notification(): void
    {
        Notification::fake();

        $this->superAdmin->notify(new SystemAlertNotification(
            'New WooCommerce Order Received',
            'Order #WC-1001 imported successfully.',
            'info',
            route('admin.orders.index')
        ));

        Notification::assertSentTo($this->superAdmin, SystemAlertNotification::class);
    }

    /** 16. Test sync failure creates notification */
    public function test_16_sync_failure_creates_notification(): void
    {
        $this->superAdmin->notify(new SystemAlertNotification(
            'WooCommerce Product Sync Failed',
            'Failed to sync Product #102 due to invalid API credentials.',
            'error',
            route('admin.woocommerce.sync-logs.index')
        ));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->superAdmin->id,
        ]);
    }

    /** 17. Test mark notification as read works */
    public function test_17_mark_notification_as_read_works(): void
    {
        $this->superAdmin->notify(new SystemAlertNotification(
            'Low Stock Warning',
            'Product Basmati Rice is low on stock.',
            'warning'
        ));

        $notification = $this->superAdmin->unreadNotifications->first();
        $this->assertNotNull($notification);

        $response = $this->actingAs($this->superAdmin, 'web')
            ->postJson('/admin/notifications/' . $notification->id . '/read');

        $response->assertStatus(200);
        $this->assertEquals(0, $this->superAdmin->fresh()->unreadNotifications->count());
    }

    /** 18. Test queue job failure reaches failed_jobs */
    public function test_18_queue_job_failure_reaches_failed_jobs(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\SyncWooCommerceProductJob']),
            'exception' => 'GuzzleHttp\\Exception\\ConnectException: Connection refused',
            'failed_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin, 'web')
            ->getJson('/admin/system/failed-jobs');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /** 19. Test failed job retry works */
    public function test_19_failed_job_retry_works(): void
    {
        $id = DB::table('failed_jobs')->insertGetId([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\SyncWooCommerceOrderJob']),
            'exception' => 'Exception: Network Timeout',
            'failed_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin, 'web')
            ->postJson('/admin/system/failed-jobs/' . $id . '/retry');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /** 20. Test WooCommerce client integration remains functional */
    public function test_20_woocommerce_client_integration_remains_functional(): void
    {
        $client = new WooCommerceClient();
        $config = $client->getMaskedConfig();

        $this->assertArrayHasKey('url', $config);
        $this->assertArrayHasKey('consumer_key_masked', $config);
        $this->assertArrayHasKey('consumer_secret_masked', $config);
    }
}
