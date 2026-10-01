<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class WooCommerceWebhooksTest extends TestCase
{
    use RefreshDatabase;

    protected string $webhookSecret = 'test_webhook_secret_key_123';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('woocommerce.webhook_secret', $this->webhookSecret);
    }

    public function test_webhook_rejected_when_signature_is_missing_or_invalid()
    {
        $payload = [
            'id' => 9991,
            'name' => 'Test Product',
        ];

        $response = $this->postJson('/api/webhooks/woocommerce', $payload, [
            'X-WC-Webhook-Topic' => 'product.updated',
            'X-WC-Webhook-Signature' => 'invalid_signature_string',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Unauthorized: Invalid WooCommerce webhook signature']);
    }

    public function test_webhook_accepted_when_signature_is_valid()
    {
        $payload = [
            'id' => 9992,
            'status' => 'processing',
            'number' => '1002',
            'total' => '250.00',
            'billing' => ['first_name' => 'John', 'email' => 'john@test.com'],
            'line_items' => [],
        ];

        $rawBody = json_encode($payload);
        $validSignature = base64_encode(hash_hmac('sha256', $rawBody, $this->webhookSecret, true));

        $response = $this->call(
            'POST',
            '/api/webhooks/woocommerce',
            [],
            [],
            [],
            [
                'HTTP_X_WC_WEBHOOK_TOPIC' => 'order.created',
                'HTTP_X_WC_WEBHOOK_SIGNATURE' => $validSignature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawBody
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}
