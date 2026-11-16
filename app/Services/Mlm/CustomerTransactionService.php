<?php

namespace App\Services\Mlm;

use App\Models\CashbackEligibility;
use App\Models\Member;
use App\Models\Order;
use App\Models\User;
use App\Support\MlmDecimal;
use Illuminate\Support\Collection;

class CustomerTransactionService
{
    public function __construct(private readonly PurchaseEligibilityService $eligibility) {}

    public function forUser(User $user, array $filters = []): array
    {
        $member = Member::query()->where('user_id', $user->id)->first();
        $query = Order::query()
            ->where('user_id', $user->id)
            ->with(['cashbackEligibility'])
            ->latest('id');

        if (! empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $query->where(function ($builder) use ($q) {
                $builder->where('order_num', 'like', '%'.$q.'%')
                    ->orWhere('status', 'like', '%'.$q.'%')
                    ->orWhere('pay_status', 'like', '%'.$q.'%');
            });
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $orders = $query->limit(200)->get();
        $dsEligible = $member ? $this->eligibility->isDirectSellingEligible($member) : false;
        $referralEligible = $member ? $this->eligibility->isReferralMonthEligible($member) : false;
        $monthTotal = $member ? $this->eligibility->memberMonthlyPurchaseTotal($member) : '0';

        $rows = $orders->map(function (Order $order) use ($dsEligible, $referralEligible, $member) {
            $cashback = $order->relationLoaded('cashbackEligibility')
                ? $order->cashbackEligibility
                : CashbackEligibility::query()->where('order_id', $order->id)->first();

            $amount = MlmDecimal::normalize((string) $order->total);
            $meets200 = bccomp($amount, '200', 2) >= 0;

            $cashbackEligible = $cashback
                ? ! in_array($cashback->status, [
                    CashbackEligibility::STATUS_NOT_ELIGIBLE,
                    CashbackEligibility::STATUS_CANCELLED_DUE_TO_REFUND,
                ], true)
                : $meets200;

            $referralStatus = $meets200 && $referralEligible;
            $dsStatus = $dsEligible;

            $anyIneligible = ! $cashbackEligible || ! $referralStatus || ! $dsStatus;

            return [
                'order_id' => $order->id,
                'transaction_id' => $order->order_num,
                'date' => $order->created_at?->toDateString(),
                'amount' => $amount,
                'status' => $order->status,
                'pay_status' => $order->pay_status,
                'channel' => $order->order_channel ?? 'online',
                'cashback' => [
                    'eligible' => $cashbackEligible,
                    'raw_amount' => $cashback?->original_eligible_amount ?? $amount,
                    'rounded_amount' => $cashback?->maximum_cashback_amount
                        ?? $cashback?->current_eligible_amount
                        ?? ($cashbackEligible ? MlmDecimal::roundUpToNextTen($amount) : '0'),
                    'customer_cashback' => $cashback?->maximum_cashback_amount
                        ?? $cashback?->current_eligible_amount
                        ?? ($cashbackEligible ? MlmDecimal::roundUpToNextTen($amount) : '0'),
                    'sponsor_cashback' => $cashback?->sponsor_cashback_amount
                        ?? ($cashbackEligible ? MlmDecimal::roundUpToNextTen($amount) : '0'),
                    'amount' => $cashback?->maximum_cashback_amount
                        ?? $cashback?->current_eligible_amount
                        ?? ($cashbackEligible ? MlmDecimal::roundUpToNextTen($amount) : '0'),
                    'status' => $cashback?->status ?? ($cashbackEligible ? 'pending_criteria' : 'not_eligible'),
                    'label' => $cashbackEligible ? 'Eligible / Conditional' : 'Not Eligible',
                ],
                'referral_income' => [
                    'eligible' => $referralStatus,
                    'label' => $referralStatus ? 'Month eligible (structure)' : 'Not Eligible this month',
                    'reason' => $referralStatus
                        ? null
                        : (! $meets200
                            ? 'Transaction below ₹200'
                            : 'Sponsor/self needs min ₹200 qualifying purchase this month'),
                ],
                'direct_selling' => [
                    'eligible' => $dsStatus,
                    'label' => $dsStatus ? 'DS eligible this month' : 'Not Eligible (need ₹1000 monthly)',
                ],
                'highlight_red' => $anyIneligible,
                'member_id' => $member?->id,
            ];
        })->values();

        $monthlyVolume = $orders
            ->groupBy(fn (Order $o) => $o->created_at?->format('Y-m') ?? 'unknown')
            ->map(fn (Collection $group, string $month) => [
                'month' => $month,
                'count' => $group->count(),
                'volume' => MlmDecimal::normalize((string) $group->sum(fn (Order $o) => (float) $o->total)),
            ])
            ->sortKeys()
            ->values()
            ->take(-6)
            ->values()
            ->all();

        $cashbackTotal = $rows->sum(fn ($r) => (float) ($r['cashback']['customer_cashback'] ?? 0));

        return [
            'summary' => [
                'month_purchase_total' => $monthTotal,
                'ds_eligible' => $dsEligible,
                'referral_month_eligible' => $referralEligible,
                'ds_minimum' => PurchaseEligibilityService::DS_MONTHLY_MINIMUM,
                'referral_min_transaction' => PurchaseEligibilityService::REFERRAL_MIN_TRANSACTION,
                'cashback_total_rounded' => MlmDecimal::normalize((string) $cashbackTotal),
                'order_count' => $rows->count(),
                'monthly_volume' => $monthlyVolume,
            ],
            'transactions' => $rows->all(),
        ];
    }
}
