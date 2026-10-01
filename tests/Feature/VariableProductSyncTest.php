<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariation;
use App\Services\WooCommerce\ProductSyncService;
use App\Services\WooCommerce\WooCommerceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class VariableProductSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_variable_product_builds_variable_payload_and_syncs_variations(): void
    {
        $category = Category::first();
        $product = Product::create([
            'name' => 'Variable T-Shirt',
            'slug' => 'variable-t-shirt',
            'sku' => 'TSHIRT-VAR',
            'category_id' => $category->id,
            'price' => 500,
            'stock_qty' => 0,
            'status' => 'active',
        ]);

        $attribute = ProductAttribute::create([
            'name' => 'Size',
            'code' => 'size',
            'status' => 'active',
        ]);

        $v1 = ProductVariation::create([
            'product_id' => $product->id,
            'attr_id' => $attribute->id,
            'attr_val' => 'M',
            'sku' => 'TSHIRT-VAR-M',
            'price' => 500,
            'stock_qty' => 10,
            'status' => 'active',
        ]);

        $v2 = ProductVariation::create([
            'product_id' => $product->id,
            'attr_id' => $attribute->id,
            'attr_val' => 'L',
            'sku' => 'TSHIRT-VAR-L',
            'price' => 550,
            'stock_qty' => 15,
            'status' => 'active',
        ]);

        $mockClient = Mockery::mock(WooCommerceClient::class);
        $mockClient->shouldReceive('isConfigured')->andReturn(true);

        // Expect parent variable product creation
        $mockClient->shouldReceive('post')
            ->with('products', Mockery::on(function ($payload) {
                return $payload['type'] === 'variable'
                    && isset($payload['attributes'])
                    && count($payload['attributes']) > 0;
            }))
            ->once()
            ->andReturn(['id' => 888]);

        // Expect two variation creations
        $mockClient->shouldReceive('post')
            ->with('products/888/variations', Mockery::on(function ($payload) {
                return in_array($payload['sku'], ['TSHIRT-VAR-M', 'TSHIRT-VAR-L'], true);
            }))
            ->twice()
            ->andReturn(['id' => 991], ['id' => 992]);

        $syncService = new ProductSyncService($mockClient);
        $result = $syncService->syncProduct($product);

        $this->assertTrue($result);
        $product->refresh();
        $this->assertEquals(888, $product->woocommerce_id);
    }
}
