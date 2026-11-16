<?php

namespace App\Services\Order;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderTransactionIdGenerator
{
    /**
     * Generate TYYMMDD0001 style transaction/order numbers.
     */
    public function next(?\DateTimeInterface $date = null): string
    {
        $date = $date ? \Carbon\Carbon::instance(\Carbon\Carbon::parse($date)) : now();
        $prefix = 'T'.$date->format('ymd');

        return DB::transaction(function () use ($prefix): string {
            for ($attempt = 1; $attempt <= 8; $attempt++) {
                $latest = Order::query()
                    ->where('order_num', 'like', $prefix.'%')
                    ->orderByDesc('order_num')
                    ->lockForUpdate()
                    ->value('order_num');

                $sequence = 1;
                if (is_string($latest) && preg_match('/^T\d{6}(\d{4})$/', $latest, $matches)) {
                    $sequence = ((int) $matches[1]) + 1;
                }

                if ($sequence > 9999) {
                    throw new RuntimeException('Daily transaction ID sequence exhausted for '.$prefix);
                }

                $candidate = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

                if (! Order::query()->where('order_num', $candidate)->exists()) {
                    return $candidate;
                }
            }

            throw new RuntimeException('Unable to allocate a unique transaction ID.');
        });
    }
}
