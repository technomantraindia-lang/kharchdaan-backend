<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarkMlmPayoutPaidRequest;
use App\Http\Requests\Admin\MlmPayoutActionReasonRequest;
use App\Http\Requests\Admin\StoreMlmAdjustmentRequest;
use App\Http\Requests\Admin\StoreMlmPayoutCycleRequest;
use App\Models\Member;
use App\Models\MlmCalculationRule;
use App\Models\MlmIncomeLedger;
use App\Models\MlmPayoutCycle;
use App\Services\Mlm\MlmPayoutService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use App\Models\MlmPayoutLine;
use App\Support\MlmDecimal;
use Illuminate\Validation\Rule;

class MlmPayoutController extends Controller
{
    public function __construct(private readonly MlmPayoutService $payoutService) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'cycle_id' => ['nullable', 'integer', 'exists:mlm_payout_cycles,id'],
            'member_id' => ['nullable', 'integer', 'exists:mlm_members,id'],
            'status' => ['nullable', Rule::in(MlmPayoutCycle::STATUSES)],
            'amount_min' => ['nullable', 'regex:/^-?\d+(?:\.\d{1,18})?$/'],
            'amount_max' => ['nullable', 'regex:/^-?\d+(?:\.\d{1,18})?$/'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $lineQuery = MlmPayoutLine::query()
            ->with(['member.user', 'cycle'])
            ->when(! empty($filters['cycle_id']), fn ($query) => $query->where('payout_cycle_id', $filters['cycle_id']))
            ->when(! empty($filters['member_id']), fn ($query) => $query->where('member_id', $filters['member_id']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['amount_min']), fn ($query) => $query->where('net_payable', '>=', $filters['amount_min']))
            ->when(isset($filters['amount_max']), fn ($query) => $query->where('net_payable', '<=', $filters['amount_max']))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereHas('cycle', fn ($cycle) => $cycle->whereDate('period_end', '>=', $filters['date_from'])))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereHas('cycle', fn ($cycle) => $cycle->whereDate('period_start', '<=', $filters['date_to'])))
            ->when(! empty($filters['q']), fn ($query) => $query->whereHas('member.user', function ($user) use ($filters): void {
                $term = trim($filters['q']);
                $user->where('name', 'like', '%'.$term.'%')->orWhere('mlm_member_id', 'like', '%'.$term.'%');
            }));

        $summaryRows = (clone $lineQuery)->get();
        $summary = [
            'total_members' => $summaryRows->pluck('member_id')->unique()->count(),
            'gross_income' => $summaryRows->reduce(fn (string $total, MlmPayoutLine $line): string => MlmDecimal::add($total, (string) $line->gross_income), MlmDecimal::normalize('0')),
            'adjustment_amount' => $summaryRows->reduce(fn (string $total, MlmPayoutLine $line): string => MlmDecimal::add($total, (string) $line->adjustment_amount), MlmDecimal::normalize('0')),
            'net_payable' => $summaryRows->reduce(fn (string $total, MlmPayoutLine $line): string => MlmDecimal::add($total, (string) $line->net_payable), MlmDecimal::normalize('0')),
            'paid_amount' => $summaryRows->filter(fn (MlmPayoutLine $line): bool => $line->status === MlmPayoutCycle::STATUS_PAID)->reduce(fn (string $total, MlmPayoutLine $line): string => MlmDecimal::add($total, (string) $line->net_payable), MlmDecimal::normalize('0')),
        ];
        $summary['pending_amount'] = MlmDecimal::subtract($summary['net_payable'], $summary['paid_amount']);

        $payoutLines = $lineQuery->latest('id')->paginate(25)->withQueryString();

        return view('admin.mlm.payouts.index', [
            'payoutLines' => $payoutLines,
            'summary' => $summary,
            'filters' => $filters,
            'cycles' => MlmPayoutCycle::query()->with('createdBy')->latest('period_start')->limit(20)->get(),
            'members' => Member::query()->with('user')->orderBy('id')->get(),
            'rules' => MlmCalculationRule::query()->where('status', 'active')->orderByDesc('id')->get(),
            'cycleStatuses' => MlmPayoutCycle::STATUSES,
        ]);
    }

    public function storeCycle(StoreMlmPayoutCycleRequest $request): RedirectResponse
    {
        $this->authorizeManage();
        $this->payoutService->createWeeklyCycle(
            $request->validated('period_start'),
            $request->user(),
            $request->validated('admin_note')
        );

        return back()->with('success', 'Weekly payout cycle prepared.');
    }

    public function approve(MlmPayoutCycle $cycle): RedirectResponse
    {
        $this->authorizeManage();
        $this->payoutService->approve($cycle, request()->user());

        return back()->with('success', 'Payout cycle approved.');
    }

    public function process(MlmPayoutCycle $cycle): RedirectResponse
    {
        $this->authorizeManage();
        $this->payoutService->startProcessing($cycle, request()->user());

        return back()->with('success', 'Payout cycle moved to processing.');
    }

    public function paid(MarkMlmPayoutPaidRequest $request, MlmPayoutCycle $cycle): RedirectResponse
    {
        $this->authorizeManage();
        $this->payoutService->markPaid(
            $cycle,
            $request->user(),
            $request->validated('payment_reference'),
            $request->validated('payment_date'),
            $request->validated('admin_note'),
            $request->file('payment_proof')
        );

        return back()->with('success', 'Payout cycle marked as paid.');
    }

    public function failed(Request $request, MlmPayoutCycle $cycle): RedirectResponse
    {
        $this->authorizeManage();
        $validated = $request->validate(['admin_note' => ['nullable', 'string', 'max:2000']]);
        $this->payoutService->markFailed($cycle, $request->user(), $validated['admin_note'] ?? null);

        return back()->with('success', 'Payout cycle marked as failed.');
    }

    public function hold(MlmPayoutActionReasonRequest $request, MlmPayoutCycle $cycle): RedirectResponse
    {
        $this->authorizeManage();
        $this->payoutService->hold($cycle, $request->validated('reason'), $request->user());

        return back()->with('success', 'MLM payout cycle placed on hold.');
    }

    public function releaseHold(Request $request, MlmPayoutCycle $cycle): RedirectResponse
    {
        $this->authorizeManage();
        $this->payoutService->releaseHold($cycle, $request->user());

        return back()->with('success', 'MLM payout cycle released from hold.');
    }

    public function reverse(MlmPayoutActionReasonRequest $request, MlmIncomeLedger $ledger): RedirectResponse
    {
        $this->authorizeManage();
        $this->payoutService->reverse($ledger, request()->user(), $request->validated('reason'));

        return back()->with('success', 'Reversal ledger entry created.');
    }

    public function paymentProof(MlmPayoutCycle $cycle): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless(request()->user()?->hasPermission('mlm.view'), 403);
        abort_unless($cycle->payment_proof_path && Storage::disk('local')->exists($cycle->payment_proof_path), 404);

        return Storage::disk('local')->download($cycle->payment_proof_path, 'mlm-payout-payment-proof');
    }

    public function adjustment(StoreMlmAdjustmentRequest $request): RedirectResponse
    {
        $this->authorizeManage();
        $data = $request->validated();
        $this->payoutService->createAdjustment(
            Member::findOrFail($data['member_id']),
            $data['eligible_amount'],
            $data['pv'],
            $data['rate'],
            $data['calculated_amount'],
            (int) $data['level'],
            $data['transaction_reference'],
            Carbon::parse($data['transaction_date']),
            MlmCalculationRule::findOrFail($data['rule_version_id']),
            $request->user()
        );

        return back()->with('success', 'Adjustment ledger entry created.');
    }

    private function authorizeManage(): void
    {
        abort_unless(request()->user()?->hasPermission('mlm.manage'), 403, 'Unauthorized action.');
    }
}
