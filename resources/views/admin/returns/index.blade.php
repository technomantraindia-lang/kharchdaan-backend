@extends('admin.layouts.app')

@section('title', 'Sales Returns')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="fw-bold m-0"><i class="fas fa-undo text-primary me-2"></i>Sales Returns</h3>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-primary">
            <div class="card-body">
                <div class="text-muted small">Total Returns</div>
                <div class="fs-4 fw-bold">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-warning">
            <div class="card-body">
                <div class="text-muted small">Requested / Pending</div>
                <div class="fs-4 fw-bold text-warning">{{ number_format($stats['requested']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-info">
            <div class="card-body">
                <div class="text-muted small">Inspected</div>
                <div class="fs-4 fw-bold text-info">{{ number_format($stats['inspected']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small">Completed / Restocked</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($stats['completed']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Search Return # or Order #..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="requested" @selected(request('status')==='requested')>Requested</option>
                    <option value="approved" @selected(request('status')==='approved')>Approved</option>
                    <option value="pickup_scheduled" @selected(request('status')==='pickup_scheduled')>Pickup Scheduled</option>
                    <option value="received" @selected(request('status')==='received')>Received</option>
                    <option value="inspected" @selected(request('status')==='inspected')>Inspected</option>
                    <option value="completed" @selected(request('status')==='completed')>Completed (Restocked)</option>
                    <option value="rejected" @selected(request('status')==='rejected')>Rejected</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="{{ admin_route('returns.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Return #</th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Requested At</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($returns as $ret)
                    <tr>
                        <td><strong>{{ $ret->return_num }}</strong></td>
                        <td><a href="{{ admin_route('orders.show', $ret->order) }}"><code>#{{ $ret->order?->order_num }}</code></a></td>
                        <td>{{ $ret->user?->name ?: 'Guest / Customer' }}</td>
                        <td>{{ $ret->reason }}</td>
                        <td>
                            @php
                                $badge = match($ret->status) {
                                    'completed' => 'success',
                                    'approved', 'inspected' => 'info',
                                    'rejected' => 'danger',
                                    default => 'warning'
                                };
                            @endphp
                            <span class="badge bg-{{ $badge }}">{{ ucfirst(str_replace('_', ' ', $ret->status)) }}</span>
                        </td>
                        <td><small class="text-muted">{{ $ret->created_at->format('Y-m-d H:i') }}</small></td>
                        <td class="text-end">
                            <a href="{{ admin_route('returns.show', $ret) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Manage
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No return requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-3">
        {{ $returns->links() }}
    </div>
</div>
@endsection
