<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Order;
use App\Services\Mlm\PurchaseEligibilityService;
use App\Support\MlmDecimal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Referral eligibility + 100% referral income amounts (client-confirmed rate).
 */
class MlmReferralEligibilityController extends Controller
{
    public function __construct(private readonly PurchaseEligibilityService $eligibility) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'week_start' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
            'eligible_only' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $month = ! empty($filters['month'])
            ? Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth()
            : now()->startOfMonth();

        $weekStart = ! empty($filters['week_start'])
            ? Carbon::parse($filters['week_start'])->startOfDay()
            : $month->copy()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $threshold = MlmDecimal::normalize(PurchaseEligibilityService::REFERRAL_MIN_TRANSACTION);

        $sponsors = Member::query()
            ->realDistributors()
            ->with('user')
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $q = trim($filters['q']);
                $query->whereHas('user', function ($uq) use ($q) {
                    $uq->where('name', 'like', "%{$q}%")
                        ->orWhere('mlm_member_id', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->whereHas('sponsoredMembers')
            ->orderBy('id')
            ->limit(300)
            ->get();

        $rows = $sponsors->map(function (Member $sponsor) use ($month, $weekStart, $weekEnd, $threshold) {
            $monthEligible = $this->eligibility->isReferralMonthEligible($sponsor, $month);
            $monthTotal = $this->eligibility->memberMonthlyPurchaseTotal($sponsor, $month);

            $weekTxnCount = 0;
            $weekVolume = '0';
            if ($sponsor->user_id) {
                $weekOrders = Order::query()
                    ->where('user_id', $sponsor->user_id)
                    ->whereBetween('created_at', [$weekStart, $weekEnd])
                    ->where(function ($query) {
                        $query->whereIn('pay_status', ['paid', 'completed'])
                            ->orWhereIn('status', ['delivered', 'completed', 'paid']);
                    })
                    ->get(['total']);
                $weekTxnCount = $weekOrders->count();
                $weekVolume = $weekOrders->reduce(
                    fn (string $sum, Order $o): string => MlmDecimal::add($sum, (string) $o->total),
                    MlmDecimal::normalize('0')
                );
            }

            $downlineRefs = Member::query()
                ->realDistributors()
                ->where('sponsor_member_id', $sponsor->id)
                ->whereBetween('joined_at', [$weekStart, $weekEnd])
                ->count();

            $weekReferralIncome = $this->eligibility->sponsorReferralIncome(
                $sponsor,
                $weekStart,
                $weekEnd,
                $month
            );
            $monthReferralIncome = $this->eligibility->sponsorReferralIncome(
                $sponsor,
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
                $month
            );

            return [
                'sponsor_id' => $sponsor->id,
                'customer_id' => $sponsor->customer_id,
                'name' => $sponsor->user?->name,
                'phone' => $sponsor->user?->phone,
                'month' => $month->format('Y-m'),
                'month_purchase_total' => $monthTotal,
                'referral_month_eligible' => $monthEligible,
                'referral_rate_percent' => (float) $this->eligibility->referralRate() * 100,
                'week_referral_income' => $weekReferralIncome,
                'month_referral_income' => $monthReferralIncome,
                'status_label' => $monthEligible ? 'Eligible (100% referral)' : 'Not Eligible',
                'reason' => $monthEligible
                    ? 'Has ≥ ₹'.$threshold.' qualifying purchase this month — 100% of eligible downline txns (rounded)'
                    : 'Needs ≥ ₹'.$threshold.' own qualifying purchase this month',
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
                'week_txn_count' => $weekTxnCount,
                'week_volume' => $weekVolume,
                'week_new_referrals' => $downlineRefs,
                'highlight_red' => ! $monthEligible,
            ];
        });

        if (! empty($filters['eligible_only'])) {
            $rows = $rows->where('referral_month_eligible', true)->values();
        }

        return view('admin.mlm.referral-eligibility.index', [
            'rows' => $rows,
            'filters' => [
                'week_start' => $weekStart->toDateString(),
                'month' => $month->format('Y-m'),
                'eligible_only' => (bool) ($filters['eligible_only'] ?? false),
                'q' => $filters['q'] ?? '',
            ],
            'threshold' => $threshold,
            'referralRatePercent' => (float) $this->eligibility->referralRate() * 100,
        ]);
    }
}
