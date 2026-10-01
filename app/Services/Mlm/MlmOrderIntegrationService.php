<?php

namespace App\Services\Mlm;

use App\Models\Member;
use App\Models\MlmCalculationRule;
use App\Models\MlmCalculationRun;
use App\Models\Order;
use App\Models\Refund;
use App\Services\Cashback\CashbackEligibilityService;
use App\Support\MlmDecimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class MlmOrderIntegrationService
{
    public function __construct(
        private readonly MlmCalculationService $calculationService,
        private readonly CashbackEligibilityService $cashbackService
    ) {}

    public function process(Order $order): ?MlmCalculationRun
    {
        return DB::transaction(fn (): ?MlmCalculationRun => $this->processLocked($order));
    }

    private function processLocked(Order $order): ?MlmCalculationRun
    {
        $order = Order::query()->lockForUpdate()->findOrFail($order->id);
        $order->load(['user.mlmMember', 'payment', 'statusHistory']);

        $existing = MlmCalculationRun::query()->where('order_id', $order->id)->first();
        $approvedRefund = $this->approvedRefundTotal($order);
        $currentEligibleAmount = $this->eligibleAmount($order, $approvedRefund);
        $fullyReversed = in_array($order->status, ['cancelled', 'refunded'], true)
            || ! MlmDecimal::isPositive($currentEligibleAmount);

        if ($existing && $existing->status !== 'failed') {
            if (MlmDecimal::isPositive($approvedRefund)) {
                $existing = $this->calculationService->reconcileApprovedRefunds($existing);
            }

            if ($order->status === 'cancelled' && $existing->status !== 'reversed') {
                $existing = $this->calculationService->reverseCalculation(
                    $existing,
                    null,
                    'Order cancelled after MLM calculation.'
                );
            }

            if ($existing->status === 'reversed' || $fullyReversed) {
                $this->markOrder($order, Order::MLM_REVERSED);
                $order->update(['mlm_eligible_amount' => $currentEligibleAmount, 'mlm_reversal_status' => 'reversed']);
                if (MlmDecimal::isPositive($approvedRefund)) {
                    $this->cashbackService->reconcileApprovedRefunds($order);
                } else {
                    $this->cashbackService->cancelDueToRefund($order);
                }
            } else {
                $this->markOrder($order, Order::MLM_COMPLETED);
                $order->update([
                    'mlm_eligible_amount' => $currentEligibleAmount,
                    'mlm_reversal_status' => MlmDecimal::isPositive($approvedRefund) ? 'partially_reversed' : 'not_applicable',
                ]);
                if (MlmDecimal::isPositive($approvedRefund)) {
                    $this->cashbackService->reconcileApprovedRefunds($order);
                } else {
                    $this->cashbackService->createEligibility($order, $existing);
                }
            }

            return $existing->fresh(['incomeLedgers', 'audits']);
        }

        if ($fullyReversed) {
            $this->markOrder($order, Order::MLM_NOT_ELIGIBLE, 'Order was cancelled or fully refunded before MLM calculation.');

            return null;
        }

        $deliveredAt = $order->statusHistory
            ->where('status', 'delivered')
            ->sortByDesc('created_at')
            ->first()?->created_at
            ?? $order->updated_at
            ?? $order->created_at
            ?? now();

        if ($order->status !== 'delivered') {
            $this->markOrder($order, Order::MLM_NOT_ELIGIBLE, 'The order must have delivered status.');

            return null;
        }

        if ($order->pay_status !== 'paid' || ! $order->payment || $order->payment->status !== 'paid') {
            $this->markOrder($order, Order::MLM_WAITING_PAYMENT, 'A verified paid payment is required.');

            return null;
        }

        $deliveredAt = Carbon::parse($deliveredAt);

        $member = $order->user?->mlmMember;
        if (! $member || $member->status !== Member::STATUS_ACTIVE) {
            return $this->failOrder($order, 'The order customer is not mapped to an active MLM member.', $member);
        }

        $eligibleAmount = $currentEligibleAmount;
        $order->update([
            'mlm_processing_status' => Order::MLM_PROCESSING,
            'mlm_pv_processing_status' => Order::MLM_PROCESSING,
            'mlm_eligible_amount' => $eligibleAmount,
            'mlm_integration_reference' => $this->integrationReference($order),
            'mlm_error_message' => null,
        ]);

        if (! MlmDecimal::isPositive($eligibleAmount)) {
            $this->markOrder($order, Order::MLM_NOT_ELIGIBLE, 'The net eligible amount is zero.');

            return null;
        }

        $rule = $this->ruleFor($deliveredAt);
        if (! $rule) {
            return $this->failOrder($order, 'No active MLM calculation rule is effective for the delivered date.', $member, $eligibleAmount);
        }

        try {
            $run = $this->calculationService->calculate(
                $member,
                $eligibleAmount,
                $this->transactionReference($order),
                $deliveredAt,
                $rule,
                null,
                $order
            );

            $order->update([
                'mlm_processing_status' => Order::MLM_COMPLETED,
                'mlm_pv_processing_status' => Order::MLM_COMPLETED,
                'mlm_formula_version' => $rule->version,
                'mlm_reversal_status' => 'not_applicable',
                'mlm_error_message' => null,
                'mlm_processed_at' => now(),
            ]);
            $this->cashbackService->createEligibility($order, $run);

            return $run;
        } catch (Throwable $exception) {
            return $this->failOrder($order, $exception->getMessage(), $member, $eligibleAmount, $rule);
        }
    }

    public function retry(Order $order): ?MlmCalculationRun
    {
        $order->update([
            'mlm_processing_status' => Order::MLM_NOT_STARTED,
            'mlm_pv_processing_status' => Order::MLM_NOT_STARTED,
            'mlm_error_message' => null,
        ]);

        return $this->process($order->fresh());
    }

    private function eligibleAmount(Order $order, string $approvedRefund): string
    {
        $subtotal = MlmDecimal::normalize((string) $order->subtotal);
        $discount = MlmDecimal::normalize((string) ($order->discount ?? '0'));
        $net = MlmDecimal::subtract($subtotal, $discount);
        $net = MlmDecimal::subtract($net, $approvedRefund);

        return bccomp($net, '0', MlmDecimal::SCALE) > 0
            ? $net
            : MlmDecimal::normalize('0');
    }

    private function approvedRefundTotal(Order $order): string
    {
        return $order->refunds()
            ->whereIn('status', ['approved', 'completed'])
            ->get()
            ->reduce(fn (string $total, Refund $refund): string => MlmDecimal::add($total, MlmDecimal::normalize((string) $refund->amount)), MlmDecimal::normalize('0'));
    }

    private function ruleFor(Carbon $date): ?MlmCalculationRule
    {
        return MlmCalculationRule::query()
            ->where('status', 'active')
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date->toDateString());
            })
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date->toDateString());
            })
            ->orderByDesc('effective_from')
            ->first();
    }

    private function integrationReference(Order $order): string
    {
        return 'order:'.$order->id.':mlm:v1';
    }

    private function transactionReference(Order $order): string
    {
        return substr('order:'.($order->order_num ?: $order->id), 0, 150);
    }

    private function markOrder(Order $order, string $status, ?string $error = null): void
    {
        $order->update([
            'mlm_processing_status' => $status,
            'mlm_error_message' => $error,
            'mlm_processed_at' => in_array($status, [Order::MLM_COMPLETED, Order::MLM_REVERSED, Order::MLM_NOT_ELIGIBLE], true) ? now() : null,
        ]);
    }

    private function failOrder(
        Order $order,
        string $message,
        ?Member $member = null,
        ?string $eligibleAmount = null,
        ?MlmCalculationRule $rule = null
    ): ?MlmCalculationRun {
        $message = substr($message ?: 'MLM order calculation failed.', 0, 65535);
        $order->update([
            'mlm_processing_status' => Order::MLM_FAILED,
            'mlm_pv_processing_status' => Order::MLM_FAILED,
            'mlm_error_message' => $message,
        ]);

        if (! $member || ! $rule || ! $eligibleAmount) {
            return null;
        }

        $reference = $this->transactionReference($order);
        $run = MlmCalculationRun::query()->where('order_id', $order->id)->first();
        if ($run && $run->status !== 'failed') {
            return $run;
        }

        $run ??= new MlmCalculationRun(['order_id' => $order->id]);
        $run->fill([
            'purchasing_member_id' => $member->id,
            'rule_version_id' => $rule->id,
            'source_transaction_reference' => $reference,
            'idempotency_key' => hash('sha256', $member->id.'|'.$reference),
            'transaction_date' => now(),
            'eligible_amount' => $eligibleAmount,
            'status' => 'failed',
            'placement_path_snapshot' => [],
            'processing_started_at' => now(),
            'processed_at' => now(),
            'error_message' => $message,
        ]);
        $run->save();

        $run->audits()->create([
            'event' => 'calculation_failed',
            'payload' => ['order_id' => $order->id, 'error' => $message],
        ]);

        return $run;
    }
}
