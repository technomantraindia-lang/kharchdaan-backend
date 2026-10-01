<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Mlm\MlmOrderIntegrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMlmEligibleOrder implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $orderId) {}

    public function uniqueId(): string
    {
        return 'mlm-order-'.$this->orderId;
    }

    public function handle(MlmOrderIntegrationService $integrationService): void
    {
        $order = Order::query()->find($this->orderId);
        if ($order) {
            $integrationService->process($order);
        }
    }
}
