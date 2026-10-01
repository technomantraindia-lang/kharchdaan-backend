<?php

namespace App\Services\WooCommerce;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WooCommerceClient
{
    protected string $url;

    protected string $consumerKey;

    protected string $consumerSecret;

    protected string $apiVersion;

    protected int $timeout;

    public function __construct()
    {
        $this->url = rtrim((string) config('woocommerce.url', ''), '/');
        $this->consumerKey = (string) config('woocommerce.consumer_key', '');
        $this->consumerSecret = (string) config('woocommerce.consumer_secret', '');
        $this->apiVersion = trim((string) config('woocommerce.api_version', 'wc/v3'), '/');
        $this->timeout = (int) config('woocommerce.timeout', 30);
    }

    public function isConfigured(): bool
    {
        return ! empty($this->url) && ! empty($this->consumerKey) && ! empty($this->consumerSecret);
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'WooCommerce API credentials are not configured in environment (.env).',
                'details' => null,
            ];
        }

        try {
            $response = $this->get('system_status');

            return [
                'success' => true,
                'message' => 'Connection established successfully with WooCommerce store.',
                'details' => [
                    'environment' => $response['environment'] ?? null,
                    'database' => $response['database'] ?? null,
                ],
            ];
        } catch (Throwable $e) {
            Log::warning('WooCommerce test connection failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to connect to WooCommerce API: ' . $e->getMessage(),
                'details' => null,
            ];
        }
    }

    public function get(string $endpoint, array $query = []): array
    {
        $response = $this->client()->get($this->buildUrl($endpoint), $query);
        $this->handleError($response);

        return $response->json() ?? [];
    }

    public function post(string $endpoint, array $data = []): array
    {
        $response = $this->client()->post($this->buildUrl($endpoint), $data);
        $this->handleError($response);

        return $response->json() ?? [];
    }

    public function put(string $endpoint, array $data = []): array
    {
        $response = $this->client()->put($this->buildUrl($endpoint), $data);
        $this->handleError($response);

        return $response->json() ?? [];
    }

    public function delete(string $endpoint, array $query = []): array
    {
        $response = $this->client()->delete($this->buildUrl($endpoint), $query);
        $this->handleError($response);

        return $response->json() ?? [];
    }

    protected function client(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->retry(2, 200, throw: false)
            ->withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->acceptJson();
    }

    protected function buildUrl(string $endpoint): string
    {
        $cleanEndpoint = ltrim($endpoint, '/');

        return "{$this->url}/wp-json/{$this->apiVersion}/{$cleanEndpoint}";
    }

    protected function handleError($response): void
    {
        if ($response->failed()) {
            $status = $response->status();
            $body = $response->json();
            $message = $body['message'] ?? "HTTP {$status} error from WooCommerce API.";

            throw new \RuntimeException("WooCommerce API Error ({$status}): {$message}");
        }
    }

    public function getMaskedConfig(): array
    {
        $key = $this->consumerKey;
        $secret = $this->consumerSecret;

        return [
            'url' => $this->url ?: 'Not Set',
            'api_version' => $this->apiVersion,
            'is_configured' => $this->isConfigured(),
            'consumer_key_masked' => ! empty($key) ? substr($key, 0, 7) . '...' . substr($key, -4) : 'Not Set',
            'consumer_secret_masked' => ! empty($secret) ? substr($secret, 0, 7) . '...' . substr($secret, -4) : 'Not Set',
        ];
    }
}
