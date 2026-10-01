<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\ReturnItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReturnService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function createReturnRequest(Order $order, string $reason, array $items = [], ?string $customerNote = null, ?User $byUser = null): OrderReturn
    {
        return DB::transaction(function () use ($order, $reason, $items, $customerNote, $byUser) {
            $returnNum = 'RET-' . str_pad((string) (OrderReturn::max('id') + 1), 6, '0', STR_PAD_LEFT);
            $userId = $byUser?->id ?? Auth::id() ?? $order->user_id;

            $orderReturn = OrderReturn::create([
                'return_num' => $returnNum,
                'order_id' => $order->id,
                'user_id' => $userId,
                'reason' => $reason,
                'status' => 'requested',
                'customer_note' => $customerNote,
            ]);

            foreach ($items as $itemData) {
                ReturnItem::create([
                    'order_return_id' => $orderReturn->id,
                    'order_item_id' => $itemData['order_item_id'] ?? null,
                    'product_id' => $itemData['product_id'] ?? null,
                    'quantity' => (int) ($itemData['quantity'] ?? 1),
                    'reason' => $itemData['reason'] ?? $reason,
                    'condition' => $itemData['condition'] ?? 'good',
                ]);
            }

            ActivityLogService::log(
                'return_requested',
                'sales',
                "Created Return request #{$orderReturn->return_num} for Order #{$order->order_num} (Status: requested - NO RESTOCK YET)",
                $orderReturn
            );

            return $orderReturn;
        });
    }

    public function updateStatus(OrderReturn $orderReturn, string $newStatus, ?string $adminNote = null, ?User $byUser = null): bool
    {
        $validStatuses = ['requested', 'approved', 'rejected', 'pickup_scheduled', 'received', 'inspected', 'completed'];
        if (! in_array($newStatus, $validStatuses, true)) {
            throw new InvalidArgumentException("Invalid return status '{$newStatus}'.");
        }

        return DB::transaction(function () use ($orderReturn, $newStatus, $adminNote, $byUser) {
            $previousStatus = $orderReturn->status;

            $orderReturn->update([
                'status' => $newStatus,
                'admin_note' => $adminNote ?: $orderReturn->admin_note,
            ]);

            // RESTOCK RULE: Restock ONLY after 'received + inspected + approved' (status == 'completed' or 'inspected' with approval)
            if ($newStatus === 'completed' && ! $orderReturn->restocked_at) {
                foreach ($orderReturn->items as $rItem) {
                    if ($rItem->product && $rItem->condition !== 'damaged' && $rItem->quantity > 0) {
                        $this->inventoryService->increaseStock(
                            $rItem->product,
                            $rItem->quantity,
                            "Return Restock (Return #{$orderReturn->return_num})",
                            $orderReturn,
                            $byUser
                        );
                    }
                }

                $orderReturn->update(['restocked_at' => now()]);
            }

            ActivityLogService::log(
                'return_status_updated',
                'sales',
                "Updated Return #{$orderReturn->return_num} status ({$previousStatus} -> {$newStatus})",
                $orderReturn
            );

            return true;
        });
    }
}
