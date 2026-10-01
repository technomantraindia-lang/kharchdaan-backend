@extends('frontend.layouts.app')

@section('title', 'Direct Selling Payouts')

@section('content')
<div class="py-2">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-wallet me-2 text-warning"></i>Direct Selling Payouts</h2>
            <p class="text-muted mb-0">Weekly verified settlement statements for your Direct Selling network income.</p>
        </div>
        <a href="{{ route('network.index') }}" class="btn btn-outline-primary"><i class="fas fa-sitemap me-1"></i>Direct Selling Network</a>
    </div>

    <div class="table-responsive card shadow-sm border-0 mb-4">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cycle Reference</th>
                    <th>Period</th>
                    <th>Gross Income</th>
                    <th>Adjustments</th>
                    <th>Net Payable</th>
                    <th>Status</th>
                    <th>Bank Settlement</th>
                </tr>
            </thead>
            <tbody>
            @forelse($payouts as $line)
                @php($cycle = $line->cycle)
                @php
                    $cycleBadge = match($cycle?->status ?? $line->status) {
                        'paid' => 'success',
                        'approved' => 'primary',
                        'processing' => 'info',
                        'failed' => 'danger',
                        default => 'warning'
                    };
                @endphp
                <tr>
                    <td class="font-monospace fw-bold text-dark">{{ $cycle?->cycle_reference ?? 'CYCLE-'.$cycle?->id }}</td>
                    <td class="small">{{ $cycle?->period_start?->format('d M Y') }} – {{ $cycle?->period_end?->format('d M Y') }}</td>
                    <td class="fw-semibold">₹{{ number_format(round((float) $line->gross_income)) }}</td>
                    <td class="text-muted">₹{{ number_format(round((float) $line->adjustment_amount)) }}</td>
                    <td class="fw-bold text-success fs-6">₹{{ number_format(round((float) $line->net_payable)) }}</td>
                    <td><span class="badge bg-{{ $cycleBadge }} text-uppercase">{{ str_replace('_', ' ', $cycle?->status ?? $line->status) }}</span></td>
                    <td>
                        @if($cycle?->payment_date)
                            <div class="fw-semibold text-success"><i class="fas fa-check-circle me-1"></i>{{ $cycle->payment_date->format('d M Y') }}</div>
                        @else
                            <span class="text-muted small">Pending Settlement</span>
                        @endif
                        @if($cycle?->payment_reference)
                            <div class="small font-monospace text-muted">{{ $cycle->payment_reference }}</div>
                        @endif
                        @if($cycle?->status === \App\Models\MlmPayoutCycle::STATUS_PAID && $cycle->payment_proof_path)
                            <a href="{{ route('mlm-payouts.payment-proof', $cycle) }}" class="badge bg-outline-primary text-primary border border-primary text-decoration-none mt-1 d-inline-block">
                                <i class="fas fa-receipt me-1"></i>Bank Receipt
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-5">No Direct Selling payout statements found for your account.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $payouts->links() }}
</div>
@endsection
