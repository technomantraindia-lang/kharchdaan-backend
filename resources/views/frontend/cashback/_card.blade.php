@php
    $formatMoney = static function ($value): string {
        return number_format(round((float) $value));
    };
    $displayStatus = [
        'eligible_awaiting_company_profit' => 'Eligible - Awaiting Company Profit',
        'selected_by_admin' => 'Selected by Company',
        'approved' => 'Approved', 'scheduled' => 'Scheduled', 'processing' => 'Processing',
        'paid' => 'Paid', 'on_hold' => 'On Hold', 'reversed' => 'Reversed',
        'cancelled_due_to_refund' => 'Cancelled due to Refund', 'not_eligible' => 'Not Eligible',
    ][$cashback->status] ?? $cashback->status;
    $batch = $cashback->payoutBatchItems->first()?->batch;
@endphp
<div class="card mb-3 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between flex-wrap gap-2">
            <h5 class="card-title mb-0">Order {{ $cashback->order?->order_num ?? $cashback->order_id }}</h5>
            <span class="badge text-bg-secondary">{{ $displayStatus }}</span>
        </div>
        <div class="row mt-3 g-3">
            <div class="col-md-3"><small class="text-muted">Transaction date</small><div>{{ optional($cashback->order?->created_at)->format('d M Y') ?? '—' }}</div></div>
            <div class="col-md-3"><small class="text-muted">Final eligible amount</small><div>₹{{ $formatMoney($cashback->final_eligible_amount) }}</div></div>
            <div class="col-md-3"><small class="text-muted">Maximum conditional cashback</small><div>₹{{ $formatMoney($cashback->maximum_cashback_amount) }}</div></div>
            <div class="col-md-3"><small class="text-muted">Scheduled payment date</small><div>{{ $batch?->scheduled_payment_date?->format('d M Y') ?? 'Not scheduled' }}</div></div>
        </div>
        @if($cashback->status === 'paid')
            <div class="mt-3 small">Payment reference: {{ $batch?->payment_reference ? \Illuminate\Support\Str::mask($batch->payment_reference, '*', 3, max(strlen($batch->payment_reference) - 6, 0)) : '—' }}</div>
            @if($batch?->payment_proof_path)
                <a class="btn btn-sm btn-outline-primary mt-2" href="{{ route('cashback.payment-proof', $cashback) }}">View payment proof</a>
            @endif
        @endif
        @if($cashback->status === 'on_hold' || $cashback->status === 'reversed' || $cashback->status === 'cancelled_due_to_refund')
            <div class="alert alert-warning mt-3 mb-0">{{ $cashback->ineligibility_reason ?? 'This cashback record has an exception status.' }}</div>
        @endif
        <a href="{{ route('cashback.show', $cashback) }}" class="btn btn-link px-0 mt-2">View details</a>
    </div>
</div>
