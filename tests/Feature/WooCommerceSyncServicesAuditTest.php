<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Services\WooCommerce\InventorySyncService;
use App\Services\WooCommerce\ProductSyncService;
use App\Services\WooCommerce\WooCommerceClient;
use App\Services\WooCommerce\WooCommerceWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use Tests\TestCase;

class WooCommerceSyncServicesAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Sync Test Category',
            'slug' => 'sync-test-category',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'name' => 'Master Product Test',
            'slug' => 'master-product-test',
            'sku' => 'MASTER-SKU-001',
            'category_id' => $this->category->id,
            'price' => 500.00,
            'cost_price' => 250.00,
            'stock_qty' => 100,
            'reserved_stock' => 10,
            'status' => 'active',
        ]);
    }

    /** 1. Test ProductSyncService payload never exposes cost_price */
    public function test_1_product_sync_payload_masks_cost_price(): void
    {
        $client = new WooCommerceClient();
        $syncService = new ProductSyncService($client);

        // Reflection to test buildProductPayload
        $ref = new \ReflectionClass($syncService);
        $method = $ref->getMethod('buildProductPayload');
        $method->setAccessible(true);

        $payload = $method->invoke($syncService, $this->product);

        $this->assertArrayHasKey('sku', $payload);
        $this->assertArrayNotHasKey('cost_price', $payload);
        $this->assertArrayNotHasKey('profit', $payload);
        $this->assertEquals(90, $payload['stock_quantity']); // available_stock = 100 - 10
    }

    /** 2. Test WooCommerce product.updated webhook does not overwrite master product */
    public function test_2_product_updated_webhook_preserves_master_product(): void
    {
        $webhookService = new WooCommerceWebhookService();

        config(['woocommerce.webhook_secret' => 'test_secret']);
        $rawPayload = json_encode([
            'id' => 999111,
            'name' => 'Changed Name on WC',
            'price' => '1.00',
        ]);

        $signature = base64_encode(hash_hmac('sha256', $rawPayload, 'test_secret', true));

        $request = Request::create(
            '/api/webhooks/woocommerce',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_X_WC_WEBHOOK_SIGNATURE' => $signature,
                'HTTP_X_WC_WEBHOOK_TOPIC' => 'product.updated',
                'HTTP_X_WC_WEBHOOK_DELIVERY_ID' => 'deliv_' . uniqid(),
            ],
            $rawPayload
        );

        $result = $webhookService->processRequest($request);

        $this->assertEquals(200, $result['status']);
        // Verify local product remains unchanged
        $this->assertEquals('Master Product Test', $this->product->fresh()->name);
        $this->assertEquals(500.00, $this->product->fresh()->price);
    }

    /** 3. Test WooCommerceClient throws RuntimeException on HTTP error */
    public function test_3_client_throws_runtime_exception_on_http_error(): void
    {
        Http::fake([
            '*/wp-json/wc/v3/products/999999' => Http::response([
                'message' => 'Invalid product ID.',
            ], 404),
        ]);

        config([
            'woocommerce.url' => 'https://example-wc.com',
            'woocommerce.consumer_key' => 'ck_test',
            'woocommerce.consumer_secret' => 'cs_test',
        ]);

        $client = new WooCommerceClient();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('WooCommerce API Error (404)');

        $client->get('products/999999');
    }
}
