<?php

namespace App\Observers;

use App\Jobs\ProcessMlmEligibleOrder;
use App\Models\Order;

class OrderObserver
{
    public function saved(Order $order): void
    {
        $isRelevantState = $order->status === 'delivered'
            || $order->status === 'cancelled'
            || $order->status === 'refunded'
            || $order->pay_status === 'paid';

        if ($isRelevantState && ($order->wasRecentlyCreated || $order->wasChanged(['status', 'pay_status', 'user_id']))) {
            ProcessMlmEligibleOrder::dispatch($order->id)->afterCommit();
        }
    }
}
