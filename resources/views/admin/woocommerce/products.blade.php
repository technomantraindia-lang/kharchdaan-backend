@extends('admin.layouts.app')

@section('title', 'WooCommerce Product Sync')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="fw-bold m-0"><i class="fab fa-wordpress text-primary me-2"></i>WooCommerce Product Sync</h3>
    <a href="{{ admin_route('woocommerce.index') }}" class="btn btn-outline-secondary"><i class="fas fa-plug me-1"></i> Integration Settings</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle me-1"></i> {{ session('info') }}
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
                <div class="text-muted small">Total Products</div>
                <div class="fs-4 fw-bold">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small">Synced</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($stats['synced']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-warning">
            <div class="card-body">
                <div class="text-muted small">Pending / Unsynced</div>
                <div class="fs-4 fw-bold text-warning">{{ number_format($stats['pending']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-danger">
            <div class="card-body">
                <div class="text-muted small">Failed</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($stats['failed']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Search name or SKU..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <select name="sync_status" class="form-select">
                    <option value="">All Sync Statuses</option>
                    <option value="synced" @selected(request('sync_status')==='synced')>Synced</option>
                    <option value="pending" @selected(request('sync_status')==='pending')>Pending</option>
                    <option value="unsynced" @selected(request('sync_status')==='unsynced')>Unsynced (No WC ID)</option>
                    <option value="failed" @selected(request('sync_status')==='failed')>Failed</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="{{ admin_route('woocommerce.products.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<form action="{{ admin_route('woocommerce.products.bulkSync') }}" method="POST" id="bulkForm">
    @csrf
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold">Bulk Actions:</span>
                <button type="submit" name="action" value="sync_selected" class="btn btn-sm btn-outline-primary"><i class="fas fa-sync me-1"></i> Sync Selected</button>
                <button type="submit" name="action" value="sync_unsynced" class="btn btn-sm btn-outline-warning"><i class="fas fa-cloud-upload-alt me-1"></i> Sync All Unsynced</button>
                <button type="submit" name="action" value="retry_failed" class="btn btn-sm btn-outline-danger"><i class="fas fa-redo me-1"></i> Retry Failed</button>
            </div>
            <button type="submit" name="action" value="sync_all" class="btn btn-sm btn-primary" onclick="return confirm('Queue full catalog sync to WooCommerce?')"><i class="fas fa-sync-alt me-1"></i> Sync All Products</button>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>WooCommerce ID</th>
                        <th>Laravel Status</th>
                        <th>Sync Status</th>
                        <th>Last Synced</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        <tr>
                            <td><input type="checkbox" name="selected_ids[]" value="{{ $p->id }}" class="select-item"></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $p->image_url }}" alt="{{ $p->name }}" style="width:40px;height:40px;object-fit:cover;" class="rounded border shadow-sm" onerror="this.onerror=null;this.src='{{ asset('images/default-product.svg') }}';">
                                    <div>
                                        <div class="fw-bold text-dark">{{ $p->name }}</div>
                                        <small class="text-muted">₹{{ number_format($p->price, 2) }} | Stock: {{ $p->available_stock }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><code>{{ $p->sku ?: '-' }}</code></td>
                            <td>
                                @if($p->woocommerce_id)
                                    <span class="badge bg-light text-dark border">#{{ $p->woocommerce_id }}</span>
                                @else
                                    <span class="text-muted small">Not Linked</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $p->status==='active'?'success':'secondary' }}">{{ ucfirst($p->status) }}</span>
                            </td>
                            <td>
                                @php
                                    $status = $p->woocommerce_sync_status ?? 'pending';
                                    $badge = match($status) {
                                        'synced' => 'success',
                                        'syncing' => 'info',
                                        'failed' => 'danger',
                                        default => 'warning'
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }}">{{ ucfirst($status) }}</span>
                            </td>
                            <td>
                                <small class="text-muted">{{ $p->woocommerce_synced_at ? $p->woocommerce_synced_at->diffForHumans() : 'Never' }}</small>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button type="submit" form="sync-single-{{ $p->id }}" class="btn btn-sm btn-outline-primary" title="Sync Product">
                                        <i class="fas fa-sync-alt"></i> Sync
                                    </button>
                                    @if($p->woocommerce_sync_status === 'failed')
                                        <button type="button" class="btn btn-sm btn-outline-danger view-error-btn" data-id="{{ $p->id }}" title="View Error">
                                            <i class="fas fa-exclamation-circle"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No products found matching filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-3">
            {{ $products->links() }}
        </div>
    </div>
</form>

@foreach($products as $p)
    <form id="sync-single-{{ $p->id }}" action="{{ admin_route('woocommerce.products.syncSingle', $p) }}" method="POST" style="display:none;">
        @csrf
    </form>
@endforeach

<div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-circle me-1"></i> Sync Error Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 id="errorProductName" class="fw-bold mb-2"></h6>
                <div class="alert alert-light border small text-wrap text-break mb-2" id="errorText"></div>
                <small class="text-muted d-block" id="errorTime"></small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.select-item').forEach(cb => cb.checked = this.checked);
});

document.querySelectorAll('.view-error-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.id;
        fetch(`/admin/woocommerce/products/${productId}/error`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('errorProductName').textContent = data.product_name;
                document.getElementById('errorText').textContent = data.error;
                document.getElementById('errorTime').textContent = 'Logged at: ' + (data.logged_at || 'N/A');
                const modal = new bootstrap.Modal(document.getElementById('errorModal'));
                modal.show();
            })
            .catch(() => alert('Failed to load error details.'));
    });
});
</script>
@endsection
