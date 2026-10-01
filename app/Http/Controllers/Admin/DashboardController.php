<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashbackEligibility;
use App\Models\Member;
use App\Models\MlmCalculationRun;
use App\Models\MlmIncomeLedger;
use App\Models\MlmPayoutCycle;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $currentWeekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $currentWeekEnd = now()->endOfWeek(Carbon::SUNDAY)->toDateString();
        $currentCycle = MlmPayoutCycle::whereDate('period_start', $currentWeekStart)->first();

        $totalMembers = Member::count();
        $activeMembers = Member::where('status', Member::STATUS_ACTIVE)->count();
        $pendingMembers = Member::where('status', Member::STATUS_PENDING)->count();
        $blockedMembers = Member::where('status', Member::STATUS_BLOCKED)->count();
        $inactiveMembers = Member::where('status', Member::STATUS_INACTIVE)->count();

        $kycPending = Member::where('kyc_status', Member::KYC_PENDING)->count();
        $kycUnderReview = Member::where('kyc_status', Member::KYC_UNDER_REVIEW)->count();
        $kycApproved = Member::where('kyc_status', Member::KYC_APPROVED)->count();
        $kycRejected = Member::where('kyc_status', Member::KYC_REJECTED)->count();

        $totalPv = (float) MlmIncomeLedger::where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('pv');
        $totalDirectSellingIncome = (float) MlmIncomeLedger::where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('calculated_amount');
        
        $pendingPayout = (float) MlmPayoutCycle::whereIn('status', [MlmPayoutCycle::STATUS_PENDING_CALCULATION, MlmPayoutCycle::STATUS_PENDING_APPROVAL])->sum('net_payable');
        $approvedPayout = (float) MlmPayoutCycle::where('status', MlmPayoutCycle::STATUS_APPROVED)->sum('net_payable');
        $paidPayout = (float) MlmPayoutCycle::where('status', MlmPayoutCycle::STATUS_PAID)->sum('net_payable');

        $cashbackEligibleCount = CashbackEligibility::count();
        $cashbackEligibleAmount = (float) CashbackEligibility::sum('eligible_amount');
        $cashbackPendingAmount = (float) CashbackEligibility::where('status', 'eligible_awaiting_profit')->sum('eligible_amount');
        $cashbackPaidAmount = (float) CashbackEligibility::where('status', 'paid')->sum('eligible_amount');

        $stats = [
            'total_members' => $totalMembers,
            'active_members' => $activeMembers,
            'pending_members' => $pendingMembers,
            'blocked_members' => $blockedMembers,
            'inactive_members' => $inactiveMembers,

            'kyc_pending' => $kycPending,
            'kyc_under_review' => $kycUnderReview,
            'kyc_approved' => $kycApproved,
            'kyc_rejected' => $kycRejected,

            'total_network_pv' => round($totalPv, 4),
            'total_direct_selling_income' => round($totalDirectSellingIncome, 2),
            'pending_payout' => round($pendingPayout, 2),
            'approved_payout' => round($approvedPayout, 2),
            'paid_payout' => round($paidPayout, 2),

            'cashback_eligible_count' => $cashbackEligibleCount,
            'cashback_eligible_amount' => round($cashbackEligibleAmount, 2),
            'cashback_pending_amount' => round($cashbackPendingAmount, 2),
            'cashback_paid_amount' => round($cashbackPaidAmount, 2),

            'current_weekly_cycle' => $currentCycle ? $currentCycle->cycle_reference : 'Week '.now()->weekOfYear.' ('.now()->format('M d').' - '.now()->endOfWeek()->format('M d').')',
            'current_cycle_status' => $currentCycle ? $currentCycle->status : 'in_progress',

            'total_customers' => User::where('role_id', function ($q) {
                $q->select('id')->from('roles')->where('name', 'Customer');
            })->count(),
            'total_orders' => Order::count(),
            'total_sales' => Order::sum('total') ?? 0,
        ];

        // A. Member Growth Timeline (Last 6 Months)
        $memberGrowth = [];
        $now = Carbon::now();
        for ($i = 5; $i >= 0; $i--) {
            $mDate = $now->copy()->subMonths($i);
            $mStart = $mDate->copy()->startOfMonth();
            $mEnd = $mDate->copy()->endOfMonth();
            $mCount = Member::whereBetween('joined_at', [$mStart, $mEnd])->count();
            $memberGrowth[] = [
                'label' => $mDate->format('M Y'),
                'count' => $mCount,
            ];
        }

        // B. Network Level Distribution (Levels 0–19)
        $parentMap = Member::query()->pluck('placement_parent_id', 'id')->all();
        $statuses = Member::query()->pluck('status', 'id')->all();
        $levelCounts = array_fill(0, 20, 0);
        $levelActiveCounts = array_fill(0, 20, 0);

        foreach ($parentMap as $id => $parentId) {
            $lvl = 0;
            $curr = $parentId;
            $visited = [$id => true];
            while ($curr !== null && !isset($visited[$curr]) && $lvl < 20) {
                $lvl++;
                $visited[$curr] = true;
                $curr = $parentMap[$curr] ?? null;
            }
            $finalLevel = min($lvl, 19);
            $levelCounts[$finalLevel]++;
            if (($statuses[$id] ?? null) === Member::STATUS_ACTIVE) {
                $levelActiveCounts[$finalLevel]++;
            }
        }

        $levelsDistribution = [];
        for ($i = 0; $i < 20; $i++) {
            $levelsDistribution[] = [
                'level' => $i,
                'tier' => $i <= 7 ? 'High PV (13.5)' : 'Low PV (0.75)',
                'count' => $levelCounts[$i],
                'active' => $levelActiveCounts[$i],
            ];
        }

        // C. Income by Level (Levels 0–19)
        $ledgerStats = MlmIncomeLedger::query()
            ->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)
            ->whereBetween('level', [0, 19])
            ->select('level', DB::raw('COUNT(*) as tx_count'), DB::raw('COUNT(DISTINCT member_id) as member_count'), DB::raw('SUM(pv) as total_pv'), DB::raw('SUM(calculated_amount) as total_income'))
            ->groupBy('level')
            ->get()
            ->keyBy('level');

        $incomeByLevel = [];
        for ($i = 0; $i < 20; $i++) {
            $row = $ledgerStats->get($i);
            $incomeByLevel[] = [
                'level' => $i,
                'tier' => $i <= 7 ? 'High PV (13.5)' : 'Low PV (0.75)',
                'pv' => $row ? round((float) $row->total_pv, 4) : 0.0,
                'income' => $row ? (int) round((float) $row->total_income) : 0,
                'member_count' => $row ? (int) $row->member_count : 0,
            ];
        }

        // D. Recent Calculations & Payout Cycles
        $recentCalculations = MlmCalculationRun::with(['purchasingMember.user', 'ruleVersion'])
            ->latest('processed_at')
            ->limit(6)
            ->get();

        $recentPayoutCycles = MlmPayoutCycle::withCount('lines')
            ->latest('period_start')
            ->limit(5)
            ->get();

        // E. Pending Actions
        $pendingActions = [
            'kyc_pending_count' => $kycPending + $kycUnderReview,
            'payout_approval_pending' => MlmPayoutCycle::where('status', MlmPayoutCycle::STATUS_PENDING_APPROVAL)->count(),
            'cashback_awaiting_profit_count' => CashbackEligibility::where('status', 'eligible_awaiting_profit')->count(),
            'reconciliation_issues_count' => Order::where('pay_status', 'paid')->whereDoesntHave('mlmCalculationRuns')->whereHas('user.mlmMember')->count(),
        ];

        return view('admin.dashboard.index', compact(
            'stats',
            'memberGrowth',
            'levelsDistribution',
            'incomeByLevel',
            'recentCalculations',
            'recentPayoutCycles',
            'pendingActions'
        ));
    }
}
