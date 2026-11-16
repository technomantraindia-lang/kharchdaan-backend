@extends('frontend.layouts.app')

@section('title', 'My Transactions')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-receipt me-2 text-primary"></i>My Transactions</h2>
            <p class="text-muted mb-0">Eligibility marks for Cashback, Referral Income, and Direct Selling. Non-eligible rows show in red.</p>
        </div>
        <a href="{{ route('account') }}" class="btn btn-outline-secondary btn-sm">Back to Account</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted text-uppercase fw-bold">This Month Purchases</div>
                    <div class="fs-4 fw-bold">₹{{ number_format((float) $summary['month_purchase_total'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted text-uppercase fw-bold">Direct Selling (₹{{ $summary['ds_minimum'] }})</div>
                    <span class="badge {{ $summary['ds_eligible'] ? 'bg-success' : 'bg-danger' }}">
                        {{ $summary['ds_eligible'] ? 'Eligible' : 'Not Eligible' }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted text-uppercase fw-bold">Referral Month (₹{{ $summary['referral_min_transaction'] }})</div>
                    <span class="badge {{ $summary['referral_month_eligible'] ? 'bg-success' : 'bg-danger' }}">
                        {{ $summary['referral_month_eligible'] ? 'Eligible' : 'Not Eligible' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <form class="card border-0 shadow-sm mb-4" method="GET">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Search</label>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Transaction ID / status">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">From</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">To</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Txn ID</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Cashback Status</th>
                        <th>Customer Cashback</th>
                        <th>Sponsor Cashback</th>
                        <th>Referral</th>
                        <th>Direct Selling</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $row)
                        <tr class="{{ !empty($row['highlight_red']) ? 'table-danger' : '' }}">
                            <td class="font-monospace fw-semibold">{{ $row['transaction_id'] }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td>₹{{ number_format((float) $row['amount'], 2) }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $row['status'] }}</span>
                                <span class="badge bg-light text-dark">{{ $row['pay_status'] }}</span>
                                @if(($row['channel'] ?? 'online') === 'offline')
                                    <span class="badge bg-warning text-dark">Offline</span>
                                @endif
                            </td>
                            <td>
                                <span class="{{ $row['cashback']['eligible'] ? 'text-success' : 'text-danger fw-bold' }}">
                                    {{ $row['cashback']['label'] }}
                                </span>
                            </td>
                            <td class="fw-semibold">
                                @if($row['cashback']['eligible'])
                                    ₹{{ number_format((float) ($row['cashback']['customer_cashback'] ?? $row['cashback']['rounded_amount'] ?? $row['cashback']['amount']), 2) }}
                                @else
                                    <span class="text-danger">—</span>
                                @endif
                            </td>
                            <td class="fw-semibold">
                                @if($row['cashback']['eligible'])
                                    ₹{{ number_format((float) ($row['cashback']['sponsor_cashback'] ?? 0), 2) }}
                                @else
                                    <span class="text-danger">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="{{ $row['referral_income']['eligible'] ? 'text-success' : 'text-danger fw-bold' }}">
                                    {{ $row['referral_income']['label'] }}
                                </span>
                            </td>
                            <td>
                                <span class="{{ $row['direct_selling']['eligible'] ? 'text-success' : 'text-danger fw-bold' }}">
                                    {{ $row['direct_selling']['label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">No transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
