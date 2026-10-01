@extends('admin.layouts.app')

@section('title', 'Global Search Results for: ' . $queryStr)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0"><i class="fas fa-search text-primary me-2"></i>Search Results for "{{ $queryStr }}"</h3>
</div>

<div class="row g-4">
    <!-- Orders Results -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between">
                <span><i class="fas fa-shopping-cart text-primary me-2"></i>Orders</span>
                <span class="badge bg-primary rounded-pill">{{ $results['orders']->count() }}</span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($results['orders'] as $order)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <a href="{{ admin_route('orders.show', $order) }}" class="fw-bold text-decoration-none">
                                    #{{ $order->order_num }}
                                </a>
                                <small class="d-block text-muted">Customer: {{ $order->user?->name ?: 'Guest' }} | ₹{{ number_format($order->total, 2) }}</small>
                            </div>
                            <span class="badge bg-secondary">{{ ucfirst($order->status) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted py-3">No matching orders found.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Products Results -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between">
                <span><i class="fas fa-box text-success me-2"></i>Products</span>
                <span class="badge bg-success rounded-pill">{{ $results['products']->count() }}</span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($results['products'] as $product)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <a href="{{ admin_route('products.edit', $product) }}" class="fw-bold text-decoration-none">
                                    {{ $product->name }}
                                </a>
                                <small class="d-block text-muted">SKU: {{ $product->sku }} | Price: ₹{{ number_format($product->price, 2) }}</small>
                            </div>
                            <span class="badge bg-info text-dark">Stock: {{ $product->available_stock }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted py-3">No matching products found.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Customers Results -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between">
                <span><i class="fas fa-users text-info me-2"></i>Customers / Staff</span>
                <span class="badge bg-info rounded-pill">{{ $results['customers']->count() }}</span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($results['customers'] as $cust)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">{{ $cust->name }}</strong>
                                <small class="text-muted">{{ $cust->email }} | {{ $cust->phone ?: 'No phone' }}</small>
                            </div>
                            <span class="badge bg-secondary">{{ $cust->role?->name ?: 'Customer' }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted py-3">No matching customers found.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Invoices Results -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between">
                <span><i class="fas fa-file-invoice text-warning me-2"></i>Invoices</span>
                <span class="badge bg-warning rounded-pill">{{ $results['invoices']->count() }}</span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($results['invoices'] as $inv)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong>{{ $inv->inv_num }}</strong>
                                <small class="d-block text-muted">Amount: ₹{{ number_format($inv->grand_total ?? $inv->amount ?? 0, 2) }}</small>
                            </div>
                            <span class="badge bg-success">Generated</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted py-3">No matching invoices found.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
