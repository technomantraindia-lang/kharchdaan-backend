<?php

namespace App\Services;

use App\Models\Order;
use App\Models\RtoShipment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RtoService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function createRto(Order $order, ?string $reason = null, ?string $shipmentId = null, ?User $byUser = null): RtoShipment
    {
        return DB::transaction(function () use ($order, $reason, $shipmentId, $byUser) {
            // Prevent duplicate active RTO records for the same order
            $existing = RtoShipment::where('order_id', $order->id)->whereNotIn('status', ['rto_closed'])->first();
            if ($existing) {
                return $existing;
            }

            $rtoNum = 'RTO-' . str_pad((string) (RtoShipment::max('id') + 1), 6, '0', STR_PAD_LEFT);
            $userId = $byUser?->id ?? Auth::id();

            $rto = RtoShipment::create([
                'rto_num' => $rtoNum,
                'order_id' => $order->id,
                'shipment_id' => $shipmentId ?: $order->tracking_num,
                'reason' => $reason ?: 'Shipment returned to origin (undelivered)',
                'status' => 'rto_initiated',
                'created_by' => $userId,
            ]);

            ActivityLogService::log(
                'rto_initiated',
                'sales',
                "Initiated RTO #{$rto->rto_num} for Order #{$order->order_num} (NO RESTOCK YET)",
                $rto
            );

            return $rto;
        });
    }

    public function updateRtoStatus(RtoShipment $rto, string $newStatus, int $damagedQty = 0, ?User $byUser = null): bool
    {
        $validStatuses = ['rto_initiated', 'rto_in_transit', 'rto_received', 'rto_inspected', 'rto_restocked', 'rto_damaged', 'rto_closed'];
        if (! in_array($newStatus, $validStatuses, true)) {
            throw new InvalidArgumentException("Invalid RTO status '{$newStatus}'.");
        }

        return DB::transaction(function () use ($rto, $newStatus, $damagedQty, $byUser) {
            $previousStatus = $rto->status;

            $updateData = ['status' => $newStatus];

            if ($newStatus === 'rto_received' && ! $rto->received_at) {
                $updateData['received_at'] = now();
            }
            if ($newStatus === 'rto_inspected' && ! $rto->inspected_at) {
                $updateData['inspected_at'] = now();
            }

            // RESTOCK RULE: Restock ONLY after physical receipt & inspection (status == 'rto_restocked')
            if ($newStatus === 'rto_restocked' && ! $rto->restocked_at) {
                $updateData['restocked_at'] = now();
                $updateData['damaged_qty'] = $damagedQty;

                $totalRestocked = 0;
                foreach ($rto->order->items as $orderItem) {
                    if ($orderItem->product) {
                        $qtyToRestock = max($orderItem->qty - $damagedQty, 0);
                        if ($qtyToRestock > 0) {
                            $this->inventoryService->increaseStock(
                                $orderItem->product,
                                $qtyToRestock,
                                "RTO Restock (RTO #{$rto->rto_num})",
                                $rto,
                                $byUser
                            );
                            $totalRestocked += $qtyToRestock;
                        }
                    }
                }

                $updateData['restocked_qty'] = $totalRestocked;
            }

            $rto->update($updateData);

            ActivityLogService::log(
                'rto_status_updated',
                'sales',
                "Updated RTO #{$rto->rto_num} status ({$previousStatus} -> {$newStatus})",
                $rto
            );

            return true;
        });
    }
}
