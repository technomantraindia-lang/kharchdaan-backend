<?php

namespace App\Services\WooCommerce;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\WooCommerceSyncLog;
use App\Services\ActivityLogService;
use Throwable;

class ProductSyncService
{
    protected WooCommerceClient $client;

    public function __construct(WooCommerceClient $client)
    {
        $this->client = $client;
    }

    public function syncProduct(Product $product): bool
    {
        if (! $this->client->isConfigured()) {
            $product->update(['woocommerce_sync_status' => 'failed']);
            WooCommerceSyncLog::log(
                'product',
                $product->id,
                $product->woocommerce_id,
                'laravel_to_woocommerce',
                'sync',
                'failed',
                null,
                null,
                'WooCommerce API credentials are not configured in environment (.env).'
            );
            return false;
        }

        $product->update(['woocommerce_sync_status' => 'syncing']);

        try {
            $payload = $this->buildProductPayload($product);

            if ($product->woocommerce_id) {
                // Update existing WooCommerce product
                $response = $this->client->put("products/{$product->woocommerce_id}", $payload);
                $action = 'update';
            } else {
                // Create new WooCommerce product
                $response = $this->client->post('products', $payload);
                $action = 'create';
            }

            $wcId = $response['id'] ?? $product->woocommerce_id;

            $product->update([
                'woocommerce_id' => $wcId,
                'woocommerce_sync_status' => 'synced',
                'woocommerce_synced_at' => now(),
            ]);

            // Sync variations if product is variable
            if ($product->variations()->exists()) {
                foreach ($product->variations as $variation) {
                    $this->syncVariation($variation);
                }
            }

            WooCommerceSyncLog::log(
                'product',
                $product->id,
                $wcId,
                'laravel_to_woocommerce',
                $action,
                'success',
                $payload,
                $response
            );

            ActivityLogService::log(
                'woocommerce_sync',
                'products',
                "Synced product #{$product->id} ({$product->name}) to WooCommerce (WC ID: {$wcId})"
            );

            return true;
        } catch (Throwable $e) {
            $product->update(['woocommerce_sync_status' => 'failed']);

            WooCommerceSyncLog::log(
                'product',
                $product->id,
                $product->woocommerce_id,
                'laravel_to_woocommerce',
                'sync',
                'failed',
                $payload ?? null,
                null,
                $e->getMessage()
            );

            ActivityLogService::log(
                'woocommerce_sync_error',
                'products',
                "Failed syncing product #{$product->id} to WooCommerce: {$e->getMessage()}"
            );

            return false;
        }
    }

    public function syncSelectedProducts(array $ids): array
    {
        $dispatched = 0;
        $products = Product::whereIn('id', $ids)->get();

        foreach ($products as $product) {
            \App\Jobs\SyncProductToWooCommerce::dispatch($product->id);
            $dispatched++;
        }

        return [
            'success' => true,
            'message' => "Dispatched {$dispatched} product synchronization queue jobs.",
            'count' => $dispatched,
        ];
    }

    public function syncAllProducts(): array
    {
        $dispatched = 0;
        Product::chunk(100, function ($products) use (&$dispatched) {
            foreach ($products as $product) {
                \App\Jobs\SyncProductToWooCommerce::dispatch($product->id);
                $dispatched++;
            }
        });

        return [
            'success' => true,
            'message' => "Dispatched {$dispatched} product synchronization queue jobs.",
            'count' => $dispatched,
        ];
    }

    public function syncVariation(ProductVariation $variation): bool
    {
        if (! $this->client->isConfigured() || ! $variation->product || ! $variation->product->woocommerce_id) {
            $variation->update(['woocommerce_sync_status' => 'failed']);
            return false;
        }

        $variation->update(['woocommerce_sync_status' => 'syncing']);

        try {
            $attributes = [];
            if ($variation->multiAttributeValues && $variation->multiAttributeValues->count()) {
                foreach ($variation->multiAttributeValues as $vav) {
                    if ($vav->attribute && $vav->attributeValue) {
                        $attributes[] = [
                            'name' => $vav->attribute->name,
                            'option' => $vav->attributeValue->value,
                        ];
                    }
                }
            } elseif ($variation->attribute) {
                $attributes[] = [
                    'name' => $variation->attribute->name,
                    'option' => (string) $variation->attr_val,
                ];
            }

            // CRITICAL: NEVER send cost_price or internal profit data in payload
            $payload = [
                'sku' => (string) $variation->sku,
                'regular_price' => number_format((float) $variation->price, 2, '.', ''),
                'sale_price' => $variation->sale_price ? number_format((float) $variation->sale_price, 2, '.', '') : '',
                'manage_stock' => true,
                'stock_quantity' => (int) $variation->available_stock,
                'status' => $variation->status === 'active' ? 'publish' : 'private',
                'attributes' => $attributes,
                'weight' => $variation->weight ? (string) $variation->weight : '',
                'image' => $variation->image ? ['src' => asset('storage/' . $variation->image)] : null,
            ];

            $wcProductId = $variation->product->woocommerce_id;

            if ($variation->woocommerce_id) {
                $response = $this->client->put("products/{$wcProductId}/variations/{$variation->woocommerce_id}", $payload);
            } else {
                $response = $this->client->post("products/{$wcProductId}/variations", $payload);
            }

            $wcVarId = $response['id'] ?? $variation->woocommerce_id;

            $variation->update([
                'woocommerce_id' => $wcVarId,
                'woocommerce_sync_status' => 'synced',
                'woocommerce_synced_at' => now(),
            ]);

            return true;
        } catch (Throwable $e) {
            $variation->update(['woocommerce_sync_status' => 'failed']);

            WooCommerceSyncLog::log(
                'variation',
                $variation->id,
                $variation->woocommerce_id,
                'laravel_to_woocommerce',
                'sync',
                'failed',
                $payload ?? null,
                null,
                $e->getMessage()
            );

            return false;
        }
    }

    public function updateWooCommerceStock(Product|ProductVariation $item): bool
    {
        if (! $this->client->isConfigured() || ! $item->woocommerce_id) {
            return false;
        }

        try {
            if ($item instanceof Product) {
                $payload = [
                    'manage_stock' => true,
                    'stock_quantity' => (int) $item->available_stock,
                ];
                $response = $this->client->put("products/{$item->woocommerce_id}", $payload);
                $item->update(['woocommerce_synced_at' => now(), 'woocommerce_sync_status' => 'synced']);
            } else {
                $wcProductId = $item->product?->woocommerce_id;
                if (! $wcProductId) return false;

                $payload = [
                    'manage_stock' => true,
                    'stock_quantity' => (int) $item->stock_qty,
                ];
                $response = $this->client->put("products/{$wcProductId}/variations/{$item->woocommerce_id}", $payload);
                $item->update(['woocommerce_synced_at' => now(), 'woocommerce_sync_status' => 'synced']);
            }

            WooCommerceSyncLog::log(
                'inventory',
                $item->id,
                $item->woocommerce_id,
                'laravel_to_woocommerce',
                'update_stock',
                'success',
                $payload,
                $response
            );

            return true;
        } catch (Throwable $e) {
            WooCommerceSyncLog::log(
                'inventory',
                $item->id,
                $item->woocommerce_id,
                'laravel_to_woocommerce',
                'update_stock',
                'failed',
                $payload ?? null,
                null,
                $e->getMessage()
            );

            return false;
        }
    }

    protected function buildProductPayload(Product $product): array
    {
        $images = [];

        if ($product->image) {
            $images[] = ['src' => asset('storage/' . $product->image)];
        }

        if ($product->images) {
            foreach ($product->images as $img) {
                $images[] = ['src' => asset('storage/' . $img->image_path)];
            }
        }

        $categories = [];
        if ($product->category) {
            $categories[] = ['name' => $product->category->name];
        }

        $isVariable = $product->variations()->exists();
        $attributes = [];

        if ($isVariable) {
            $attrMap = [];
            foreach ($product->variations as $var) {
                if ($var->attribute && $var->attr_val) {
                    $attrName = $var->attribute->name;
                    $attrMap[$attrName][] = (string) $var->attr_val;
                }
            }
            foreach ($attrMap as $name => $options) {
                $attributes[] = [
                    'name' => $name,
                    'options' => array_values(array_unique($options)),
                    'visible' => true,
                    'variation' => true,
                ];
            }
        }

        // CRITICAL RULE: NEVER include cost_price, internal notes, or profit fields in payload
        $payload = [
            'name' => $product->name,
            'slug' => $product->slug,
            'type' => $isVariable ? 'variable' : 'simple',
            'status' => $product->status === 'active' ? 'publish' : 'draft',
            'featured' => (bool) $product->featured,
            'short_description' => (string) ($product->short_desc ?? ''),
            'description' => (string) ($product->description ?? ''),
            'sku' => (string) $product->sku,
            'categories' => $categories,
            'images' => $images,
        ];

        if ($isVariable) {
            $payload['attributes'] = $attributes;
        } else {
            $payload['regular_price'] = number_format((float) $product->price, 2, '.', '');
            $payload['sale_price'] = $product->sale_price ? number_format((float) $product->sale_price, 2, '.', '') : '';
            $payload['manage_stock'] = true;
            $payload['stock_quantity'] = (int) $product->available_stock;
        }

        return $payload;
    }
}
