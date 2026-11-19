<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class OrderService
{
    protected array $allowedTransitions = [
        'pending' => ['processing', 'cancelled', 'failed'],
        'processing' => ['packed', 'cancelled'],
        'packed' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'cancelled'],
        'delivered' => ['refunded'],
        'cancelled' => [],
        'failed' => [],
        'refunded' => [],
    ];

    public function canTransition(string $currentStatus, string $targetStatus): bool
    {
        if ($currentStatus === $targetStatus) {
            return true;
        }

        return isset($this->allowedTransitions[$currentStatus])
            && in_array($targetStatus, $this->allowedTransitions[$currentStatus], true);
    }

    public function updateOrderStatus(
        Order $order,
        string $newStatus,
        ?string $note = null,
        bool $isSuperAdminOverride = false,
        ?string $overrideReason = null,
        string $logAction = 'update_order_status'
    ): Order {
        $oldStatus = $order->status;

        if ($oldStatus === $newStatus) {
            return $order;
        }

        if (! $this->canTransition($oldStatus, $newStatus)) {
            if ($isSuperAdminOverride) {
                if (empty($overrideReason)) {
                    throw new InvalidArgumentException('Super Admin override requires a specified reason.');
                }
                ActivityLogService::log(
                    'order_status_override',
                    'orders',
                    "Super Admin overridden status transition for Order #{$order->order_num} from {$oldStatus} to {$newStatus}. Reason: {$overrideReason}",
                    $order
                );
            } else {
                throw new InvalidArgumentException("Invalid status transition from '{$oldStatus}' to '{$newStatus}'.");
            }
        }

        $order->update(['status' => $newStatus]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $newStatus,
            'note' => $note ?? "Status updated from {$oldStatus} to {$newStatus}" . ($overrideReason ? " (Override: {$overrideReason})" : ''),
        ]);

        ActivityLogService::log(
            $logAction,
            'orders',
            "Order {$order->order_num} status changed from {$oldStatus} to {$newStatus}",
            $order,
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );

        return $order;
    }

    public function markPaymentVerifiedManually(
        Order $order,
        string $reason,
        ?string $txnId = null
    ): Order {
        return $this->updatePaymentStatus(
            $order,
            'paid',
            $reason,
            $txnId,
            'manual_payment_verification'
        );
    }

    /**
     * Admin can set payment status from the order manage form (COD collect, mark failed, etc).
     */
    public function updatePaymentStatus(
        Order $order,
        string $newPayStatus,
        ?string $reason = null,
        ?string $txnId = null,
        string $logAction = 'update_payment_status'
    ): Order {
        $allowed = ['pending', 'paid', 'failed', 'refunded'];
        if (! in_array($newPayStatus, $allowed, true)) {
            throw new InvalidArgumentException("Invalid payment status '{$newPayStatus}'.");
        }

        $oldPayStatus = (string) ($order->pay_status ?? 'pending');
        if ($oldPayStatus === $newPayStatus) {
            return $order;
        }

        $admin = Auth::user();
        $order->update(['pay_status' => $newPayStatus]);

        $payment = $order->relationLoaded('payment') ? $order->payment : $order->payment()->first();
        if ($payment) {
            $payment->update([
                'status' => $newPayStatus,
                'txn_id' => $txnId ?: $payment->txn_id,
            ]);
        } elseif ($newPayStatus === 'paid') {
            // COD/offline drafts may not have a payment row yet.
            \App\Models\Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total,
                'method' => $order->payment_method ?: 'cod',
                'status' => 'paid',
                'txn_id' => $txnId,
            ]);
        }

        ActivityLogService::log(
            $logAction,
            'payments',
            "Payment status for Order #{$order->order_num} changed from {$oldPayStatus} to {$newPayStatus} by Admin #{$admin?->id}"
                .($reason ? ". Reason: {$reason}" : ''),
            $order,
            ['pay_status' => $oldPayStatus],
            [
                'pay_status' => $newPayStatus,
                'updated_by' => $admin?->id,
                'reason' => $reason,
                'updated_at' => now()->toIso8601String(),
            ]
        );

        return $order->fresh(['payment']);
    }
}
