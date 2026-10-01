<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Refund;
use App\Models\User;
use App\Services\Cashback\CashbackRefundReconciliationService;
use App\Support\MlmDecimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RefundService
{
    public function getRefundableAmount(Order $order, bool $includePending = true): string
    {
        $statuses = $includePending ? ['pending', 'approved', 'completed'] : ['approved', 'completed'];
        $previouslyRefunded = Refund::where('order_id', $order->id)
            ->whereIn('status', $statuses)
            ->get()
            ->reduce(
                fn (string $total, Refund $refund): string => MlmDecimal::add($total, MlmDecimal::normalize((string) $refund->amount)),
                MlmDecimal::normalize('0')
            );
        $remaining = MlmDecimal::subtract(MlmDecimal::normalize((string) $order->total), $previouslyRefunded);

        return bccomp($remaining, '0', MlmDecimal::SCALE) > 0 ? $remaining : MlmDecimal::normalize('0');
    }

    public function requestRefund(Order $order, string|int|float $amount, string $reason = '', ?User $byUser = null): Refund
    {
        try {
            $amountDecimal = MlmDecimal::normalize((string) $amount);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException('Refund amount must be an exact non-negative decimal.');
        }
        if (! MlmDecimal::isPositive($amountDecimal)) {
            throw new InvalidArgumentException('Refund amount must be greater than zero.');
        }

        return DB::transaction(function () use ($order, $amountDecimal, $reason, $byUser): Refund {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();
            if (! $lockedOrder) {
                throw new InvalidArgumentException('Order not found.');
            }

            $refundable = $this->getRefundableAmount($lockedOrder, true);
            if (bccomp($amountDecimal, $refundable, MlmDecimal::SCALE) > 0) {
                throw new InvalidArgumentException("Refund amount (₹{$amountDecimal}) exceeds maximum refundable amount (₹{$refundable}).");
            }

            $refundNum = 'REF-'.str_pad((string) (Refund::max('id') + 1), 6, '0', STR_PAD_LEFT);
            $userId = $byUser?->id ?? Auth::id();

            $refund = Refund::create([
                'order_id' => $lockedOrder->id,
                'refund_num' => $refundNum,
                'amount' => $amountDecimal,
                'reason' => $reason,
                'status' => 'pending',
                'processed_by' => $userId,
            ]);

            ActivityLogService::log(
                'refund_requested',
                'orders',
                "Requested refund #{$refund->refund_num} of ₹{$amountDecimal} for Order #{$lockedOrder->order_num} (Reason: {$reason})",
                $refund
            );

            return $refund;
        });
    }

    public function approveRefund(Refund $refund, ?User $byUser = null): bool
    {
        return DB::transaction(function () use ($refund, $byUser): bool {
            $lockedRefund = Refund::where('id', $refund->id)->lockForUpdate()->first();
            if (! $lockedRefund || $lockedRefund->status !== 'pending') {
                $status = $lockedRefund?->status ?? 'non-existent';
                throw new InvalidArgumentException("Refund #{$refund->id} cannot be approved because current status is '{$status}'.");
            }

            $userId = $byUser?->id ?? Auth::id();
            $lockedRefund->update([
                'status' => 'approved',
                'processed_by' => $userId,
                'processed_at' => now(),
            ]);

            $order = Order::where('id', $lockedRefund->order_id)->lockForUpdate()->first();
            $totalRefunded = Refund::where('order_id', $order->id)
                ->whereIn('status', ['approved', 'completed'])
                ->get()
                ->reduce(
                    fn (string $total, Refund $item): string => MlmDecimal::add($total, MlmDecimal::normalize((string) $item->amount)),
                    MlmDecimal::normalize('0')
                );

            if (bccomp($totalRefunded, MlmDecimal::normalize((string) $order->total), MlmDecimal::SCALE) >= 0) {
                $order->update(['pay_status' => 'refunded', 'status' => 'refunded']);
            } else {
                $order->update(['pay_status' => 'refunded']);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'note' => "Approved refund #{$lockedRefund->refund_num} (Amount: ₹{$lockedRefund->amount})",
            ]);

            ActivityLogService::log(
                'refund_approved',
                'orders',
                "Approved refund #{$lockedRefund->refund_num} (₹{$lockedRefund->amount}) for Order #{$order->order_num}",
                $lockedRefund
            );

            app(CashbackRefundReconciliationService::class)->processApprovedRefund($lockedRefund, $byUser);

            return true;
        });
    }

    public function rejectRefund(Refund $refund, string $reason = '', ?User $byUser = null): bool
    {
        return DB::transaction(function () use ($refund, $reason, $byUser): bool {
            $lockedRefund = Refund::where('id', $refund->id)->lockForUpdate()->first();
            if (! $lockedRefund || $lockedRefund->status !== 'pending') {
                $status = $lockedRefund?->status ?? 'non-existent';
                throw new InvalidArgumentException("Refund #{$refund->id} cannot be rejected because current status is '{$status}'.");
            }

            $userId = $byUser?->id ?? Auth::id();
            $lockedRefund->update([
                'status' => 'rejected',
                'reason' => $reason ?: $lockedRefund->reason,
                'processed_by' => $userId,
                'processed_at' => now(),
            ]);

            ActivityLogService::log(
                'refund_rejected',
                'orders',
                "Rejected refund #{$lockedRefund->refund_num} for Order #{$lockedRefund->order?->order_num} (Reason: {$reason})",
                $lockedRefund
            );

            return true;
        });
    }
}
