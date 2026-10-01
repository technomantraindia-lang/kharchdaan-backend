@extends('admin.layouts.app')

@section('title', 'WooCommerce Sync Conflicts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="fw-bold m-0"><i class="fas fa-exclamation-triangle text-warning me-2"></i>WooCommerce Sync Conflicts</h3>
    <a href="{{ admin_route('woocommerce.index') }}" class="btn btn-outline-secondary"><i class="fas fa-plug me-1"></i> Integration Settings</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-4 border-warning">
            <div class="card-body">
                <div class="text-muted small">Open Conflicts</div>
                <div class="fs-4 fw-bold text-warning">{{ number_format($stats['open']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small">Resolved</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($stats['resolved']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-4 border-secondary">
            <div class="card-body">
                <div class="text-muted small">Ignored</div>
                <div class="fs-4 fw-bold text-secondary">{{ number_format($stats['ignored']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="open" @selected(request('status', 'open')==='open')>Open Conflicts Only</option>
                    <option value="resolved" @selected(request('status')==='resolved')>Resolved Conflicts</option>
                    <option value="ignored" @selected(request('status')==='ignored')>Ignored Conflicts</option>
                </select>
            </div>
            <div class="col-md-4">
                <select name="entity_type" class="form-select">
                    <option value="">All Entity Types</option>
                    <option value="product" @selected(request('entity_type')==='product')>Product</option>
                    <option value="inventory" @selected(request('entity_type')==='inventory')>Inventory</option>
                    <option value="order" @selected(request('entity_type')==='order')>Order</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="{{ admin_route('woocommerce.conflicts.index') }}" class="btn btn-secondary">Reset</a>
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
                    <th>Field</th>
                    <th>Laravel Master Value</th>
                    <th>WooCommerce Value</th>
                    <th>Status / Resolution</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conflicts as $c)
                    <tr>
                        <td><code>#{{ $c->id }}</code></td>
                        <td>
                            <span class="badge bg-secondary">{{ ucfirst($c->entity_type) }}</span>
                            <small class="d-block text-muted">ID: {{ $c->entity_id ?: 'N/A' }} | WC: #{{ $c->woocommerce_id ?: 'N/A' }}</small>
                        </td>
                        <td><code>{{ $c->field_name ?: 'general' }}</code></td>
                        <td>
                            <div class="p-2 bg-light border rounded text-success fw-bold small">
                                <i class="fas fa-database me-1"></i> {{ $c->laravel_value ?: 'Empty' }}
                            </div>
                        </td>
                        <td>
                            <div class="p-2 bg-light border rounded text-primary small">
                                <i class="fab fa-wordpress me-1"></i> {{ $c->woocommerce_value ?: 'Empty' }}
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $c->status==='open'?'warning':($c->status==='resolved'?'success':'secondary') }}">
                                {{ ucfirst($c->status) }}
                            </span>
                            @if($c->resolution)
                                <small class="d-block text-muted">via {{ $c->resolution }}</small>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($c->status === 'open')
                                <div class="dropdown d-inline">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Resolve
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <form action="{{ admin_route('woocommerce.conflicts.resolve', $c) }}" method="POST">
                                                @csrf
                                                <button type="submit" name="resolution" value="use_laravel" class="dropdown-item text-success fw-bold">
                                                    <i class="fas fa-check-circle me-1"></i> Use Laravel (Master)
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ admin_route('woocommerce.conflicts.resolve', $c) }}" method="POST">
                                                @csrf
                                                <button type="submit" name="resolution" value="use_woocommerce" class="dropdown-item text-primary">
                                                    <i class="fab fa-wordpress me-1"></i> Use WooCommerce
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ admin_route('woocommerce.conflicts.resolve', $c) }}" method="POST">
                                                @csrf
                                                <button type="submit" name="resolution" value="ignore" class="dropdown-item text-muted">
                                                    <i class="fas fa-ban me-1"></i> Ignore Conflict
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            @else
                                <span class="text-muted small">Resolved by {{ $c->resolver?->name ?? 'System' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No sync conflicts found matching criteria.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-3">
        {{ $conflicts->links() }}
    </div>
</div>
@endsection
