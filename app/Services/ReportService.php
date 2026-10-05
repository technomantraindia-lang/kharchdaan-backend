<?php

namespace App\Services;

use App\Models\CashbackEligibility;
use App\Models\CashbackPayoutBatch;
use App\Models\CashbackProfitPool;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Inquiry;
use App\Models\Member;
use App\Models\MlmCalculationRun;
use App\Models\MlmIncomeLedger;
use App\Models\MlmPayoutCycle;
use App\Models\MlmPayoutLine;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\OrderReturn;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Resolve start and end Carbon dates from request input.
     */
    public function resolveDateRange(?string $preset, ?string $dateFrom, ?string $dateTo): array
    {
        $now = Carbon::now();

        switch ($preset) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                break;

            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end = $now->copy()->subDay()->endOfDay();
                break;

            case 'last_7_days':
                $start = $now->copy()->subDays(6)->startOfDay();
                $end = $now->copy()->endOfDay();
                break;

            case 'last_30_days':
                $start = $now->copy()->subDays(29)->startOfDay();
                $end = $now->copy()->endOfDay();
                break;

            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
                break;

            case 'this_year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                break;

            case 'custom':
                $start = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : $now->copy()->startOfMonth();
                $end = $dateTo ? Carbon::parse($dateTo)->endOfDay() : $now->copy()->endOfDay();
                if ($start->greaterThan($end)) {
                    $tmp = $start;
                    $start = $end->copy()->startOfDay();
                    $end = $tmp->copy()->endOfDay();
                }
                break;

            case 'this_month':
            default:
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfDay();
                $preset = 'this_month';
                break;
        }

        return [
            'preset' => $preset,
            'start' => $start,
            'end' => $end,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
        ];
    }

    /**
     * Compute Overview Statistics and chart datasets for the date range.
     */
    public function getOverviewReport(array $range): array
    {
        $start = $range['start'];
        $end = $range['end'];
        $customerRoleId = Role::where('name', 'Customer')->value('id');

        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        // Revenue Metrics (All-time, Range, This Month, Today)
        $totalRevenue = (float) Order::where('pay_status', 'paid')->sum('total');
        $rangeRevenue = (float) Order::where('pay_status', 'paid')->whereBetween('created_at', [$start, $end])->sum('total');
        $monthRevenue = (float) Order::where('pay_status', 'paid')->whereBetween('created_at', [$monthStart, $monthEnd])->sum('total');
        $todayRevenue = (float) Order::where('pay_status', 'paid')->whereBetween('created_at', [$todayStart, $todayEnd])->sum('total');

        // Order Counts
        $totalOrders = (int) Order::count();
        $rangeOrders = (int) Order::whereBetween('created_at', [$start, $end])->count();
        $monthOrders = (int) Order::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $todayOrders = (int) Order::whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $deliveredOrders = (int) Order::whereBetween('created_at', [$start, $end])->where('status', 'delivered')->count();
        $pendingOrders = (int) Order::whereBetween('created_at', [$start, $end])->where('status', 'pending')->count();
        $cancelledOrders = (int) Order::whereBetween('created_at', [$start, $end])->where('status', 'cancelled')->count();

        // Customer & Product Counts
        $totalCustomers = (int) User::where('role_id', $customerRoleId)->count();
        $rangeCustomers = (int) User::where('role_id', $customerRoleId)->whereBetween('created_at', [$start, $end])->count();
        $totalProducts = (int) Product::count();
        $lowStockCount = (int) Product::whereColumn('stock_qty', '<=', 'low_stock_qty')->count();
        $pendingInquiries = (int) Inquiry::where('status', 'pending')->count();

        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0.0;

        // Direct Selling & Network Metrics
        $totalMembers = (int) Member::count();
        $activeMembers = (int) Member::where('status', Member::STATUS_ACTIVE)->count();
        $newMembers = (int) Member::whereBetween('joined_at', [$start, $end])->count();
        $totalPv = (float) MlmIncomeLedger::where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('pv');
        $totalDirectSellingIncome = (float) MlmIncomeLedger::where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('calculated_amount');
        $pendingPayouts = (float) MlmPayoutCycle::whereIn('status', [MlmPayoutCycle::STATUS_PENDING_CALCULATION, MlmPayoutCycle::STATUS_PENDING_APPROVAL])->sum('net_payable');
        $paidPayouts = (float) MlmPayoutCycle::where('status', MlmPayoutCycle::STATUS_PAID)->sum('net_payable');

        // Cashback Metrics
        $cashbackEligibleCount = (int) CashbackEligibility::count();
        $cashbackEligibleAmount = (float) CashbackEligibility::sum('eligible_amount');
        $cashbackPaidAmount = (float) CashbackEligibility::where('status', 'paid')->sum('eligible_amount');

        // Chart Data 1: Monthly Sales (Last 6 Months)
        $monthlySales = [];
        for ($i = 5; $i >= 0; $i--) {
            $mDate = $now->copy()->subMonths($i);
            $mStart = $mDate->copy()->startOfMonth();
            $mEnd = $mDate->copy()->endOfMonth();
            $mTotal = (float) Order::where('pay_status', 'paid')->whereBetween('created_at', [$mStart, $mEnd])->sum('total');
            $monthlySales[] = [
                'label' => $mDate->format('M Y'),
                'total' => round($mTotal, 2),
            ];
        }

        // Chart Data 2: Daily Sales (Last 7 Days)
        $dailySales = [];
        for ($i = 6; $i >= 0; $i--) {
            $dDate = $now->copy()->subDays($i);
            $dStart = $dDate->copy()->startOfDay();
            $dEnd = $dDate->copy()->endOfDay();
            $dTotal = (float) Order::where('pay_status', 'paid')->whereBetween('created_at', [$dStart, $dEnd])->sum('total');
            $dailySales[] = [
                'label' => $dDate->format('D, M d'),
                'total' => round($dTotal, 2),
            ];
        }

        // Chart Data 3: Orders by Status
        $ordersByStatus = [
            'pending' => (int) Order::where('status', 'pending')->count(),
            'processing' => (int) Order::where('status', 'processing')->count(),
            'shipped' => (int) Order::where('status', 'shipped')->count(),
            'delivered' => (int) Order::where('status', 'delivered')->count(),
            'cancelled' => (int) Order::where('status', 'cancelled')->count(),
        ];

        // Payments by Method
        $paymentsByMethod = Payment::query()
            ->select('method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        // Top Products
        $topProducts = OrderItem::query()
            ->with(['product.category'])
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.pay_status', 'paid')
            ->select('order_items.product_id', DB::raw('SUM(order_items.qty) as total_qty'), DB::raw('SUM(order_items.qty * order_items.price) as revenue'))
            ->groupBy('order_items.product_id')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // Sales by Category
        $categorySales = Category::query()
            ->leftJoin('products', 'categories.id', '=', 'products.category_id')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->where(function ($q) {
                $q->whereNull('orders.pay_status')->orWhere('orders.pay_status', 'paid');
            })
            ->select('categories.name', DB::raw('COALESCE(SUM(order_items.qty), 0) as total_qty'), DB::raw('COALESCE(SUM(order_items.qty * order_items.price), 0) as revenue'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // Recent Orders
        $recentOrders = Order::with('user')->latest()->limit(5)->get();

        return [
            'stats' => [
                'total_revenue' => $totalRevenue,
                'range_revenue' => $rangeRevenue,
                'month_revenue' => $monthRevenue,
                'today_revenue' => $todayRevenue,
                'total_orders' => $totalOrders,
                'range_orders' => $rangeOrders,
                'month_orders' => $monthOrders,
                'today_orders' => $todayOrders,
                'delivered_orders' => $deliveredOrders,
                'pending_orders' => $pendingOrders,
                'cancelled_orders' => $cancelledOrders,
                'total_customers' => $totalCustomers,
                'range_customers' => $rangeCustomers,
                'total_products' => $totalProducts,
                'low_stock_products' => $lowStockCount,
                'pending_inquiries' => $pendingInquiries,
                'avg_order_value' => round($avgOrderValue, 2),
                'total_members' => $totalMembers,
                'active_members' => $activeMembers,
                'new_members' => $newMembers,
                'total_pv' => round($totalPv, 2),
                'total_direct_selling_income' => round($totalDirectSellingIncome, 2),
                'pending_payouts' => round($pendingPayouts, 2),
                'paid_payouts' => round($paidPayouts, 2),
                'cashback_eligible_count' => $cashbackEligibleCount,
                'cashback_eligible_amount' => round($cashbackEligibleAmount, 2),
                'cashback_paid_amount' => round($cashbackPaidAmount, 2),
            ],
            'monthlySales' => $monthlySales,
            'dailySales' => $dailySales,
            'ordersByStatus' => $ordersByStatus,
            'paymentsByMethod' => $paymentsByMethod,
            'topProducts' => $topProducts,
            'categorySales' => $categorySales,
            'recentOrders' => $recentOrders,
        ];
    }

    /**
     * Compute Network Reports with Level 0-19 breakdown, Sponsors, and Placement stats.
     */
    public function getNetworkReport(array $range, array $filters = []): array
    {
        $start = $range['start'];
        $end = $range['end'];

        $totalMembers = Member::count();
        $newMembers = Member::whereBetween('joined_at', [$start, $end])->count();
        $activeMembers = Member::where('status', Member::STATUS_ACTIVE)->count();
        $inactiveMembers = Member::where('status', Member::STATUS_INACTIVE)->count();
        $pendingMembers = Member::where('status', Member::STATUS_PENDING)->count();
        $blockedMembers = Member::where('status', Member::STATUS_BLOCKED)->count();

        // KYC status breakdown
        $kycApproved = Member::where('kyc_status', Member::KYC_APPROVED)->count();
        $kycPending = Member::where('kyc_status', Member::KYC_PENDING)->count();
        $kycUnderReview = Member::where('kyc_status', Member::KYC_UNDER_REVIEW)->count();
        $kycRejected = Member::where('kyc_status', Member::KYC_REJECTED)->count();

        // Calculate Level 0 - 19 distribution from actual placement tree
        $parentMap = Member::query()->pluck('placement_parent_id', 'id')->all();
        $levelCounts = array_fill(0, 20, 0);
        $levelActiveCounts = array_fill(0, 20, 0);
        $memberLevels = [];

        $memberStatuses = Member::query()->pluck('status', 'id')->all();

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
            $memberLevels[$id] = $finalLevel;
            $levelCounts[$finalLevel]++;
            if (($memberStatuses[$id] ?? null) === Member::STATUS_ACTIVE) {
                $levelActiveCounts[$finalLevel]++;
            }
        }

        $levelsData = [];
        for ($i = 0; $i < 20; $i++) {
            $count = $levelCounts[$i];
            $pct = $totalMembers > 0 ? round(($count / $totalMembers) * 100, 1) : 0;
            $levelsData[] = [
                'level' => $i,
                'label' => 'Level ' . $i,
                'tier' => $i <= 7 ? 'High PV Tier (13.5)' : 'Low PV Tier (0.75)',
                'count' => $count,
                'active_count' => $levelActiveCounts[$i],
                'percentage' => $pct,
            ];
        }

        // Placement Position Distribution
        $placementLeft = Member::where('placement_position', Member::POSITION_LEFT)->count();
        $placementMiddle = Member::where('placement_position', Member::POSITION_MIDDLE)->count();
        $placementRight = Member::where('placement_position', Member::POSITION_RIGHT)->count();
        $placementRoots = Member::whereNull('placement_parent_id')->count();

        // Top Sponsors by Direct Referral Count
        $topSponsors = Member::query()
            ->with('user')
            ->has('sponsoredMembers')
            ->withCount('sponsoredMembers')
            ->orderByDesc('sponsored_members_count')
            ->limit(10)
            ->get()
            ->map(function ($sponsor) {
                return [
                    'id' => $sponsor->id,
                    'customer_id' => $sponsor->user?->mlm_member_id ?? ('#' . $sponsor->id),
                    'name' => $sponsor->user?->name ?? 'Unknown',
                    'referrals_count' => $sponsor->sponsored_members_count,
                    'status' => $sponsor->status,
                    'joined_at' => $sponsor->joined_at?->format('M d, Y') ?? '-',
                ];
            });

        // Member Growth over time (Last 6 Months)
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

        // Filtered Members Table Query
        $membersQuery = Member::query()
            ->with(['user', 'sponsor.user', 'placementParent.user'])
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = trim($filters['search']);
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('mlm_member_id', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(!empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['kyc_status']), fn ($q) => $q->where('kyc_status', $filters['kyc_status']))
            ->when(!empty($filters['placement_position']), fn ($q) => $q->where('placement_position', $filters['placement_position']));

        $filteredMembers = $membersQuery->latest('joined_at')->paginate(15)->withQueryString();

        return [
            'summary' => [
                'total_members' => $totalMembers,
                'new_members' => $newMembers,
                'active_members' => $activeMembers,
                'inactive_members' => $inactiveMembers,
                'pending_members' => $pendingMembers,
                'blocked_members' => $blockedMembers,
                'kyc_approved' => $kycApproved,
                'kyc_pending' => $kycPending,
                'kyc_under_review' => $kycUnderReview,
                'kyc_rejected' => $kycRejected,
                'placement_left' => $placementLeft,
                'placement_middle' => $placementMiddle,
                'placement_right' => $placementRight,
                'placement_roots' => $placementRoots,
            ],
            'levels_distribution' => $levelsData,
            'top_sponsors' => $topSponsors,
            'member_growth' => $memberGrowth,
            'members' => $filteredMembers,
            'member_levels_map' => $memberLevels,
        ];
    }

    /**
     * Compute Direct Selling Income Reports across Levels 0-19, members and periods.
     */
    public function getIncomeReport(array $range, array $filters = []): array
    {
        $start = $range['start'];
        $end = $range['end'];

        $baseQuery = MlmIncomeLedger::query()->whereBetween('transaction_date', [$start, $end]);

        $totalPv = (float) (clone $baseQuery)->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('pv');
        $totalIncome = (float) (clone $baseQuery)->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('calculated_amount');
        $paidIncome = (float) (clone $baseQuery)->where('status', MlmIncomeLedger::STATUS_PAID)->sum('calculated_amount');
        $pendingIncome = (float) (clone $baseQuery)->whereIn('status', [MlmIncomeLedger::STATUS_CALCULATED, MlmIncomeLedger::STATUS_PENDING_APPROVAL, MlmIncomeLedger::STATUS_APPROVED, MlmIncomeLedger::STATUS_PROCESSING])->sum('calculated_amount');
        $reversedIncome = (float) (clone $baseQuery)->where('status', MlmIncomeLedger::STATUS_REVERSED)->sum('calculated_amount');

        // Level-wise Income Breakdown (0 to 19)
        $incomeByLevelRaw = MlmIncomeLedger::query()
            ->whereBetween('transaction_date', [$start, $end])
            ->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)
            ->whereBetween('level', [0, 19])
            ->select(
                'level',
                DB::raw('COUNT(*) as tx_count'),
                DB::raw('COUNT(DISTINCT member_id) as member_count'),
                DB::raw('SUM(pv) as total_pv'),
                DB::raw('SUM(calculated_amount) as total_income')
            )
            ->groupBy('level')
            ->get()
            ->keyBy('level');

        $levelIncomeRows = [];
        for ($i = 0; $i < 20; $i++) {
            $row = $incomeByLevelRaw->get($i);
            $levelIncomeRows[] = [
                'level' => $i,
                'tier' => $i <= 7 ? 'High PV Tier (13.5 PV / ₹3000)' : 'Low PV Tier (0.75 PV / ₹3000)',
                'rate' => '20%',
                'tx_count' => $row ? (int) $row->tx_count : 0,
                'member_count' => $row ? (int) $row->member_count : 0,
                'total_pv' => $row ? round((float) $row->total_pv, 2) : 0.0,
                'total_income' => $row ? (int) round((float) $row->total_income) : 0,
            ];
        }

        // Income by Type
        $incomeByType = MlmIncomeLedger::query()
            ->whereBetween('transaction_date', [$start, $end])
            ->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)
            ->select('income_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(calculated_amount) as total'))
            ->groupBy('income_type')
            ->get();

        // Income by Status
        $incomeByStatus = MlmIncomeLedger::query()
            ->whereBetween('transaction_date', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(calculated_amount) as total'))
            ->groupBy('status')
            ->get();

        // Top Earning Members
        $topEarners = MlmIncomeLedger::query()
            ->with('member.user')
            ->whereBetween('transaction_date', [$start, $end])
            ->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)
            ->select('member_id', DB::raw('SUM(pv) as total_pv'), DB::raw('SUM(calculated_amount) as total_income'), DB::raw('COUNT(*) as entries_count'))
            ->groupBy('member_id')
            ->orderByDesc('total_income')
            ->limit(10)
            ->get();

        // Paginated Filtered Ledgers
        $ledgersQuery = MlmIncomeLedger::query()
            ->with(['member.user', 'purchasingMember.user', 'ruleVersion', 'payoutCycle'])
            ->when(!empty($filters['member_id']), fn ($q) => $q->where('member_id', $filters['member_id']))
            ->when(isset($filters['level']) && $filters['level'] !== '', fn ($q) => $q->where('level', (int) $filters['level']))
            ->when(!empty($filters['income_type']), fn ($q) => $q->where('income_type', $filters['income_type']))
            ->when(!empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = trim($filters['search']);
                $q->where(function ($sub) use ($search) {
                    $sub->where('source_transaction_reference', 'like', "%{$search}%")
                        ->orWhereHas('member.user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%")->orWhere('mlm_member_id', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('transaction_date');

        $paginatedLedgers = $ledgersQuery->paginate(20)->withQueryString();

        return [
            'summary' => [
                'total_pv' => round($totalPv, 2),
                'total_income' => (int) round($totalIncome),
                'paid_income' => (int) round($paidIncome),
                'pending_income' => (int) round($pendingIncome),
                'reversed_income' => (int) round($reversedIncome),
            ],
            'level_income' => $levelIncomeRows,
            'income_by_type' => $incomeByType,
            'income_by_status' => $incomeByStatus,
            'top_earners' => $topEarners,
            'ledgers' => $paginatedLedgers,
        ];
    }

    /**
     * Compute Weekly Payout Reports and cycle stats.
     */
    public function getPayoutReport(array $range, array $filters = []): array
    {
        $start = $range['start'];
        $end = $range['end'];

        $cycleQuery = MlmPayoutCycle::query()->whereBetween('period_start', [$start, $end]);

        $grossIncome = (float) (clone $cycleQuery)->sum('gross_income');
        $adjustments = (float) (clone $cycleQuery)->sum('adjustments_reversals');
        $netPayable = (float) (clone $cycleQuery)->sum('net_payable');
        $paidAmount = (float) (clone $cycleQuery)->where('status', MlmPayoutCycle::STATUS_PAID)->sum('net_payable');
        $pendingAmount = (float) (clone $cycleQuery)->whereIn('status', [MlmPayoutCycle::STATUS_PENDING_CALCULATION, MlmPayoutCycle::STATUS_PENDING_APPROVAL, MlmPayoutCycle::STATUS_APPROVED, MlmPayoutCycle::STATUS_PROCESSING])->sum('net_payable');
        $onHoldAmount = (float) (clone $cycleQuery)->where('status', MlmPayoutCycle::STATUS_ON_HOLD)->sum('net_payable');

        // Weekly Cycles Breakdown
        $weeklyCycles = MlmPayoutCycle::query()
            ->with(['createdBy', 'approvedBy'])
            ->withCount('lines')
            ->orderByDesc('period_start')
            ->paginate(15, ['*'], 'cycles_page')
            ->withQueryString();

        // Payout Lines query
        $payoutLinesQuery = MlmPayoutLine::query()
            ->with(['member.user', 'cycle'])
            ->when(!empty($filters['cycle_id']), fn ($q) => $q->where('payout_cycle_id', $filters['cycle_id']))
            ->when(!empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['member_id']), fn ($q) => $q->where('member_id', $filters['member_id']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = trim($filters['search']);
                $q->whereHas('member.user', function ($uq) use ($term) {
                    $uq->where('name', 'like', "%{$term}%")->orWhere('mlm_member_id', 'like', "%{$term}%");
                });
            })
            ->latest('id');

        $payoutLines = $payoutLinesQuery->paginate(20, ['*'], 'lines_page')->withQueryString();

        return [
            'summary' => [
                'gross_income' => round($grossIncome, 2),
                'adjustments_reversals' => round($adjustments, 2),
                'net_payable' => round($netPayable, 2),
                'paid_amount' => round($paidAmount, 2),
                'pending_amount' => round($pendingAmount, 2),
                'on_hold_amount' => round($onHoldAmount, 2),
            ],
            'cycles' => $weeklyCycles,
            'lines' => $payoutLines,
        ];
    }

    /**
     * Compute 100% Cashback & Company Profit Pool Reports.
     */
    public function getCashbackReport(array $range, array $filters = []): array
    {
        $start = $range['start'];
        $end = $range['end'];

        $totalEligible = (float) CashbackEligibility::sum('eligible_amount');
        $rangeEligible = (float) CashbackEligibility::whereBetween('created_at', [$start, $end])->sum('eligible_amount');
        $maxCashback = (float) CashbackEligibility::sum('max_cashback_amount');
        $recoveredAmount = (float) CashbackEligibility::sum('recovered_amount');
        $paidAmount = (float) CashbackEligibility::where('status', 'paid')->sum('eligible_amount');
        $awaitingProfitAmount = (float) CashbackEligibility::where('status', 'eligible_awaiting_profit')->sum('eligible_amount');
        $allocatedAmount = (float) CashbackEligibility::whereIn('status', ['allocated_to_pool', 'in_payout_batch'])->sum('eligible_amount');

        // Profit Pools Summary
        $profitPools = CashbackProfitPool::query()
            ->latest('pool_date')
            ->limit(10)
            ->get();

        // Payout Batches
        $payoutBatches = CashbackPayoutBatch::query()
            ->with(['pool', 'approvedBy'])
            ->withCount('items')
            ->latest('id')
            ->limit(10)
            ->get();

        // Paginated Cashback Records
        $recordsQuery = CashbackEligibility::query()
            ->with(['member.user', 'order'])
            ->when(!empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['member_id']), fn ($q) => $q->where('member_id', $filters['member_id']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = trim($filters['search']);
                $q->where(function ($sub) use ($term) {
                    $sub->where('order_reference', 'like', "%{$term}%")
                        ->orWhereHas('member.user', function ($uq) use ($term) {
                            $uq->where('name', 'like', "%{$term}%")->orWhere('mlm_member_id', 'like', "%{$term}%");
                        });
                });
            })
            ->latest();

        $records = $recordsQuery->paginate(20)->withQueryString();

        return [
            'summary' => [
                'total_eligible' => round($totalEligible, 2),
                'range_eligible' => round($rangeEligible, 2),
                'max_cashback' => round($maxCashback, 2),
                'recovered_amount' => round($recoveredAmount, 2),
                'paid_amount' => round($paidAmount, 2),
                'awaiting_profit_amount' => round($awaitingProfitAmount, 2),
                'allocated_amount' => round($allocatedAmount, 2),
            ],
            'profit_pools' => $profitPools,
            'payout_batches' => $payoutBatches,
            'records' => $records,
        ];
    }

    /**
     * Compute Profit & Margin Report.
     */
    public function getProfitReport(array $range): array
    {
        $start = $range['start'];
        $end = $range['end'];

        $orderItems = OrderItem::with('product')
            ->whereHas('order', function ($oq) use ($start, $end) {
                $oq->where('pay_status', 'paid')->whereBetween('created_at', [$start, $end]);
            })
            ->get();

        $revenue = 0.0;
        $cogs = 0.0;

        foreach ($orderItems as $item) {
            $itemRevenue = (float) ($item->line_total ?? ($item->qty * $item->price));
            $itemCost = (float) ($item->qty * ($item->product?->cost_price ?? 0.0));

            $revenue += $itemRevenue;
            $cogs += $itemCost;
        }

        $grossProfit = $revenue - $cogs;
        $grossMarginPct = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0.0;

        // Product Breakdown
        $productBreakdown = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.pay_status', 'paid')
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.cost_price',
                DB::raw('SUM(order_items.qty) as units_sold'),
                DB::raw('SUM(order_items.qty * order_items.price) as revenue'),
                DB::raw('SUM(order_items.qty * COALESCE(products.cost_price, 0)) as cost')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.cost_price')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($row) {
                $profit = (float) ($row->revenue - $row->cost);
                $marginPct = $row->revenue > 0 ? ($profit / $row->revenue) * 100 : 0.0;
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'sku' => $row->sku,
                    'units_sold' => (int) $row->units_sold,
                    'revenue' => (float) $row->revenue,
                    'cost' => (float) $row->cost,
                    'gross_profit' => round($profit, 2),
                    'margin_pct' => round($marginPct, 2),
                ];
            });

        return [
            'revenue' => round($revenue, 2),
            'cogs' => round($cogs, 2),
            'gross_profit' => round($grossProfit, 2),
            'gross_margin_pct' => round($grossMarginPct, 2),
            'product_breakdown' => $productBreakdown,
        ];
    }

    /**
     * Compute GST & HSN Breakdown Report.
     */
    public function getGstReport(array $range, ?string $hsn = null): array
    {
        $start = $range['start'];
        $end = $range['end'];

        $orders = Order::with(['items.product', 'user.addresses'])
            ->where('pay_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $totalTaxable = 0.0;
        $totalGst = 0.0;
        $totalCgst = 0.0;
        $totalSgst = 0.0;
        $totalIgst = 0.0;
        $missingLocationCount = 0;

        $sellerStateSetting = \App\Models\Setting::where('key', 'seller_state')->value('value')
            ?? config('app.seller_state', 'Gujarat');

        $hsnSummaryMap = [];

        foreach ($orders as $order) {
            $gstAmt = (float) $order->gst_amt;
            $totalAmt = (float) $order->total;
            $taxable = max($totalAmt - $gstAmt, 0.0);

            $totalTaxable += $taxable;
            $totalGst += $gstAmt;

            // Determine seller state
            $sellerState = $order->seller_state ?: $sellerStateSetting;

            // Determine customer state
            $customerState = $order->customer_state;
            if (! $customerState && $order->ship_addr) {
                $shipAddr = is_string($order->ship_addr) ? json_decode($order->ship_addr, true) : $order->ship_addr;
                $customerState = $shipAddr['state'] ?? null;
            }
            if (! $customerState && $order->bill_addr) {
                $billAddr = is_string($order->bill_addr) ? json_decode($order->bill_addr, true) : $order->bill_addr;
                $customerState = $billAddr['state'] ?? null;
            }
            if (! $customerState && $order->user && $order->user->addresses) {
                $defaultAddr = $order->user->addresses->first();
                $customerState = $defaultAddr?->state;
            }

            if ($sellerState && $customerState) {
                if (strcasecmp(trim($sellerState), trim($customerState)) === 0) {
                    // Intra-state sale (Same State) -> CGST + SGST
                    $cgst = $gstAmt / 2.0;
                    $sgst = $gstAmt / 2.0;
                    $igst = 0.0;
                } else {
                    // Inter-state sale (Different State) -> IGST
                    $cgst = 0.0;
                    $sgst = 0.0;
                    $igst = $gstAmt;
                }
            } else {
                // Missing location data: default to estimated intra-state CGST + SGST split
                $missingLocationCount++;
                $cgst = $gstAmt / 2.0;
                $sgst = $gstAmt / 2.0;
                $igst = 0.0;
            }

            $totalCgst += $cgst;
            $totalSgst += $sgst;
            $totalIgst += $igst;

            foreach ($order->items as $item) {
                $itemHsn = $item->product?->hsn_code ?? 'OTHER';
                if ($hsn && strcasecmp($itemHsn, $hsn) !== 0) {
                    continue;
                }

                $qty = (int) $item->qty;
                $itemRevenue = (float) ($item->qty * $item->price);

                if (! isset($hsnSummaryMap[$itemHsn])) {
                    $hsnSummaryMap[$itemHsn] = [
                        'hsn_code' => $itemHsn,
                        'total_qty' => 0,
                        'revenue' => 0.0,
                    ];
                }

                $hsnSummaryMap[$itemHsn]['total_qty'] += $qty;
                $hsnSummaryMap[$itemHsn]['revenue'] += $itemRevenue;
            }
        }

        return [
            'total_taxable' => round($totalTaxable, 2),
            'total_gst' => round($totalGst, 2),
            'total_cgst' => round($totalCgst, 2),
            'total_sgst' => round($totalSgst, 2),
            'total_igst' => round($totalIgst, 2),
            'missing_location_orders_count' => $missingLocationCount,
            'is_complete_location_data' => $missingLocationCount === 0,
            'hsn_summary' => array_values($hsnSummaryMap),
        ];
    }
}
