<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\WooCommerceSyncLog;
use App\Services\ActivityLogService;
use App\Services\WooCommerce\ProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncProductToWooCommerce implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public int $timeout = 120;

    public int $productId;

    public function __construct(int $productId)
    {
        $this->productId = $productId;
    }

    public function handle(ProductSyncService $syncService): void
    {
        $product = Product::find($this->productId);

        if (! $product) {
            return;
        }

        $syncService->syncProduct($product);
    }

    public function failed(Throwable $exception): void
    {
        $product = Product::find($this->productId);

        if ($product) {
            $product->update(['woocommerce_sync_status' => 'failed']);
        }

        WooCommerceSyncLog::log(
            'product',
            $this->productId,
            $product?->woocommerce_id,
            'laravel_to_woocommerce',
            'job_failed',
            'failed',
            null,
            null,
            $exception->getMessage()
        );

        ActivityLogService::log(
            'job_failed',
            'woocommerce',
            "Queue job SyncProductToWooCommerce failed for Product #{$this->productId}: {$exception->getMessage()}"
        );
    }
}
