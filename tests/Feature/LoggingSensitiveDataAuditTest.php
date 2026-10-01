<?php

namespace Tests\Feature;

use App\Models\WebhookLog;
use App\Services\WooCommerce\WooCommerceWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class LoggingSensitiveDataAuditTest extends TestCase
{
    use RefreshDatabase;

    /** 1. Test WebhookLog::sanitizePayload masks sensitive keys */
    public function test_1_webhook_log_sanitize_payload_masks_sensitive_keys(): void
    {
        $payload = [
            'id' => 123,
            'email' => 'user@example.com',
            'password' => 'secret_password_123',
            'billing' => [
                'card_number' => '4111222233334444',
                'cvv' => '123',
            ],
            'api_key' => 'secret_api_key',
        ];

        $sanitized = WebhookLog::sanitizePayload($payload);

        $this->assertEquals('[REDACTED]', $sanitized['password']);
        $this->assertEquals('[REDACTED]', $sanitized['billing']['card_number']);
        $this->assertEquals('[REDACTED]', $sanitized['billing']['cvv']);
        $this->assertEquals('[REDACTED]', $sanitized['api_key']);
        $this->assertEquals('user@example.com', $sanitized['email']);
    }

    /** 2. Test WooCommerceWebhookService processRequest redacts sensitive payload in DB */
    public function test_2_webhook_service_process_request_redacts_sensitive_data(): void
    {
        $webhookService = new WooCommerceWebhookService();

        config(['woocommerce.webhook_secret' => 'test_secret']);
        $rawPayload = json_encode([
            'id' => 555666,
            'customer_secret' => 'my_private_token_xyz',
            'user' => [
                'password' => 'plaintext_pass',
            ],
        ]);

        $signature = base64_encode(hash_hmac('sha256', $rawPayload, 'test_secret', true));
        $deliveryId = 'deliv_sens_' . uniqid();

        $request = Request::create(
            '/api/webhooks/woocommerce',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_X_WC_WEBHOOK_SIGNATURE' => $signature,
                'HTTP_X_WC_WEBHOOK_TOPIC' => 'customer.created',
                'HTTP_X_WC_WEBHOOK_DELIVERY_ID' => $deliveryId,
            ],
            $rawPayload
        );

        $webhookService->processRequest($request);

        $log = WebhookLog::where('delivery_id', $deliveryId)->first();
        $this->assertNotNull($log);
        $this->assertEquals('[REDACTED]', $log->payload['customer_secret']);
        $this->assertEquals('[REDACTED]', $log->payload['user']['password']);
    }
}
