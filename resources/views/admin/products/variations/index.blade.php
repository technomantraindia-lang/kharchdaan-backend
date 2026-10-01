@extends('admin.layouts.app')

@section('title', 'Product Variations - ' . $product->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold m-0"><i class="fas fa-layer-group me-2 text-primary"></i>Variations: {{ $product->name }}</h3>
        <small class="text-muted">SKU: {{ $product->sku }} | WC ID: {{ $product->woocommerce_id ?? 'N/A' }}</small>
    </div>
    <a href="{{ admin_route('products.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back to Products
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <!-- Add New Variation Form -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0 fw-semibold"><i class="fas fa-plus me-2 text-success"></i>Add Variation</h5>
            </div>
            <div class="card-body">
                <form action="{{ admin_route('products.variations.store', $product) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label required">SKU</label>
                        <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku') }}" required>
                        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label required">Regular Price (₹)</label>
                            <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" required>
                            @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Sale Price (₹)</label>
                            <input type="number" step="0.01" name="sale_price" class="form-control @error('sale_price') is-invalid @enderror" value="{{ old('sale_price') }}">
                            @error('sale_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Cost Price (₹)</label>
                            <input type="number" step="0.01" name="cost_price" class="form-control" value="{{ old('cost_price') }}">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label required">Stock Qty</label>
                            <input type="number" name="stock_qty" class="form-control @error('stock_qty') is-invalid @enderror" value="{{ old('stock_qty', 0) }}" required>
                            @error('stock_qty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Weight (kg)</label>
                            <input type="number" step="0.01" name="weight" class="form-control" value="{{ old('weight') }}">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label required">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Attribute</label>
                        <select name="attr_id" class="form-select">
                            <option value="">Select Attribute</option>
                            @foreach($attributes as $attr)
                                <option value="{{ $attr->id }}">{{ $attr->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Attribute Option Value</label>
                        <input type="text" name="attr_val" class="form-control" placeholder="e.g. M, Red, 500g" value="{{ old('attr_val') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Variation Image</label>
                        <input type="file" name="image" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> Save Variation</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Variation Listing -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0 fw-semibold">Existing Variations ({{ $product->variations->count() }})</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>SKU</th>
                                <th>Attribute / Option</th>
                                <th>Price</th>
                                <th>Physical Stock</th>
                                <th>Reserved / Available</th>
                                <th>WC ID / Sync</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($product->variations as $var)
                                <tr>
                                    <td class="fw-bold">{{ $var->sku }}</td>
                                    <td>
                                        @if($var->attribute)
                                            <span class="badge bg-light text-dark border">{{ $var->attribute->name }}: {{ $var->attr_val }}</span>
                                        @else
                                            <span class="text-muted">Default</span>
                                        @endif
                                    </td>
                                    <td>
                                        ₹{{ number_format($var->price, 2) }}
                                        @if($var->sale_price)
                                            <br><small class="text-success fw-bold">Sale: ₹{{ number_format($var->sale_price, 2) }}</small>
                                        @endif
                                    </td>
                                    <td><span class="fw-bold">{{ $var->stock_qty }}</span></td>
                                    <td>
                                        <small class="text-muted">Res: {{ $var->reserved_stock ?? 0 }}</small><br>
                                        <span class="badge bg-success">Avail: {{ $var->available_stock }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">ID: {{ $var->woocommerce_id ?? 'Unsynced' }}</small><br>
                                        <span class="badge {{ $var->woocommerce_sync_status === 'synced' ? 'bg-success' : 'bg-warning text-dark' }}">
                                            {{ ucfirst($var->woocommerce_sync_status ?? 'pending') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $var->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ucfirst($var->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ admin_route('products.variations.destroy', [$product, $var]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete variation {{ $var->sku }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No variations found for this product.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
