<?php

namespace App\Jobs;

use App\Models\ProductVariation;
use App\Models\WooCommerceSyncLog;
use App\Services\WooCommerce\ProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncVariationToWooCommerce implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public int $timeout = 120;

    public int $variationId;

    public function __construct(int $variationId)
    {
        $this->variationId = $variationId;
    }

    public function handle(ProductSyncService $syncService): void
    {
        $variation = ProductVariation::find($this->variationId);

        if (! $variation) {
            return;
        }

        $syncService->syncVariation($variation);
    }

    public function failed(Throwable $exception): void
    {
        $variation = ProductVariation::find($this->variationId);

        if ($variation) {
            $variation->update(['woocommerce_sync_status' => 'failed']);
        }

        WooCommerceSyncLog::log(
            'variation',
            $this->variationId,
            $variation?->woocommerce_id,
            'laravel_to_woocommerce',
            'job_failed',
            'failed',
            null,
            null,
            $exception->getMessage()
        );
    }
}
