<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\WooCommerceSyncConflict;
use App\Models\WooCommerceSyncLog;
use App\Services\WooCommerce\ProductSyncService;
use App\Services\WooCommerce\WooCommerceClient;
use Illuminate\Console\Command;

class TestWooCommerceBackend extends Command
{
    protected $signature = 'woocommerce:test-backend {--sync : Also run a single product sync test}';

    protected $description = 'Test the whole WooCommerce backend with data: config, connection, products/orders/customers endpoints, sync logs, conflicts';

    private int $pass = 0;
    private int $fail = 0;

    public function handle(WooCommerceClient $client, ProductSyncService $productSync): int
    {
        $this->info('=== WooCommerce Backend Test Suite ===');
        $this->newLine();

        // 1. Configuration
        $url = config('services.woocommerce.url') ?? env('WOOCOMMERCE_STORE_URL');
        $key = config('services.woocommerce.key') ?? env('WOOCOMMERCE_CONSUMER_KEY');
        $secret = config('services.woocommerce.secret') ?? env('WOOCOMMERCE_CONSUMER_SECRET');

        $this->test('Configuration present', function () use ($url, $key, $secret) {
            return !empty($url) && !empty($key) && !empty($secret)
                ? [true, "Store URL: {$url}"]
                : [false, 'Missing WOOCOMMERCE_STORE_URL / CONSUMER_KEY / CONSUMER_SECRET in .env'];
        });

        if (empty($url) || empty($key) || empty($secret)) {
            $this->summary();
            return self::FAILURE;
        }

        // 2. Connection test
        $this->test('API connection', function () use ($client) {
            try {
                $result = $client->testConnection();
                return [true, is_array($result) ? json_encode($result) : (string) $result];
            } catch (\Throwable $e) {
                return [false, $e->getMessage()];
            }
        });

        // 3. Data endpoints: products / orders / customers
        foreach (['products' => 'Products endpoint', 'orders' => 'Orders endpoint', 'customers' => 'Customers endpoint'] as $endpoint => $label) {
            $this->test($label, function () use ($client, $endpoint) {
                try {
                    $data = $client->get($endpoint, ['per_page' => 5]);
                    $count = is_array($data) ? count($data) : 0;
                    return [$count > 0, "{$count} record(s) fetched"];
                } catch (\Throwable $e) {
                    return [false, $e->getMessage()];
                }
            });
        }

        // 4. Local database tables/models
        $this->test('Local products table', function () {
            try {
                $count = Product::count();
                return [true, "{$count} local product(s)"];
            } catch (\Throwable $e) {
                return [false, $e->getMessage()];
            }
        });

        $this->test('woocommerce_sync_logs table', function () {
            try {
                $count = WooCommerceSyncLog::count();
                return [true, "{$count} sync log(s)"];
            } catch (\Throwable $e) {
                return [false, $e->getMessage()];
            }
        });

        $this->test('woocommerce_sync_conflicts table', function () {
            try {
                $count = WooCommerceSyncConflict::count();
                return [true, "{$count} conflict(s)"];
            } catch (\Throwable $e) {
                return [false, $e->getMessage()];
            }
        });

        // 5. Optional live sync of one mapped product
        if ($this->option('sync')) {
            $this->test('Single product sync', function () use ($productSync) {
                try {
                    $product = Product::whereNotNull('woocommerce_id')->first()
                        ?? Product::first();

                    if (!$product) {
                        return [false, 'No local product available to sync'];
                    }

                    $result = $productSync->pushProduct($product);
                    return [true, 'Sync executed for product #' . $product->id];
                } catch (\Throwable $e) {
                    return [false, $e->getMessage()];
                }
            });
        }

        $this->newLine();
        return $this->summary();
    }

    private function test(string $label, callable $fn): void
    {
        [$ok, $detail] = $fn();

        if ($ok) {
            $this->pass++;
            $this->info("[PASS] {$label}" . ($detail ? " — {$detail}" : ''));
        } else {
            $this->fail++;
            $this->error("[FAIL] {$label}" . ($detail ? " — {$detail}" : ''));
        }
    }

    private function summary(): int
    {
        $this->newLine();
        $this->line("Results: {$this->pass} passed, {$this->fail} failed");

        return $this->fail === 0 ? self::SUCCESS : self::FAILURE;
    }
}