<?php

namespace App\Services\Cashback;

use App\Models\CashbackActionHistory;
use App\Models\CashbackAdjustment;
use App\Models\CashbackEligibility;
use App\Models\CashbackPayoutBatchItem;
use App\Models\CashbackStatusHistory;
use App\Models\Refund;
use App\Models\User;
use App\Support\MlmDecimal;
use Illuminate\Support\Facades\DB;
use LogicException;

class CashbackRefundReconciliationService
{
    public function processApprovedRefund(Refund $refund, ?User $actor = null): ?CashbackEligibility
    {
        return DB::transaction(function () use ($refund, $actor): ?CashbackEligibility {
            $lockedRefund = Refund::query()->lockForUpdate()->with('order')->findOrFail($refund->id);
            if (! in_array($lockedRefund->status, ['approved', 'completed'], true)) {
                return null;
            }

            $cashback = CashbackEligibility::query()->where('order_id', $lockedRefund->order_id)->lockForUpdate()->first();
            if (! $cashback) {
                return null;
            }

            $originalEligible = MlmDecimal::normalize((string) ($cashback->original_eligible_amount ?? $cashback->final_eligible_amount));
            $originalCashback = MlmDecimal::normalize((string) ($cashback->original_cashback_amount ?? $cashback->maximum_cashback_amount));
            if ($cashback->original_eligible_amount === null || $cashback->original_cashback_amount === null) {
                $cashback->update([
                    'original_eligible_amount' => $originalEligible,
                    'original_cashback_amount' => $originalCashback,
                ]);
            }

            $refunded = $this->approvedRefundTotal($lockedRefund->order_id);
            $currentEligible = $this->remainingEligible($originalEligible, $refunded);
            $recoveryTarget = $this->proportionalAmount($originalCashback, $refunded, $originalEligible);
            $alreadyRecovered = $this->recoveredAmount($cashback->id);
            $increment = MlmDecimal::subtract($recoveryTarget, $alreadyRecovered);
            if (bccomp($increment, '0', MlmDecimal::SCALE) < 0) {
                $increment = MlmDecimal::normalize('0');
            }
            $eventKey = hash('sha256', 'cashback-refund-event|'.$cashback->id.'|refund:'.$lockedRefund->id);

            if (CashbackAdjustment::query()->where('idempotency_key', $eventKey)->exists()) {
                return $cashback->fresh(['order', 'adjustments']);
            }

            $wasPaid = $cashback->status === CashbackEligibility::STATUS_PAID;
            $batchId = $cashback->payoutBatchItems()->first()?->batch_id;
            $reason = trim((string) ($lockedRefund->reason ?: 'Approved refund adjustment.'));
            $snapshot = [
                'refund_id' => $lockedRefund->id,
                'refund_reference' => $lockedRefund->refund_num,
                'original_cashback_amount' => $originalCashback,
                'recovered_amount' => $recoveryTarget,
                'remaining_recovery_amount' => MlmDecimal::subtract($originalCashback, $recoveryTarget),
                'refunded_amount' => $refunded,
                'remaining_eligible_amount' => $currentEligible,
                'recovered_this_event' => $increment,
                'reason' => $reason,
                'recorded_at' => now()->toDateTimeString(),
            ];

            CashbackAdjustment::create([
                'cashback_eligibility_id' => $cashback->id,
                'order_id' => $cashback->order_id,
                'member_id' => $cashback->member_id,
                'adjustment_type' => CashbackAdjustment::TYPE_REFUND_RECOVERY,
                'amount' => MlmDecimal::negate($increment),
                'idempotency_key' => $eventKey,
                'reason' => $reason,
                'adjustment_snapshot' => $snapshot,
                'created_by' => $actor?->id ?? $lockedRefund->processed_by,
                'source_refund_id' => $lockedRefund->id,
            ]);

            if ($wasPaid) {
                $this->recordHistories($cashback, $cashback->status, $cashback->status, $reason, $actor, $lockedRefund, 'refund_recovery', $batchId);

                return $cashback->fresh(['order', 'adjustments']);
            }

            $eligibleForCashback = bccomp($currentEligible, CashbackEligibilityService::THRESHOLD, MlmDecimal::SCALE) >= 0;
            $newMaximum = $eligibleForCashback ? $currentEligible : MlmDecimal::normalize('0');
            $from = $cashback->status;
            $to = $eligibleForCashback ? $from : CashbackEligibility::STATUS_CANCELLED_DUE_TO_REFUND;
            $this->syncUnpaidBatch($cashback, $newMaximum);

            $cashback->update([
                'current_eligible_amount' => $currentEligible,
                'final_eligible_amount' => $currentEligible,
                'maximum_cashback_amount' => $newMaximum,
                'refunded_amount' => $refunded,
                'refund_status' => bccomp($currentEligible, '0', MlmDecimal::SCALE) <= 0 ? 'fully_refunded' : 'partially_refunded',
                'status' => $to,
                'selected_pool_id' => $eligibleForCashback ? $cashback->selected_pool_id : null,
                'selected_by' => $eligibleForCashback ? $cashback->selected_by : null,
                'selected_at' => $eligibleForCashback ? $cashback->selected_at : null,
                'ineligibility_reason' => $eligibleForCashback ? null : $reason,
                'last_processing_error' => null,
                'last_processed_at' => now(),
            ]);
            $this->recordHistories($cashback, $from, $to, $reason, $actor, $lockedRefund, $to === $from ? 'refund_adjusted' : 'refund_cancelled', $batchId);

            return $cashback->fresh(['order', 'adjustments']);
        });
    }

    public function retry(CashbackEligibility $cashback): CashbackEligibility
    {
        $refund = Refund::query()->where('order_id', $cashback->order_id)->whereIn('status', ['approved', 'completed'])->latest('id')->first();
        if (! $refund) {
            throw new LogicException('No approved refund exists for this cashback record.');
        }

        return $this->processApprovedRefund($refund) ?? $cashback->fresh();
    }

    private function syncUnpaidBatch(CashbackEligibility $cashback, string $newAmount): void
    {
        $item = CashbackPayoutBatchItem::query()
            ->where('cashback_eligibility_id', $cashback->id)
            ->lockForUpdate()
            ->first();
        if (! $item) {
            return;
        }

        $batch = $item->batch()->lockForUpdate()->first();
        if (! $batch) {
            throw new LogicException('Cashback payout batch is missing.');
        }
        if (in_array($batch->status, ['paid', 'processing'], true)) {
            throw new LogicException('A refund cannot silently edit a paid or processing cashback batch.');
        }

        $oldAmount = MlmDecimal::normalize((string) $item->amount);
        $newAmount = MlmDecimal::normalize($newAmount);
        if (bccomp($newAmount, $oldAmount, MlmDecimal::SCALE) > 0) {
            throw new LogicException('Refund reconciliation cannot increase a selected cashback amount.');
        }

        $released = MlmDecimal::subtract($oldAmount, $newAmount);
        if (! MlmDecimal::isPositive($released)) {
            return;
        }

        $newBatchTotal = MlmDecimal::subtract((string) $batch->total_amount, $released);
        if (bccomp($newBatchTotal, '0', MlmDecimal::SCALE) < 0) {
            throw new LogicException('Cashback batch total cannot become negative.');
        }
        $pool = $batch->pool()->lockForUpdate()->first();
        if (! $pool) {
            throw new LogicException('Cashback profit pool is missing.');
        }
        $newPoolAllocation = MlmDecimal::subtract((string) $pool->allocated_amount, $released);
        if (bccomp($newPoolAllocation, '0', MlmDecimal::SCALE) < 0) {
            throw new LogicException('Cashback profit-pool allocation cannot become negative.');
        }

        $batch->update([
            'total_amount' => $newBatchTotal,
            'record_count' => bccomp($newAmount, '0', MlmDecimal::SCALE) === 0
                ? max(0, (int) $batch->record_count - 1)
                : (int) $batch->record_count,
        ]);
        $pool->update(['allocated_amount' => $newPoolAllocation]);

        if (bccomp($newAmount, '0', MlmDecimal::SCALE) === 0) {
            $item->delete();
        } else {
            $item->update([
                'amount' => $newAmount,
                'record_snapshot' => array_merge((array) $item->record_snapshot, [
                    'current_cashback_amount' => $newAmount,
                    'refund_adjusted_at' => now()->toDateTimeString(),
                ]),
            ]);
        }
    }

    private function recordHistories(
        CashbackEligibility $cashback,
        string $from,
        string $to,
        string $reason,
        ?User $actor,
        Refund $refund,
        string $action,
        ?int $batchId
    ): void {
        CashbackStatusHistory::create([
            'cashback_eligibility_id' => $cashback->id,
            'batch_id' => $batchId,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => 'Refund '.$refund->refund_num.' reconciled: '.$reason,
            'acted_by' => $actor?->id ?? $refund->processed_by,
            'metadata' => ['refund_id' => $refund->id, 'refund_reference' => $refund->refund_num],
        ]);
        CashbackActionHistory::create([
            'cashback_eligibility_id' => $cashback->id,
            'batch_id' => $batchId,
            'action' => $action,
            'reason' => $reason,
            'acted_by' => $actor?->id ?? $refund->processed_by,
            'metadata' => ['refund_id' => $refund->id, 'refund_reference' => $refund->refund_num],
        ]);
    }

    private function approvedRefundTotal(int $orderId): string
    {
        return Refund::query()->where('order_id', $orderId)->whereIn('status', ['approved', 'completed'])->get()
            ->reduce(fn (string $total, Refund $refund): string => MlmDecimal::add($total, MlmDecimal::normalize((string) $refund->amount)), MlmDecimal::normalize('0'));
    }

    private function remainingEligible(string $original, string $refunded): string
    {
        $remaining = MlmDecimal::subtract($original, $refunded);

        return bccomp($remaining, '0', MlmDecimal::SCALE) > 0 ? $remaining : MlmDecimal::normalize('0');
    }

    private function proportionalAmount(string $original, string $refunded, string $basis): string
    {
        if (bccomp($basis, '0', MlmDecimal::SCALE) <= 0) {
            return MlmDecimal::normalize('0');
        }

        $ratio = MlmDecimal::divide(MlmDecimal::minimum($refunded, $basis), $basis);
        $amount = MlmDecimal::multiply($original, $ratio);

        return MlmDecimal::minimum($amount, $original);
    }

    private function recoveredAmount(int $cashbackId): string
    {
        return CashbackAdjustment::query()
            ->where('cashback_eligibility_id', $cashbackId)
            ->where('adjustment_type', CashbackAdjustment::TYPE_REFUND_RECOVERY)
            ->get()
            ->reduce(fn (string $total, CashbackAdjustment $adjustment): string => MlmDecimal::add($total, MlmDecimal::absolute((string) $adjustment->amount)), MlmDecimal::normalize('0'));
    }
}
