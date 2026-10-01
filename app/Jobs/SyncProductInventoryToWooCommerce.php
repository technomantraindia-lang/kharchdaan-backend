<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\WooCommerceSyncLog;
use App\Services\WooCommerce\ProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncProductInventoryToWooCommerce implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public int $timeout = 120;

    public string $itemType;

    public int $itemId;

    public function __construct(string $itemType, int $itemId)
    {
        $this->itemType = $itemType;
        $this->itemId = $itemId;
    }

    public function handle(ProductSyncService $syncService): void
    {
        $item = $this->itemType === 'product'
            ? Product::find($this->itemId)
            : ProductVariation::find($this->itemId);

        if (! $item) {
            return;
        }

        $syncService->updateWooCommerceStock($item);
    }

    public function failed(Throwable $exception): void
    {
        WooCommerceSyncLog::log(
            'inventory',
            $this->itemId,
            null,
            'laravel_to_woocommerce',
            'job_failed',
            'failed',
            ['type' => $this->itemType, 'id' => $this->itemId],
            null,
            $exception->getMessage()
        );
    }
}
