@extends('admin.layouts.app')

@section('title', 'WooCommerce Sync Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="fw-bold m-0"><i class="fas fa-history text-primary me-2"></i>WooCommerce Sync Logs</h3>
    <a href="{{ admin_route('woocommerce.index') }}" class="btn btn-outline-secondary"><i class="fas fa-plug me-1"></i> Integration Settings</a>
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

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-primary">
            <div class="card-body">
                <div class="text-muted small">Total Log Records</div>
                <div class="fs-4 fw-bold">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small">Successful</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($stats['success']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-danger">
            <div class="card-body">
                <div class="text-muted small">Failed Logs</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($stats['failed']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-warning">
            <div class="card-body">
                <div class="text-muted small">Pending / In Progress</div>
                <div class="fs-4 fw-bold text-warning">{{ number_format($stats['pending']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-2">
                <select name="entity_type" class="form-select">
                    <option value="">All Entities</option>
                    <option value="product" @selected(request('entity_type')==='product')>Product</option>
                    <option value="variation" @selected(request('entity_type')==='variation')>Variation</option>
                    <option value="order" @selected(request('entity_type')==='order')>Order</option>
                    <option value="customer" @selected(request('entity_type')==='customer')>Customer</option>
                    <option value="inventory" @selected(request('entity_type')==='inventory')>Inventory</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="direction" class="form-select">
                    <option value="">All Directions</option>
                    <option value="laravel_to_woocommerce" @selected(request('direction')==='laravel_to_woocommerce')>Laravel &rarr; WC</option>
                    <option value="woocommerce_to_laravel" @selected(request('direction')==='woocommerce_to_laravel')>WC &rarr; Laravel</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="success" @selected(request('status')==='success')>Success</option>
                    <option value="failed" @selected(request('status')==='failed')>Failed</option>
                    <option value="pending" @selected(request('status')==='pending')>Pending</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From Date">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To Date">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="{{ admin_route('woocommerce.sync-logs.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Entity</th>
                    <th>Direction</th>
                    <th>Action</th>
                    <th>Status</th>
                    <th>IDs (Laravel / WC)</th>
                    <th>Timestamp</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td><code>#{{ $log->id }}</code></td>
                        <td><span class="badge bg-secondary">{{ ucfirst($log->entity_type) }}</span></td>
                        <td>
                            @if($log->direction === 'laravel_to_woocommerce')
                                <small class="text-primary"><i class="fas fa-arrow-right"></i> Laravel &rarr; WC</small>
                            @else
                                <small class="text-info"><i class="fas fa-arrow-left"></i> WC &rarr; Laravel</small>
                            @endif
                        </td>
                        <td><code>{{ $log->action }}</code></td>
                        <td>
                            <span class="badge bg-{{ $log->status==='success'?'success':($log->status==='failed'?'danger':'warning') }}">
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>
                        <td>
                            <small>
                                Laravel ID: <strong>{{ $log->entity_id ?: '-' }}</strong><br>
                                WC ID: <strong>{{ $log->woocommerce_id ? '#'.$log->woocommerce_id : '-' }}</strong>
                            </small>
                        </td>
                        <td><small class="text-muted">{{ $log->created_at->format('Y-m-d H:i:s') }}</small></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-info view-detail-btn" data-id="{{ $log->id }}" title="View Payload & Details">
                                <i class="fas fa-eye"></i> Details
                            </button>
                            @if($log->status === 'failed')
                                <form action="{{ admin_route('woocommerce.sync-logs.retry', $log) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Retry Operation">
                                        <i class="fas fa-redo"></i> Retry
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No sync logs found matching criteria.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-3">
        {{ $logs->links() }}
    </div>
</div>

<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-code me-1"></i> Sync Log Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="logErrorMsg" class="alert alert-danger d-none mb-3"></div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6"><strong>Entity:</strong> <span id="modalEntity"></span></div>
                    <div class="col-md-6"><strong>Direction:</strong> <span id="modalDirection"></span></div>
                    <div class="col-md-6"><strong>Action:</strong> <span id="modalAction"></span></div>
                    <div class="col-md-6"><strong>Status:</strong> <span id="modalStatus"></span></div>
                </div>
                <h6 class="fw-bold">Request Payload (Sanitized)</h6>
                <pre id="modalRequest" class="bg-dark text-light p-3 rounded small" style="max-height: 200px; overflow-y: auto;"></pre>
                <h6 class="fw-bold mt-3">Response Payload</h6>
                <pre id="modalResponse" class="bg-dark text-light p-3 rounded small" style="max-height: 200px; overflow-y: auto;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.view-detail-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        fetch(`/admin/woocommerce/sync-logs/${id}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('modalEntity').textContent = data.entity_type + ' (ID: ' + (data.entity_id || 'N/A') + ')';
                document.getElementById('modalDirection').textContent = data.direction;
                document.getElementById('modalAction').textContent = data.action;
                document.getElementById('modalStatus').textContent = data.status;

                const errBox = document.getElementById('logErrorMsg');
                if (data.error_message) {
                    errBox.textContent = 'Error: ' + data.error_message;
                    errBox.classList.remove('d-none');
                } else {
                    errBox.classList.add('d-none');
                }

                document.getElementById('modalRequest').textContent = data.request_payload ? JSON.stringify(data.request_payload, null, 2) : 'None';
                document.getElementById('modalResponse').textContent = data.response_payload ? JSON.stringify(data.response_payload, null, 2) : 'None';

                const modal = new bootstrap.Modal(document.getElementById('logDetailModal'));
                modal.show();
            });
    });
});
</script>
@endsection
