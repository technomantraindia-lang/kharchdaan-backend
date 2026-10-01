@extends('frontend.layouts.app')

@section('title', 'Direct Selling Level Summary')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="fas fa-layer-group me-2 text-primary"></i>Direct Selling Level Summary</h2>
        <p class="text-muted mb-0">Level 0 is your own purchase. Levels 1–19 show team purchase PV and calculated income.</p>
    </div>
    <a href="{{ route('network.index') }}" class="btn btn-outline-primary"><i class="fas fa-sitemap me-1"></i>Back to Network</a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3 d-flex align-items-center justify-content-between">
        <div>
            <div class="fw-bold fs-5 text-dark">{{ $member['name'] }}</div>
            <div class="font-monospace text-primary small">{{ $member['customer_id'] }}</div>
        </div>
        <div>
            <span class="badge bg-primary px-3 py-2">20-Level Matrix</span>
        </div>
    </div>
</div>

<div class="table-responsive card shadow-sm border-0">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Level</th>
                <th>Description</th>
                <th>Members</th>
                <th>Generated PV</th>
                <th>Direct Selling Income</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        @foreach($levels as $row)
            <tr>
                <td class="fw-bold text-primary">{{ $row['label'] }}</td>
                <td>{{ $row['meaning'] }}</td>
                @if($row['calculated'])
                    <td class="fw-semibold">{{ $row['member_count'] }}</td>
                    <td class="font-monospace">{{ number_format((float) $row['pv'], 4) }} PV</td>
                    <td class="fw-bold text-success">₹{{ number_format(round((float) $row['income'])) }}</td>
                @else
                    <td colspan="3" class="text-muted small">No transactions yet</td>
                @endif
                <td><span class="badge {{ $row['calculated'] ? 'bg-success' : 'bg-secondary' }}">{{ $row['status'] }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="alert alert-info mt-3 mb-0">
    <i class="fas fa-info-circle me-1"></i> PV and Direct Selling income are displayed directly from your verified ledger entries. Level 0–7 uses High PV tier (13.5) and Level 8–19 uses Low PV tier (0.75).
</div>
@endsection
