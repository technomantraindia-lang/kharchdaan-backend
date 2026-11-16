<?php

namespace App\Services\Mlm;

use App\Models\Member;
use App\Models\Order;
use App\Support\MlmDecimal;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class PurchaseEligibilityService
{
    public const DS_MONTHLY_MINIMUM = '1000';

    public const REFERRAL_MIN_TRANSACTION = '200';

    /** Client: 100% referral (overridable via config/mlm.php). */
    public const REFERRAL_RATE_DEFAULT = '1.00';

    public function memberMonthlyPurchaseTotal(Member $member, ?CarbonInterface $month = null): string
    {
        $month = $month ? Carbon::instance($month)->startOfMonth() : now()->startOfMonth();
        $userId = $member->user_id;
        if (! $userId) {
            return MlmDecimal::normalize('0');
        }

        $total = Order::query()
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->where(function ($query) {
                $query->whereIn('pay_status', ['paid', 'completed'])
                    ->orWhereIn('status', ['delivered', 'completed', 'paid']);
            })
            ->sum('total');

        return MlmDecimal::normalize((string) $total);
    }

    public function isDirectSellingEligible(Member $member, ?CarbonInterface $month = null): bool
    {
        $total = $this->memberMonthlyPurchaseTotal($member, $month);

        return bccomp($total, MlmDecimal::normalize(self::DS_MONTHLY_MINIMUM), MlmDecimal::SCALE) >= 0;
    }

    public function hasQualifyingReferralPurchase(Member $member, ?CarbonInterface $month = null): bool
    {
        $month = $month ? Carbon::instance($month)->startOfMonth() : now()->startOfMonth();
        $userId = $member->user_id;
        if (! $userId) {
            return false;
        }

        $threshold = MlmDecimal::normalize(self::REFERRAL_MIN_TRANSACTION);

        return Order::query()
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->where(function ($query) {
                $query->whereIn('pay_status', ['paid', 'completed'])
                    ->orWhereIn('status', ['delivered', 'completed', 'paid']);
            })
            ->where('total', '>=', (float) $threshold)
            ->exists();
    }

    public function isReferralMonthEligible(Member $sponsor, ?CarbonInterface $month = null): bool
    {
        return $this->hasQualifyingReferralPurchase($sponsor, $month);
    }

    public function referralRate(): string
    {
        return MlmDecimal::normalize((string) config('mlm.referral_rate', self::REFERRAL_RATE_DEFAULT), 4);
    }

    /**
     * 100% of qualifying amount, then round up to next ₹10 (client cashback-style rounding).
     */
    public function referralIncomeFromAmount(string $purchaseAmount): string
    {
        $amount = MlmDecimal::normalize($purchaseAmount);
        $threshold = MlmDecimal::normalize(self::REFERRAL_MIN_TRANSACTION);
        if (bccomp($amount, $threshold, MlmDecimal::SCALE) < 0) {
            return MlmDecimal::normalize('0');
        }

        $raw = MlmDecimal::multiply($amount, $this->referralRate());

        return MlmDecimal::roundUpToNextTen($raw);
    }

    /**
     * Sum 100% referral income from sponsored members' qualifying paid orders in a window.
     */
    public function sponsorReferralIncome(
        Member $sponsor,
        CarbonInterface $from,
        CarbonInterface $to,
        ?CarbonInterface $eligibilityMonth = null
    ): string {
        if (! $this->isReferralMonthEligible($sponsor, $eligibilityMonth ?? $from)) {
            return MlmDecimal::normalize('0');
        }

        $downlineUserIds = Member::query()
            ->realDistributors()
            ->where('sponsor_member_id', $sponsor->id)
            ->whereNotNull('user_id')
            ->pluck('user_id');

        if ($downlineUserIds->isEmpty()) {
            return MlmDecimal::normalize('0');
        }

        $threshold = (float) self::REFERRAL_MIN_TRANSACTION;
        $orders = Order::query()
            ->whereIn('user_id', $downlineUserIds)
            ->whereBetween('created_at', [
                Carbon::instance($from)->startOfDay(),
                Carbon::instance($to)->endOfDay(),
            ])
            ->where(function ($query) {
                $query->whereIn('pay_status', ['paid', 'completed'])
                    ->orWhereIn('status', ['delivered', 'completed', 'paid']);
            })
            ->where('total', '>=', $threshold)
            ->get(['total']);

        return $orders->reduce(
            fn (string $sum, Order $order): string => MlmDecimal::add(
                $sum,
                $this->referralIncomeFromAmount((string) $order->total)
            ),
            MlmDecimal::normalize('0')
        );
    }

    public function memberHasMinPurchase(Member $member, string $minimum = self::REFERRAL_MIN_TRANSACTION): bool
    {
        $userId = $member->user_id;
        if (! $userId) {
            return false;
        }

        $threshold = MlmDecimal::normalize($minimum);

        return Order::query()
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->whereIn('pay_status', ['paid', 'completed'])
                    ->orWhereIn('status', ['delivered', 'completed', 'paid']);
            })
            ->where('total', '>=', (float) $threshold)
            ->exists();
    }
}
