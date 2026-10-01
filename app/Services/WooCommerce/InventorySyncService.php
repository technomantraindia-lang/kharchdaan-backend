<?php

namespace App\Services\WooCommerce;

use App\Jobs\SyncProductInventoryToWooCommerce;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\WooCommerceSyncLog;
use App\Services\ActivityLogService;
use Throwable;

class InventorySyncService
{
    protected WooCommerceClient $client;

    public function __construct(WooCommerceClient $client)
    {
        $this->client = $client;
    }

    public function dispatchInventorySync(Product|ProductVariation $item): void
    {
        $type = $item instanceof Product ? 'product' : 'variation';
        SyncProductInventoryToWooCommerce::dispatch($type, $item->id);
    }

    public function syncInventory(Product|ProductVariation $item): bool
    {
        if (! $this->client->isConfigured() || ! $item->woocommerce_id) {
            return false;
        }

        try {
            if ($item instanceof Product) {
                $qty = (int) $item->available_stock;
                $payload = [
                    'manage_stock' => true,
                    'stock_quantity' => max($qty, 0),
                ];
                $response = $this->client->put("products/{$item->woocommerce_id}", $payload);

                $item->update([
                    'woocommerce_synced_at' => now(),
                    'woocommerce_sync_status' => 'synced',
                ]);
            } else {
                $wcProductId = $item->product?->woocommerce_id;
                if (! $wcProductId) {
                    return false;
                }

                $qty = (int) $item->stock_qty;
                $payload = [
                    'manage_stock' => true,
                    'stock_quantity' => max($qty, 0),
                ];
                $response = $this->client->put("products/{$wcProductId}/variations/{$item->woocommerce_id}", $payload);

                $item->update([
                    'woocommerce_synced_at' => now(),
                    'woocommerce_sync_status' => 'synced',
                ]);
            }

            WooCommerceSyncLog::log(
                'inventory',
                $item->id,
                $item->woocommerce_id,
                'laravel_to_woocommerce',
                'sync_stock',
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
                'sync_stock',
                'failed',
                $payload ?? null,
                null,
                $e->getMessage()
            );

            return false;
        }
    }

    public function detectStockMismatch(Product $product): ?array
    {
        if (! $this->client->isConfigured() || ! $product->woocommerce_id) {
            return null;
        }

        try {
            $wcData = $this->client->get("products/{$product->woocommerce_id}");
            $wcStock = (int) ($wcData['stock_quantity'] ?? 0);
            $laravelStock = (int) $product->available_stock;

            if ($wcStock !== $laravelStock) {
                ActivityLogService::log(
                    'stock_mismatch',
                    'inventory',
                    "Stock mismatch detected for Product #{$product->id} ({$product->name}): WooCommerce={$wcStock}, Laravel Available={$laravelStock}"
                );

                return [
                    'product_id' => $product->id,
                    'woocommerce_id' => $product->woocommerce_id,
                    'laravel_available_stock' => $laravelStock,
                    'woocommerce_stock' => $wcStock,
                    'mismatch' => true,
                ];
            }

            return null;
        } catch (Throwable $e) {
            return null;
        }
    }
}
