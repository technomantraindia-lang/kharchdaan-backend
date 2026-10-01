<?php

namespace App\Services\WooCommerce;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\WooCommerceSyncLog;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderSyncService
{
    protected WooCommerceClient $client;

    protected CustomerSyncService $customerSync;

    public function __construct(WooCommerceClient $client, CustomerSyncService $customerSync)
    {
        $this->client = $client;
        $this->customerSync = $customerSync;
    }

    public function importOrderByWooCommerceId(int $woocommerceId): ?Order
    {
        if (! $this->client->isConfigured()) {
            WooCommerceSyncLog::log('order', null, $woocommerceId, 'woocommerce_to_laravel', 'import', 'failed', null, null, 'WooCommerce API credentials not configured.');
            return null;
        }

        try {
            $payload = $this->client->get("orders/{$woocommerceId}");
            return $this->importOrderPayload($payload);
        } catch (Throwable $e) {
            WooCommerceSyncLog::log('order', null, $woocommerceId, 'woocommerce_to_laravel', 'import', 'failed', null, null, $e->getMessage());
            return null;
        }
    }

    public function syncRecentOrders(int $perPage = 20): array
    {
        if (! $this->client->isConfigured()) {
            return ['success' => false, 'message' => 'WooCommerce API credentials not configured.', 'imported' => 0];
        }

        try {
            $orders = $this->client->get('orders', ['per_page' => $perPage]);
            $count = 0;

            foreach ($orders as $payload) {
                if ($this->importOrderPayload($payload)) {
                    $count++;
                }
            }

            return [
                'success' => true,
                'message' => "Successfully imported/synchronized {$count} orders from WooCommerce.",
                'imported' => $count,
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => "Order sync failed: {$e->getMessage()}", 'imported' => 0];
        }
    }

    public function importOrderPayload(array $payload): ?Order
    {
        $wcId = (int) ($payload['id'] ?? 0);
        if ($wcId <= 0) {
            return null;
        }

        return DB::transaction(function () use ($payload, $wcId) {
            $existingOrder = Order::where('woocommerce_id', $wcId)->first();

            // 1. Resolve Customer (or Guest)
            $wcCustomerId = (int) ($payload['customer_id'] ?? 0);
            $user = null;

            if ($wcCustomerId > 0) {
                $user = $this->customerSync->syncCustomerByWooCommerceId($wcCustomerId);
            }

            $laravelStatus = OrderStatusMapper::toLaravelStatus($payload['status'] ?? 'pending', $existingOrder?->status);
            $payStatus = OrderStatusMapper::toLaravelPayStatus($payload['status'] ?? 'pending', ! empty($payload['date_paid']));

            $orderNum = ! empty($payload['number']) ? 'WC-' . $payload['number'] : 'WC-' . $wcId;

            $billAddr = json_encode($payload['billing'] ?? []);
            $shipAddr = json_encode($payload['shipping'] ?? []);

            $orderData = [
                'woocommerce_id' => $wcId,
                'order_num' => $orderNum,
                'user_id' => $user?->id,
                'subtotal' => (float) ($payload['subtotal'] ?? $payload['total'] ?? 0),
                'discount' => (float) ($payload['discount_total'] ?? 0),
                'gst_amt' => (float) ($payload['total_tax'] ?? 0),
                'ship_charge' => (float) ($payload['shipping_total'] ?? 0),
                'total' => (float) ($payload['total'] ?? 0),
                'status' => $laravelStatus,
                'pay_status' => $payStatus,
                'currency' => $payload['currency'] ?? 'INR',
                'payment_method' => $payload['payment_method_title'] ?? ($payload['payment_method'] ?? 'Online'),
                'txn_id' => $payload['transaction_id'] ?? null,
                'bill_addr' => $billAddr,
                'ship_addr' => $shipAddr,
                'woocommerce_synced_at' => now(),
            ];

            $order = Order::updateOrCreate(['woocommerce_id' => $wcId], $orderData);

            // Record status history if status changed or new order
            if (! $existingOrder || $existingOrder->status !== $laravelStatus) {
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => $laravelStatus,
                    'note' => "Imported/Updated from WooCommerce (WC Order #{$wcId}, Status: {$payload['status']})",
                ]);
            }

            // Sync payment record if transaction ID exists
            if (! empty($payload['transaction_id']) || $payStatus === 'paid') {
                Payment::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'amount' => $order->total,
                        'method' => $order->payment_method ?? 'woocommerce',
                        'status' => $payStatus,
                        'txn_id' => $payload['transaction_id'] ?? 'WC-' . $wcId,
                        'resp' => json_encode(['wc_order_id' => $wcId, 'payment_method' => $payload['payment_method'] ?? null]),
                    ]
                );
            }

            // 2. Sync Order Items Snapshot
            $lineItems = $payload['line_items'] ?? [];
            if (! empty($lineItems)) {
                // Delete existing items for updateOrCreate clean snapshot sync
                OrderItem::where('order_id', $order->id)->delete();

                foreach ($lineItems as $item) {
                    $itemWcProdId = (int) ($item['product_id'] ?? 0);
                    $itemWcVarId = (int) ($item['variation_id'] ?? 0);
                    $itemSku = (string) ($item['sku'] ?? '');
                    $itemName = (string) ($item['name'] ?? 'Product');

                    // Try matching local Product
                    $localProduct = null;
                    if ($itemWcProdId > 0) {
                        $localProduct = Product::where('woocommerce_id', $itemWcProdId)->first();
                    }
                    if (! $localProduct && ! empty($itemSku)) {
                        $localProduct = Product::where('sku', $itemSku)->first();
                    }

                    // Try matching local ProductVariation
                    $localVar = null;
                    if ($itemWcVarId > 0) {
                        $localVar = ProductVariation::where('woocommerce_id', $itemWcVarId)->first();
                    }
                    if (! $localVar && ! empty($itemSku)) {
                        $localVar = ProductVariation::where('sku', $itemSku)->first();
                    }

                    if (! $localProduct && ! $localVar) {
                        WooCommerceSyncLog::log(
                            'order',
                            $order->id,
                            $wcId,
                            'woocommerce_to_laravel',
                            'unmatched_item',
                            'failed',
                            $item,
                            null,
                            "Order Item '{$itemName}' (SKU: {$itemSku}, WC Prod ID: {$itemWcProdId}) could not be matched to local product. Preserved snapshot data."
                        );
                    }

                    $qty = (int) ($item['quantity'] ?? 1);
                    $unitPrice = (float) ($item['price'] ?? 0);
                    $lineTotal = (float) ($item['total'] ?? ($unitPrice * $qty));
                    $taxTotal = (float) ($item['total_tax'] ?? 0);
                    $gstPct = $lineTotal > 0 ? round(($taxTotal / $lineTotal) * 100, 2) : 0;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $localProduct?->id,
                        'var_id' => $localVar?->id,
                        'qty' => $qty,
                        'price' => $unitPrice,
                        'gst_pct' => $gstPct,
                        'product_name' => $itemName,
                        'sku' => $itemSku,
                        'discount' => 0.00,
                        'line_total' => $lineTotal,
                    ]);
                }
            }

            WooCommerceSyncLog::log(
                'order',
                $order->id,
                $wcId,
                'woocommerce_to_laravel',
                $existingOrder ? 'update' : 'create',
                'success',
                $payload,
                ['order_id' => $order->id]
            );

            ActivityLogService::log(
                'woocommerce_order_import',
                'orders',
                "Imported WooCommerce Order #{$wcId} -> Laravel Order #{$order->order_num}"
            );

            return $order;
        });
    }
}
