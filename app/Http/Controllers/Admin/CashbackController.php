<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CashbackActionReasonRequest;
use App\Http\Requests\Admin\MarkCashbackBatchPaidRequest;
use App\Http\Requests\Admin\ScheduleCashbackBatchRequest;
use App\Http\Requests\Admin\SelectCashbackRecordsRequest;
use App\Http\Requests\Admin\StoreCashbackProfitPoolRequest;
use App\Models\CashbackEligibility;
use App\Models\CashbackPayoutBatch;
use App\Models\CashbackProfitPool;
use App\Models\Member;
use App\Services\Cashback\CashbackPayoutService;
use App\Services\Cashback\CashbackRefundReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CashbackController extends Controller
{
    public function __construct(private readonly CashbackPayoutService $payoutService) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'member_id' => ['nullable', 'integer', 'exists:mlm_members,id'],
            'order_num' => ['nullable', 'string', 'max:100'],
            'amount_min' => ['nullable', 'regex:/^\d+(?:\.\d{1,18})?$/'],
            'amount_max' => ['nullable', 'regex:/^\d+(?:\.\d{1,18})?$/'],
            'status' => ['nullable', Rule::in(CashbackEligibility::STATUSES)],
            'pool_id' => ['nullable', 'integer', 'exists:cashback_profit_pools,id'],
        ]);
        $cashbacks = $this->payoutService->eligibleQuery($filters)
            ->latest('eligibility_date')
            ->paginate(20)
            ->withQueryString();

        $pools = CashbackProfitPool::query()->latest()->limit(25)->get();
        $selectedPool = ! empty($filters['pool_id'])
            ? CashbackProfitPool::query()->find($filters['pool_id'])
            : $pools->firstWhere('status', CashbackProfitPool::STATUS_ACTIVE);

        return view('admin.cashback.index', [
            'cashbacks' => $cashbacks,
            'pools' => $pools,
            'selectedPool' => $selectedPool,
            'summary' => $this->payoutService->poolSummary($selectedPool),
            'members' => Member::query()->with('user')->whereHas('cashbackEligibilities')->orderBy('id')->get(),
            'statuses' => CashbackEligibility::STATUSES,
            'batches' => CashbackPayoutBatch::query()->with(['pool', 'items'])->latest()->limit(20)->get(),
        ]);
    }

    public function reconciliation(Request $request): View
    {
        $records = CashbackEligibility::query()
            ->with(['order.user', 'member.user', 'adjustments', 'payoutBatchItems.batch'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('order'), fn ($query) => $query->whereHas('order', fn ($order) => $order->where('order_num', 'like', '%'.$request->string('order').'%')))
            ->latest('last_processed_at')
            ->paginate(25)->withQueryString();

        return view('admin.cashback.reconciliation', compact('records'));
    }

    public function retryReconciliation(CashbackEligibility $cashback, CashbackRefundReconciliationService $service): RedirectResponse
    {
        $service->retry($cashback);

        return back()->with('success', 'Cashback refund reconciliation retried safely.');
    }

    public function storePool(StoreCashbackProfitPoolRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->payoutService->declarePool(
            $data['approved_available_amount'],
            $data['pool_reference'],
            $data['admin_note'] ?? null,
            $request->user()
        );

        return back()->with('success', 'Approved cashback profit pool declared.');
    }

    public function select(SelectCashbackRecordsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $filters = collect($data)->only(['date_from', 'date_to', 'member_id', 'order_num', 'amount_min', 'amount_max', 'status'])->all();
        $count = $this->payoutService->selectRecords(
            CashbackProfitPool::findOrFail($data['pool_id']),
            $filters,
            $data['cashback_ids'] ?? [],
            $request->boolean('select_all'),
            $request->user()
        );

        return back()->with('success', "{$count} cashback record(s) selected.");
    }

    public function createBatch(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'cashback.select');
        $data = $request->validate(['pool_id' => ['required', 'integer', 'exists:cashback_profit_pools,id']]);
        $batch = $this->payoutService->createBatch(CashbackProfitPool::findOrFail($data['pool_id']), $request->user());

        return back()->with('success', "Cashback payout batch {$batch->batch_reference} created for approval.");
    }

    public function approve(Request $request, CashbackPayoutBatch $batch): RedirectResponse
    {
        $this->authorizePermission($request, 'cashback.approve');
        $this->payoutService->approveBatch($batch, $request->user());

        return back()->with('success', 'Cashback payout batch approved.');
    }

    public function schedule(ScheduleCashbackBatchRequest $request, CashbackPayoutBatch $batch): RedirectResponse
    {
        $this->payoutService->scheduleBatch($batch, $request->validated('scheduled_payment_date'), $request->user());

        return back()->with('success', 'Cashback payout batch scheduled.');
    }

    public function process(Request $request, CashbackPayoutBatch $batch): RedirectResponse
    {
        $this->authorizePermission($request, 'cashback.pay');
        $this->payoutService->startProcessing($batch, $request->user());

        return back()->with('success', 'Cashback payout batch moved to processing.');
    }

    public function paid(MarkCashbackBatchPaidRequest $request, CashbackPayoutBatch $batch): RedirectResponse
    {
        $data = $request->validated();
        $this->payoutService->markPaid(
            $batch,
            $data['payment_reference'],
            $request->file('payment_proof'),
            $request->user(),
            $data['admin_note'] ?? null
        );

        return back()->with('success', 'Cashback payout batch marked paid.');
    }

    public function hold(CashbackActionReasonRequest $request, CashbackEligibility $cashback): RedirectResponse
    {
        $this->authorizePermission($request, 'cashback.hold');
        $this->payoutService->hold($cashback, $request->validated('reason'), $request->user());

        return back()->with('success', 'Cashback record placed on hold.');
    }

    public function reverse(CashbackActionReasonRequest $request, CashbackEligibility $cashback): RedirectResponse
    {
        $this->authorizePermission($request, 'cashback.reverse');
        $this->payoutService->reverse($cashback, $request->validated('reason'), $request->user());

        return back()->with('success', 'Cashback reversal recorded.');
    }

    public function paymentProof(CashbackPayoutBatch $batch): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless(request()->user()?->hasPermission('cashback.view'), 403);
        abort_unless($batch->payment_proof_path && Storage::disk('local')->exists($batch->payment_proof_path), 404);

        return Storage::disk('local')->download($batch->payment_proof_path);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403, 'Unauthorized action.');
    }
}
