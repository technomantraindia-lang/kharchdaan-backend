<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\WooCommerce\WooCommerceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Tests\TestCase;

class Phase41To48Test extends TestCase
{
    use RefreshDatabase;

    protected Role $superAdminRole;
    protected Role $adminRole;
    protected Role $customerRole;
    protected User $superAdmin;
    protected User $admin;
    protected User $customer;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::firstOrCreate(['name' => 'Super Admin'], ['guard_name' => 'web']);
        $this->adminRole = Role::firstOrCreate(['name' => 'Admin'], ['guard_name' => 'web']);
        $this->customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);

        $permReportsView = Permission::firstOrCreate(['name' => 'reports.view'], ['guard_name' => 'web']);
        $this->adminRole->permissions()->syncWithoutDetaching([$permReportsView->id]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->superAdminRole->id,
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'name' => 'Staff Reports Admin',
            'email' => 'reports@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->adminRole->id,
            'status' => 'active',
        ]);

        $this->customer = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->customerRole->id,
            'status' => 'active',
        ]);

        $this->category = Category::create([
            'name' => 'Spices',
            'slug' => 'spices',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'name' => 'Organic Turmeric 500g',
            'slug' => 'organic-turmeric-500g',
            'sku' => 'SPICE-001',
            'category_id' => $this->category->id,
            'price' => 200.00,
            'sale_price' => 180.00,
            'cost_price' => 100.00,
            'stock_qty' => 50,
            'status' => 'active',
            'hsn_code' => '0910',
        ]);
    }

    /** 1. Test report date range filtering works */
    public function test_1_report_date_range_filtering_works(): void
    {
        $response = $this->actingAs($this->superAdmin, 'web')
            ->getJson('/admin/reports?date_preset=last_7_days');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('range.preset', 'last_7_days');
    }

    /** 2. Test report CSV export works */
    public function test_2_report_csv_export_works(): void
    {
        $response = $this->actingAs($this->superAdmin, 'web')
            ->get('/admin/reports/export/products');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('SPICE-001', $content);
    }

    /** 3. Test sensitive credentials are absent in customer CSV export */
    public function test_3_sensitive_credentials_absent_in_customer_csv_export(): void
    {
        $response = $this->actingAs($this->superAdmin, 'web')
            ->get('/admin/reports/export/customers');

        $response->assertStatus(200);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('$2y$', $content);
    }

    /** 4. Test profit report calculation */
    public function test_4_profit_report_calculation(): void
    {
        $order = Order::create([
            'order_num' => 'ORD-PROFIT-001',
            'user_id' => $this->customer->id,
            'subtotal' => 180.00,
            'total' => 180.00,
            'status' => 'delivered',
            'pay_status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'sku' => $this->product->sku,
            'qty' => 1,
            'price' => 180.00,
            'line_total' => 180.00,
        ]);

        $response = $this->actingAs($this->superAdmin, 'web')
            ->getJson('/admin/reports/profit?date_preset=this_month');

        $response->assertStatus(200)
            ->assertJsonPath('data.revenue', 180)
            ->assertJsonPath('data.cogs', 100)
            ->assertJsonPath('data.gross_profit', 80);
    }

    /** 5. Test GST report calculation */
    public function test_5_gst_report_calculation(): void
    {
        $order = Order::create([
            'order_num' => 'ORD-GST-001',
            'user_id' => $this->customer->id,
            'subtotal' => 100.00,
            'gst_amt' => 18.00,
            'total' => 118.00,
            'status' => 'delivered',
            'pay_status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'sku' => $this->product->sku,
            'qty' => 1,
            'price' => 118.00,
            'line_total' => 118.00,
        ]);

        $response = $this->actingAs($this->superAdmin, 'web')
            ->getJson('/admin/reports/gst?date_preset=this_month');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_gst', 18)
            ->assertJsonPath('data.total_cgst', 9)
            ->assertJsonPath('data.total_sgst', 9);
    }

    /** 6. Test system health endpoint restricted to Super Admin */
    public function test_6_system_health_endpoint_restricted_to_super_admin(): void
    {
        $responseCustomer = $this->actingAs($this->customer, 'web')->getJson('/admin/system-health');
        $this->assertTrue(in_array($responseCustomer->status(), [302, 403]));

        $responseSuper = $this->actingAs($this->superAdmin, 'web')->getJson('/admin/system-health');
        $responseSuper->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['health' => ['laravel_version', 'php_version', 'database_status']]);
    }

    /** 7. Test backup creation, listing, downloading, and deletion */
    public function test_7_backup_creation_listing_download_and_deletion(): void
    {
        // Create backup
        $createRes = $this->actingAs($this->superAdmin, 'web')->postJson('/admin/system/backups');
        $createRes->assertStatus(200)->assertJsonPath('success', true);
        $filename = $createRes->json('filename');

        // List backups
        $listRes = $this->actingAs($this->superAdmin, 'web')->getJson('/admin/system/backups');
        $listRes->assertStatus(200)->assertJsonPath('success', true);

        // Download backup
        $dlRes = $this->actingAs($this->superAdmin, 'web')->get('/admin/system/backups/' . $filename . '/download');
        $dlRes->assertStatus(200);

        // Delete backup
        $delRes = $this->actingAs($this->superAdmin, 'web')->deleteJson('/admin/system/backups/' . $filename);
        $delRes->assertStatus(200)->assertJsonPath('success', true);
    }

    /** 8. Regression: Inventory service prevents negative stock */
    public function test_8_inventory_service_prevents_negative_stock(): void
    {
        $service = new InventoryService();
        $service->decreaseStock($this->product, 20, 'Manual adjustment');
        $this->assertEquals(30, $this->product->fresh()->stock_qty);

        $this->expectException(InvalidArgumentException::class);
        $service->decreaseStock($this->product, 50, 'Excessive reduction');
    }

    /** 9. Regression: Public Product API masks cost price */
    public function test_9_public_product_api_masks_cost_price(): void
    {
        $response = $this->getJson('/api/v1/products/' . $this->product->slug);
        $response->assertStatus(200);
        $this->assertStringNotContainsString('cost_price', $response->getContent());
        $this->assertStringNotContainsString('100.00', $response->getContent());
    }

    /** 10. Regression: WooCommerce client integration mock */
    public function test_10_woocommerce_client_mock(): void
    {
        $client = new WooCommerceClient();
        $config = $client->getMaskedConfig();

        $this->assertArrayHasKey('url', $config);
        $this->assertArrayHasKey('consumer_key_masked', $config);
        $this->assertArrayHasKey('consumer_secret_masked', $config);
    }
}
