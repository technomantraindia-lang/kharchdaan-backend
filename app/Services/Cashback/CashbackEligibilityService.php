<?php

namespace App\Services\Cashback;

use App\Models\CashbackAdjustment;
use App\Models\CashbackEligibility;
use App\Models\MlmCalculationRun;
use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use App\Support\MlmDecimal;
use Illuminate\Support\Facades\DB;
use LogicException;

class CashbackEligibilityService
{
    public const THRESHOLD = '200';

    public function createEligibility(Order $order, ?MlmCalculationRun $run = null): CashbackEligibility
    {
        return DB::transaction(function () use ($order, $run): CashbackEligibility {
            $existing = CashbackEligibility::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing->fresh(['order', 'member', 'calculationRun', 'adjustments']);
            }

            $record = $this->createRecord($order, $run);
            $this->notifyCustomer($record, 'Cashback eligibility created. It is conditional and awaits company profit availability.');

            return $record;
        });
    }

    public function recalculateEligibility(
        CashbackEligibility $cashback,
        Order $order,
        ?MlmCalculationRun $run
    ): CashbackEligibility {
        return DB::transaction(function () use ($cashback, $order, $run): CashbackEligibility {
            $locked = CashbackEligibility::query()->lockForUpdate()->findOrFail($cashback->id);
            $this->assertEligibilityCanChange($locked);
            $this->updateRecord($locked, $order, $run);

            return $locked->fresh(['order', 'member', 'calculationRun', 'adjustments']);
        });
    }

    public function markNotEligible(
        Order $order,
        ?MlmCalculationRun $run,
        string $reason = 'Final eligible amount is below the cashback threshold.'
    ): CashbackEligibility {
        return DB::transaction(function () use ($order, $run, $reason): CashbackEligibility {
            $existing = CashbackEligibility::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $this->assertEligibilityCanChange($existing);
                $this->updateRecord($existing, $order, $run, $reason);

                return $existing->fresh(['order', 'member', 'calculationRun', 'adjustments']);
            }

            return $this->createRecord($order, $run, $reason);
        });
    }

    public function cancelDueToRefund(Order $order, ?User $createdBy = null, ?string $reason = null): ?CashbackEligibility
    {
        $refund = $order->refunds()->whereIn('status', ['approved', 'completed'])->latest('id')->first();
        if ($refund) {
            return app(CashbackRefundReconciliationService::class)->processApprovedRefund($refund, $createdBy);
        }

        return DB::transaction(function () use ($order, $createdBy, $reason): ?CashbackEligibility {
            $cashback = CashbackEligibility::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if (! $cashback) {
                return null;
            }

            if ($cashback->status !== CashbackEligibility::STATUS_CANCELLED_DUE_TO_REFUND) {
                $reason ??= 'Order was cancelled or refunded after cashback eligibility was recorded.';
                if ($cashback->status !== CashbackEligibility::STATUS_PAID) {
                    app(CashbackPayoutService::class)->releaseForCancellation($cashback);
                }
                if (MlmDecimal::isPositive((string) $cashback->maximum_cashback_amount)) {
                    $this->createRefundAdjustmentLocked($cashback, $reason, $createdBy);
                }
                $cashback->update([
                    'status' => CashbackEligibility::STATUS_CANCELLED_DUE_TO_REFUND,
                    'ineligibility_reason' => $reason,
                ]);
                $this->notifyCustomer($cashback, 'Your cashback was cancelled because the order was refunded or cancelled.');
            }

            return $cashback->fresh(['order', 'member', 'calculationRun', 'adjustments']);
        });
    }

    public function reconcileApprovedRefunds(Order $order, ?User $actor = null): ?CashbackEligibility
    {
        $cashback = CashbackEligibility::query()->where('order_id', $order->id)->first();
        if (! $cashback) {
            return null;
        }

        $result = $cashback;
        foreach ($order->refunds()->whereIn('status', ['approved', 'completed'])->orderBy('id')->get() as $refund) {
            $result = app(CashbackRefundReconciliationService::class)->processApprovedRefund($refund, $actor);
        }

        return $result?->fresh(['order', 'member', 'calculationRun', 'adjustments']);
    }

    public function createRefundAdjustment(
        CashbackEligibility $cashback,
        string $reason,
        ?User $createdBy = null
    ): CashbackAdjustment {
        return DB::transaction(function () use ($cashback, $reason, $createdBy): CashbackAdjustment {
            $locked = CashbackEligibility::query()->lockForUpdate()->findOrFail($cashback->id);

            return $this->createRefundAdjustmentLocked($locked, $reason, $createdBy);
        });
    }

    public function createReversalAdjustment(
        CashbackEligibility $cashback,
        string $reason,
        User $createdBy
    ): CashbackAdjustment {
        return DB::transaction(function () use ($cashback, $reason, $createdBy): CashbackAdjustment {
            $locked = CashbackEligibility::query()->lockForUpdate()->findOrFail($cashback->id);
            $key = hash('sha256', 'cashback-manual-reversal|'.$locked->id);
            $existing = CashbackAdjustment::query()->where('idempotency_key', $key)->first();
            if ($existing) {
                return $existing;
            }

            return CashbackAdjustment::create([
                'cashback_eligibility_id' => $locked->id,
                'order_id' => $locked->order_id,
                'member_id' => $locked->member_id,
                'adjustment_type' => CashbackAdjustment::TYPE_MANUAL_REVERSAL,
                'amount' => MlmDecimal::negate((string) $locked->maximum_cashback_amount),
                'idempotency_key' => $key,
                'reason' => trim($reason),
                'adjustment_snapshot' => [
                    'cashback_id' => $locked->id,
                    'maximum_cashback_amount' => (string) $locked->maximum_cashback_amount,
                    'reason' => trim($reason),
                ],
                'created_by' => $createdBy->id,
            ]);
        });
    }

    private function createRecord(
        Order $order,
        ?MlmCalculationRun $run,
        ?string $notEligibleReason = null
    ): CashbackEligibility {
        $member = $run->purchasingMember;
        if (! $member) {
            throw new LogicException('A cashback record requires the MLM purchasing member.');
        }

        $rule = $run->ruleVersion;
        $amount = MlmDecimal::normalize((string) $run->eligible_amount);
        $threshold = MlmDecimal::normalize(self::THRESHOLD);
        $eligible = bccomp($amount, $threshold, MlmDecimal::SCALE) >= 0;
        $maximum = $eligible ? $amount : MlmDecimal::normalize('0');
        $status = $eligible
            ? CashbackEligibility::STATUS_ELIGIBLE_AWAITING_COMPANY_PROFIT
            : CashbackEligibility::STATUS_NOT_ELIGIBLE;
        $reason = $eligible ? null : ($notEligibleReason ?? 'Final eligible amount is below ₹200.');
        $formulaVersion = $rule?->version;
        $snapshot = $this->snapshot($order, $run, $amount, $threshold, $maximum, $eligible, $formulaVersion);

        return CashbackEligibility::create([
            'order_id' => $order->id,
            'member_id' => $member->id,
            'calculation_run_id' => $run->id,
            'rule_version_id' => $rule?->id,
            'idempotency_key' => $this->idempotencyKey($order, $run),
            'final_eligible_amount' => $amount,
            'original_eligible_amount' => $amount,
            'current_eligible_amount' => $amount,
            'maximum_cashback_amount' => $maximum,
            'original_cashback_amount' => $maximum,
            'refunded_amount' => MlmDecimal::normalize('0'),
            'eligibility_threshold' => $threshold,
            'formula_version' => $formulaVersion,
            'status' => $status,
            'ineligibility_reason' => $reason,
            'eligibility_date' => now(),
            'eligibility_snapshot' => $snapshot,
        ]);
    }

    private function updateRecord(
        CashbackEligibility $cashback,
        Order $order,
        MlmCalculationRun $run,
        ?string $notEligibleReason = null
    ): void {
        $member = $run->purchasingMember;
        if (! $member) {
            throw new LogicException('A cashback record requires the MLM purchasing member.');
        }

        $rule = $run->ruleVersion;
        $amount = MlmDecimal::normalize((string) $run->eligible_amount);
        $threshold = MlmDecimal::normalize(self::THRESHOLD);
        $eligible = bccomp($amount, $threshold, MlmDecimal::SCALE) >= 0;
        $maximum = $eligible ? $amount : MlmDecimal::normalize('0');

        $cashback->update([
            'member_id' => $member->id,
            'calculation_run_id' => $run->id,
            'rule_version_id' => $rule?->id,
            'idempotency_key' => $this->idempotencyKey($order, $run),
            'final_eligible_amount' => $amount,
            'current_eligible_amount' => $amount,
            'maximum_cashback_amount' => $maximum,
            'eligibility_threshold' => $threshold,
            'formula_version' => $rule?->version,
            'status' => $eligible
                ? CashbackEligibility::STATUS_ELIGIBLE_AWAITING_COMPANY_PROFIT
                : CashbackEligibility::STATUS_NOT_ELIGIBLE,
            'ineligibility_reason' => $eligible ? null : ($notEligibleReason ?? 'Final eligible amount is below ₹200.'),
            'eligibility_date' => now(),
            'eligibility_snapshot' => $this->snapshot($order, $run, $amount, $threshold, $maximum, $eligible, $rule?->version),
        ]);
    }

    private function assertEligibilityCanChange(CashbackEligibility $cashback): void
    {
        if (! in_array($cashback->status, [
            CashbackEligibility::STATUS_NOT_ELIGIBLE,
            CashbackEligibility::STATUS_ELIGIBLE_AWAITING_COMPANY_PROFIT,
        ], true)) {
            throw new LogicException('Cashback eligibility cannot change after Admin selection or payment processing.');
        }
    }

    private function notifyCustomer(CashbackEligibility $cashback, string $message): void
    {
        $cashback->loadMissing('member.user');
        $cashback->member?->user?->notify(new SystemAlertNotification(
            'Cashback update',
            $message,
            'info',
            route('cashback.show', $cashback),
            ['cashback_id' => $cashback->id, 'status' => $cashback->status]
        ));
    }

    private function createRefundAdjustmentLocked(
        CashbackEligibility $cashback,
        string $reason,
        ?User $createdBy = null
    ): CashbackAdjustment {
        $key = hash('sha256', 'cashback-refund|'.$cashback->id);
        $existing = CashbackAdjustment::query()->where('idempotency_key', $key)->first();
        if ($existing) {
            return $existing;
        }

        $amount = MlmDecimal::negate((string) $cashback->maximum_cashback_amount);

        return CashbackAdjustment::create([
            'cashback_eligibility_id' => $cashback->id,
            'order_id' => $cashback->order_id,
            'member_id' => $cashback->member_id,
            'adjustment_type' => CashbackAdjustment::TYPE_REFUND_RECOVERY,
            'amount' => $amount,
            'idempotency_key' => $key,
            'reason' => trim($reason),
            'adjustment_snapshot' => [
                'cashback_id' => $cashback->id,
                'maximum_cashback_amount' => (string) $cashback->maximum_cashback_amount,
                'adjustment_amount' => $amount,
            ],
            'created_by' => $createdBy?->id,
        ]);
    }

    private function snapshot(
        Order $order,
        MlmCalculationRun $run,
        string $amount,
        string $threshold,
        string $maximum,
        bool $eligible,
        ?string $formulaVersion
    ): array {
        return [
            'order_id' => $order->id,
            'calculation_run_id' => $run->id,
            'final_eligible_amount' => $amount,
            'eligibility_threshold' => $threshold,
            'maximum_cashback_amount' => $maximum,
            'eligible' => $eligible,
            'gst_included' => false,
            'formula_version' => $formulaVersion,
            'source' => 'mlm_calculation_run',
            'calculated_at' => now()->toDateTimeString(),
        ];
    }

    private function idempotencyKey(Order $order, MlmCalculationRun $run): string
    {
        return hash('sha256', 'cashback|order:'.$order->id.'|calculation:'.$run->id);
    }
}
