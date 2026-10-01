@extends('admin.layouts.app')

@section('title', 'Manage RTO #' . $rto->rto_num)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0"><i class="fas fa-shipping-fast text-danger me-2"></i>RTO Shipment #{{ $rto->rto_num }}</h3>
    <a href="{{ admin_route('rto.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to RTO List</a>
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
                <h5 class="fw-bold m-0">RTO Details</h5>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>Order #:</strong> <a href="{{ admin_route('orders.show', $rto->order) }}">#{{ $rto->order?->order_num }}</a></p>
                <p class="mb-1"><strong>Tracking / AWB #:</strong> {{ $rto->shipment_id ?: 'N/A' }}</p>
                <p class="mb-1"><strong>Reason:</strong> {{ $rto->reason }}</p>
                <p class="mb-1"><strong>Initiated By:</strong> {{ $rto->creator?->name ?: 'System' }}</p>
                <p class="mb-1"><strong>Received At:</strong> {{ $rto->received_at ? $rto->received_at->format('Y-m-d H:i') : 'Not Yet Received' }}</p>
                <p class="mb-1"><strong>Inspected At:</strong> {{ $rto->inspected_at ? $rto->inspected_at->format('Y-m-d H:i') : 'Not Yet Inspected' }}</p>
                <p class="mb-0"><strong>Restocked At:</strong> {{ $rto->restocked_at ? $rto->restocked_at->format('Y-m-d H:i') : 'Not Restocked Yet' }}</p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0">Order Items in Shipment</h5>
            </div>
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rto->order?->items ?? [] as $item)
                            <tr>
                                <td>{{ $item->product?->name ?: ($item->product_name ?: 'Item') }}</td>
                                <td><code>{{ $item->sku ?: ($item->product?->sku ?: '-') }}</code></td>
                                <td><span class="badge bg-secondary">{{ $item->qty }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0">Update RTO Status</h5>
            </div>
            <div class="card-body">
                <form action="{{ admin_route('rto.updateStatus', $rto) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Current Status</label>
                        <select name="status" class="form-select fw-bold" id="rtoStatusSelect">
                            <option value="rto_initiated" @selected($rto->status==='rto_initiated')>RTO Initiated</option>
                            <option value="rto_in_transit" @selected($rto->status==='rto_in_transit')>In Transit</option>
                            <option value="rto_received" @selected($rto->status==='rto_received')>Received at Hub</option>
                            <option value="rto_inspected" @selected($rto->status==='rto_inspected')>Inspected</option>
                            <option value="rto_restocked" @selected($rto->status==='rto_restocked')>Restocked (Physical Inspection Passed)</option>
                            <option value="rto_damaged" @selected($rto->status==='rto_damaged')>Damaged Goods</option>
                            <option value="rto_closed" @selected($rto->status==='rto_closed')>RTO Closed</option>
                        </select>
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle text-primary"></i> Inventory is ONLY restocked after setting status to <strong>Restocked</strong> upon physical inspection.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Damaged Quantity (if any)</label>
                        <input type="number" name="damaged_qty" class="form-control" value="{{ $rto->damaged_qty }}" min="0">
                        <small class="text-muted">Damaged items will be excluded from inventory restocking.</small>
                    </div>

                    <button type="submit" class="btn btn-danger w-100"><i class="fas fa-save me-1"></i> Update RTO Status</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
