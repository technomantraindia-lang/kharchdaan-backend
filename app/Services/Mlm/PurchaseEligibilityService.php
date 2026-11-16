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
