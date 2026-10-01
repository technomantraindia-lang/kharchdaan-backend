@extends('admin.layouts.app')

@section('title', 'Manage Return #' . $return->return_num)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0"><i class="fas fa-undo text-primary me-2"></i>Return #{{ $return->return_num }}</h3>
    <a href="{{ admin_route('returns.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Returns</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0">Return Items</h5>
            </div>
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Condition</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($return->items as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->product?->name ?: 'Item' }}</strong>
                                    <small class="d-block text-muted">SKU: {{ $item->product?->sku ?: 'N/A' }}</small>
                                </td>
                                <td><span class="badge bg-secondary">{{ $item->quantity }}</span></td>
                                <td><span class="badge bg-info text-dark">{{ ucfirst($item->condition ?: 'good') }}</span></td>
                                <td>{{ $item->reason }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No return items attached.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0">Order Information</h5>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>Order #:</strong> {{ $return->order?->order_num }}</p>
                <p class="mb-1"><strong>Customer:</strong> {{ $return->user?->name ?: 'Guest' }} ({{ $return->user?->email ?: 'N/A' }})</p>
                <p class="mb-1"><strong>Order Total:</strong> ₹{{ number_format($return->order?->total ?? 0, 2) }}</p>
                <p class="mb-0"><strong>Customer Note:</strong> {{ $return->customer_note ?: 'None' }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0">Update Return Status</h5>
            </div>
            <div class="card-body">
                <form action="{{ admin_route('returns.updateStatus', $return) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Current Status</label>
                        <select name="status" class="form-select fw-bold">
                            <option value="requested" @selected($return->status==='requested')>Requested</option>
                            <option value="approved" @selected($return->status==='approved')>Approved</option>
                            <option value="pickup_scheduled" @selected($return->status==='pickup_scheduled')>Pickup Scheduled</option>
                            <option value="received" @selected($return->status==='received')>Received</option>
                            <option value="inspected" @selected($return->status==='inspected')>Inspected</option>
                            <option value="completed" @selected($return->status==='completed')>Completed (Restock Product)</option>
                            <option value="rejected" @selected($return->status==='rejected')>Rejected</option>
                        </select>
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle text-primary"></i> Restocking only occurs upon setting status to <strong>Completed</strong>.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Admin Notes</label>
                        <textarea name="admin_note" class="form-control" rows="3" placeholder="Inspection notes or comments...">{{ $return->admin_note }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> Update Return</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
