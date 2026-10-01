<?php

namespace App\Services\WooCommerce;

use App\Jobs\ImportWooCommerceOrder;
use App\Jobs\SyncWooCommerceCustomer;
use App\Jobs\UpdateWooCommerceOrder;
use App\Models\WebhookLog;
use App\Models\WooCommerceSyncLog;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Throwable;

class WooCommerceWebhookService
{
    public function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-WC-Webhook-Signature');
        $secret = config('woocommerce.webhook_secret');

        if (empty($signature) || empty($secret)) {
            return false;
        }

        $rawBody = $request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        return hash_equals($expectedSignature, $signature);
    }

    public function processRequest(Request $request): array
    {
        $topic = $request->header('X-WC-Webhook-Topic', 'unknown');
        $deliveryId = $request->header('X-WC-Webhook-Delivery-ID');
        $resource = $request->header('X-WC-Webhook-Resource');
        $event = $request->header('X-WC-Webhook-Event');
        $payload = $request->json()->all();

        $sanitizedPayload = WebhookLog::sanitizePayload($payload);

        // 1. Check Webhook Idempotency using provider + delivery_id
        if (! empty($deliveryId) && WebhookLog::isDuplicate('woocommerce', $deliveryId)) {
            WebhookLog::create([
                'provider' => 'woocommerce',
                'delivery_id' => $deliveryId . '_dup_' . time(),
                'topic' => $topic,
                'resource' => $resource,
                'event' => $event,
                'signature_valid' => true,
                'payload' => $sanitizedPayload,
                'status' => 'duplicate',
                'error_message' => "Duplicate webhook delivery ID ({$deliveryId}) ignored.",
                'processed_at' => now(),
            ]);

            return [
                'status' => 200,
                'success' => true,
                'message' => "Duplicate delivery ID ({$deliveryId}) acknowledged without re-processing.",
            ];
        }

        // 2. Record Webhook Receipt
        $webhookLog = WebhookLog::create([
            'provider' => 'woocommerce',
            'delivery_id' => $deliveryId,
            'topic' => $topic,
            'resource' => $resource,
            'event' => $event,
            'signature_valid' => true,
            'payload' => $sanitizedPayload,
            'status' => 'processing',
        ]);

        try {
            // 3. Centralized Dispatcher (Fast Async Job Dispatching)
            switch ($topic) {
                case 'order.created':
                    ImportWooCommerceOrder::dispatch($payload);
                    $message = "Dispatched ImportWooCommerceOrder job for WC Order #" . ($payload['id'] ?? 'unknown');
                    break;

                case 'order.updated':
                    UpdateWooCommerceOrder::dispatch($payload);
                    $message = "Dispatched UpdateWooCommerceOrder job for WC Order #" . ($payload['id'] ?? 'unknown');
                    break;

                case 'customer.created':
                case 'customer.updated':
                    SyncWooCommerceCustomer::dispatch($payload);
                    $message = "Dispatched SyncWooCommerceCustomer job for WC Customer #" . ($payload['id'] ?? 'unknown');
                    break;

                case 'product.updated':
                    // Source of truth: Laravel is Product Master. Log event without blindly overwriting master product data.
                    ActivityLogService::log(
                        'woocommerce_webhook',
                        'products',
                        "Received product.updated webhook from WooCommerce for WC Product #" . ($payload['id'] ?? 'unknown')
                    );
                    $message = "Logged product.updated notification for WC Product #" . ($payload['id'] ?? 'unknown');
                    break;

                default:
                    $message = "Received unhandled webhook topic: {$topic}";
                    $webhookLog->update(['status' => 'ignored']);
                    return ['status' => 200, 'success' => true, 'message' => $message];
            }

            $webhookLog->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            WooCommerceSyncLog::log(
                'webhook',
                null,
                (int) ($payload['id'] ?? 0),
                'woocommerce_to_laravel',
                "webhook:{$topic}",
                'success',
                ['topic' => $topic, 'delivery_id' => $deliveryId],
                ['message' => $message]
            );

            return ['status' => 200, 'success' => true, 'message' => $message];
        } catch (Throwable $e) {
            $webhookLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return ['status' => 500, 'success' => false, 'message' => $e->getMessage()];
        }
    }
}
