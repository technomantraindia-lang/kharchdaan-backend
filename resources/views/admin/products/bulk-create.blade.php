@extends('admin.layouts.app')

@section('title', 'Bulk Add Products')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-layer-group text-blue-600"></i> Bulk Add Products
            </h1>
            <p class="text-sm text-slate-500 mt-1">Quickly add multiple products to your catalog in a single batch.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ admin_route('products.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-arrow-left text-slate-400"></i> Back to Products
            </a>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-xs">
        <form action="{{ admin_route('products.bulkStore') }}" method="POST" id="bulkForm">
            @csrf
            
            <div id="productRows" class="space-y-4 mb-6">
                <!-- Product Row 0 -->
                <div class="product-row bg-slate-50/70 border border-slate-200/80 rounded-xl p-4.5 grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product Name *</label>
                        <input type="text" name="products[0][name]" class="form-control" required placeholder="e.g. Wellness Kit">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">SKU *</label>
                        <input type="text" name="products[0][sku]" class="form-control font-mono" required placeholder="e.g. KD-101">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Category *</label>
                        <select name="products[0][category_id]" class="form-select" required>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Price (₹)</label>
                        <input type="number" step="0.01" name="products[0][price]" class="form-control" value="0">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Stock</label>
                        <input type="number" name="products[0][stock_qty]" class="form-control" value="0">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Unit</label>
                        <select name="products[0][unit]" class="form-select">
                            @foreach($units as $u)
                                <option value="{{ $u }}">{{ $u }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">GST %</label>
                        <input type="number" name="products[0][gst_percentage]" class="form-control" value="5">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                        <select name="products[0][status]" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-100 flex-wrap gap-3">
                <button type="button" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition" id="addRow">
                    <i class="fas fa-plus"></i> Add Another Item
                </button>

                <div class="flex items-center gap-2.5">
                    <a href="{{ admin_route('products.index') }}" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                        <i class="fas fa-check"></i> Save All Products
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let rowIndex = 1;
const categoryOptions = `@foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach`;
const unitOptions = `@foreach($units as $u)<option value="{{ $u }}">{{ $u }}</option>@endforeach`;

document.getElementById('addRow').addEventListener('click', function() {
    const html = `
    <div class="product-row bg-slate-50/70 border border-slate-200/80 rounded-xl p-4.5 grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
        <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Product Name *</label>
            <input type="text" name="products[${rowIndex}][name]" class="form-control" required placeholder="Product name">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 mb-1">SKU *</label>
            <input type="text" name="products[${rowIndex}][sku]" class="form-control font-mono" required placeholder="SKU code">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Category *</label>
            <select name="products[${rowIndex}][category_id]" class="form-select" required>${categoryOptions}</select>
        </div>
        <div class="sm:col-span-1">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Price (₹)</label>
            <input type="number" step="0.01" name="products[${rowIndex}][price]" class="form-control" value="0">
        </div>
        <div class="sm:col-span-1">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Stock</label>
            <input type="number" name="products[${rowIndex}][stock_qty]" class="form-control" value="0">
        </div>
        <div class="sm:col-span-1">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Unit</label>
            <select name="products[${rowIndex}][unit]" class="form-select">${unitOptions}</select>
        </div>
        <div class="sm:col-span-1">
            <label class="block text-xs font-semibold text-slate-700 mb-1">GST %</label>
            <input type="number" name="products[${rowIndex}][gst_percentage]" class="form-control" value="5">
        </div>
        <div class="sm:col-span-1">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
            <select name="products[${rowIndex}][status]" class="form-select">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>`;
    document.getElementById('productRows').insertAdjacentHTML('beforeend', html);
    rowIndex++;
});
</script>
@endsection
