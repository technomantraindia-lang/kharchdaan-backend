<?php

namespace App\Jobs;

use App\Services\WooCommerce\OrderSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class UpdateWooCommerceOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public int $timeout = 120;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(OrderSyncService $orderSync): void
    {
        $orderSync->importOrderPayload($this->payload);
    }

    public function failed(Throwable $exception): void
    {
        \App\Models\WooCommerceSyncLog::log(
            'order',
            null,
            (int) ($this->payload['id'] ?? 0),
            'woocommerce_to_laravel',
            'update_job_failed',
            'failed',
            $this->payload,
            null,
            $exception->getMessage()
        );
    }
}
