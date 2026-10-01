<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MlmPayoutCycle;
use App\Services\ExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportService $reportService;
    protected ExportService $exportService;

    public function __construct(ReportService $reportService, ExportService $exportService)
    {
        $this->reportService = $reportService;
        $this->exportService = $exportService;
    }

    public function index(Request $request)
    {
        $preset = $request->get('date_preset', 'this_month');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $range = $this->reportService->resolveDateRange($preset, $dateFrom, $dateTo);
        $overviewData = $this->reportService->getOverviewReport($range);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'range' => $range,
                'data' => $overviewData,
            ]);
        }

        return view('admin.reports.index', [
            'range' => $range,
            'stats' => $overviewData['stats'],
            'monthlySales' => $overviewData['monthlySales'],
            'dailySales' => $overviewData['dailySales'],
            'ordersByStatus' => $overviewData['ordersByStatus'],
            'paymentsByMethod' => $overviewData['paymentsByMethod'],
            'topProducts' => $overviewData['topProducts'],
            'categorySales' => $overviewData['categorySales'],
            'recentOrders' => $overviewData['recentOrders'],
        ]);
    }

    public function network(Request $request)
    {
        $preset = $request->get('date_preset', 'this_year');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $range = $this->reportService->resolveDateRange($preset, $dateFrom, $dateTo);
        $filters = $request->only(['search', 'status', 'kyc_status', 'placement_position']);
        $networkData = $this->reportService->getNetworkReport($range, $filters);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'range' => $range,
                'data' => $networkData,
            ]);
        }

        return view('admin.reports.network', [
            'range' => $range,
            'filters' => $filters,
            'summary' => $networkData['summary'],
            'levelsDistribution' => $networkData['levels_distribution'],
            'topSponsors' => $networkData['top_sponsors'],
            'memberGrowth' => $networkData['member_growth'],
            'members' => $networkData['members'],
            'memberLevelsMap' => $networkData['member_levels_map'],
        ]);
    }

    public function income(Request $request)
    {
        $preset = $request->get('date_preset', 'this_month');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $range = $this->reportService->resolveDateRange($preset, $dateFrom, $dateTo);
        $filters = $request->only(['search', 'member_id', 'level', 'income_type', 'status']);
        $incomeData = $this->reportService->getIncomeReport($range, $filters);

        $members = Member::with('user')->orderBy('id')->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'range' => $range,
                'data' => $incomeData,
            ]);
        }

        return view('admin.reports.income', [
            'range' => $range,
            'filters' => $filters,
            'summary' => $incomeData['summary'],
            'levelIncome' => $incomeData['level_income'],
            'incomeByType' => $incomeData['income_by_type'],
            'incomeByStatus' => $incomeData['income_by_status'],
            'topEarners' => $incomeData['top_earners'],
            'ledgers' => $incomeData['ledgers'],
            'members' => $members,
        ]);
    }

    public function payouts(Request $request)
    {
        $preset = $request->get('date_preset', 'this_year');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $range = $this->reportService->resolveDateRange($preset, $dateFrom, $dateTo);
        $filters = $request->only(['search', 'cycle_id', 'member_id', 'status']);
        $payoutData = $this->reportService->getPayoutReport($range, $filters);

        $cycles = MlmPayoutCycle::latest('period_start')->get();
        $members = Member::with('user')->orderBy('id')->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'range' => $range,
                'data' => $payoutData,
            ]);
        }

        return view('admin.reports.payouts', [
            'range' => $range,
            'filters' => $filters,
            'summary' => $payoutData['summary'],
            'cycles' => $payoutData['cycles'],
            'lines' => $payoutData['lines'],
            'allCycles' => $cycles,
            'members' => $members,
        ]);
    }

    public function cashback(Request $request)
    {
        $preset = $request->get('date_preset', 'this_year');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $range = $this->reportService->resolveDateRange($preset, $dateFrom, $dateTo);
        $filters = $request->only(['search', 'member_id', 'status']);
        $cashbackData = $this->reportService->getCashbackReport($range, $filters);

        $members = Member::with('user')->orderBy('id')->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'range' => $range,
                'data' => $cashbackData,
            ]);
        }

        return view('admin.reports.cashback', [
            'range' => $range,
            'filters' => $filters,
            'summary' => $cashbackData['summary'],
            'profitPools' => $cashbackData['profit_pools'],
            'payoutBatches' => $cashbackData['payout_batches'],
            'records' => $cashbackData['records'],
            'members' => $members,
        ]);
    }

    public function profit(Request $request)
    {
        $preset = $request->get('date_preset', 'this_month');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $range = $this->reportService->resolveDateRange($preset, $dateFrom, $dateTo);
        $profitData = $this->reportService->getProfitReport($range);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'range' => $range,
                'data' => $profitData,
            ]);
        }

        return view('admin.reports.profit', [
            'range' => $range,
            'profit' => $profitData,
        ]);
    }

    public function gst(Request $request)
    {
        $preset = $request->get('date_preset', 'this_month');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $hsn = $request->get('hsn');

        $range = $this->reportService->resolveDateRange($preset, $dateFrom, $dateTo);
        $gstData = $this->reportService->getGstReport($range, $hsn);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'range' => $range,
                'data' => $gstData,
            ]);
        }

        return view('admin.reports.gst', [
            'range' => $range,
            'gst' => $gstData,
        ]);
    }

    public function export(Request $request, string $module)
    {
        $preset = $request->get('date_preset', 'this_month');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $range = $this->reportService->resolveDateRange($preset, $dateFrom, $dateTo);
        $filters = $request->except(['date_preset', 'date_from', 'date_to']);

        return $this->exportService->exportCsv($module, $range, $filters);
    }
}
