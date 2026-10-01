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
        $admin = Auth::user();

        $order->update(['pay_status' => 'paid']);

        if ($order->payment) {
            $order->payment->update([
                'status' => 'paid',
                'txn_id' => $txnId ?: $order->payment->txn_id,
            ]);
        }

        ActivityLogService::log(
            'manual_payment_verification',
            'payments',
            "Manual payment verification for Order #{$order->order_num} by Admin #{$admin?->id} ({$admin?->email}). Reason: {$reason}",
            $order,
            ['pay_status' => 'pending'],
            ['pay_status' => 'paid', 'verified_by' => $admin?->id, 'reason' => $reason, 'verified_at' => now()->toIso8601String()]
        );

        return $order;
    }
}
