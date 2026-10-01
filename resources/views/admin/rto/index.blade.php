@extends('admin.layouts.app')

@section('title', 'Return To Origin (RTO)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="fw-bold m-0"><i class="fas fa-shipping-fast text-danger me-2"></i>Return To Origin (RTO)</h3>
    <a href="{{ admin_route('rto.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Initiate RTO</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-danger">
            <div class="card-body">
                <div class="text-muted small">Total RTO Shipments</div>
                <div class="fs-4 fw-bold">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-warning">
            <div class="card-body">
                <div class="text-muted small">Initiated / In Transit</div>
                <div class="fs-4 fw-bold text-warning">{{ number_format($stats['initiated']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-info">
            <div class="card-body">
                <div class="text-muted small">Received / Inspected</div>
                <div class="fs-4 fw-bold text-info">{{ number_format($stats['received']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small">Restocked</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($stats['restocked']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Search RTO #, Tracking #, or Order #..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="rto_initiated" @selected(request('status')==='rto_initiated')>Initiated</option>
                    <option value="rto_in_transit" @selected(request('status')==='rto_in_transit')>In Transit</option>
                    <option value="rto_received" @selected(request('status')==='rto_received')>Received</option>
                    <option value="rto_inspected" @selected(request('status')==='rto_inspected')>Inspected</option>
                    <option value="rto_restocked" @selected(request('status')==='rto_restocked')>Restocked</option>
                    <option value="rto_damaged" @selected(request('status')==='rto_damaged')>Damaged</option>
                    <option value="rto_closed" @selected(request('status')==='rto_closed')>Closed</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="{{ admin_route('rto.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>RTO #</th>
                    <th>Order #</th>
                    <th>Tracking #</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Initiated At</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shipments as $rto)
                    <tr>
                        <td><strong>{{ $rto->rto_num }}</strong></td>
                        <td><a href="{{ admin_route('orders.show', $rto->order) }}"><code>#{{ $rto->order?->order_num }}</code></a></td>
                        <td><code>{{ $rto->shipment_id ?: 'N/A' }}</code></td>
                        <td>{{ $rto->reason }}</td>
                        <td>
                            @php
                                $badge = match($rto->status) {
                                    'rto_restocked' => 'success',
                                    'rto_received', 'rto_inspected' => 'info',
                                    'rto_damaged' => 'danger',
                                    default => 'warning'
                                };
                            @endphp
                            <span class="badge bg-{{ $badge }}">{{ ucfirst(str_replace('_', ' ', $rto->status)) }}</span>
                        </td>
                        <td><small class="text-muted">{{ $rto->created_at->format('Y-m-d H:i') }}</small></td>
                        <td class="text-end">
                            <a href="{{ admin_route('rto.show', $rto) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Manage
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No RTO shipments recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-3">
        {{ $shipments->links() }}
    </div>
</div>
@endsection
