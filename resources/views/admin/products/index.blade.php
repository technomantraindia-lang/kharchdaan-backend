@extends('admin.layouts.app')

@section('title', 'Products Catalog')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-boxes-stacked text-blue-600"></i> Store Products & Catalog
            </h1>
            <p class="text-sm text-slate-500 mt-1">Manage e-commerce products, prices, stock levels, categories, and direct selling attributes.</p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="{{ admin_route('products.bulkCreate') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-layer-group text-slate-500"></i> Bulk Add
            </a>
            <a href="{{ admin_route('products.import') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-file-csv text-slate-500"></i> CSV Import
            </a>
            @if(auth()->user()->hasPermission('products.create') || auth()->user()->isSuperAdmin())
                <a href="{{ admin_route('products.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            @endif
        </div>
    </div>

    <!-- Stats KPI Cards -->
    @php
        $totalCount = \App\Models\Product::count();
        $activeCount = \App\Models\Product::where('status', 'active')->count();
        $lowStockCount = \App\Models\Product::whereRaw('stock_qty <= low_stock_qty')->where('stock_qty', '>', 0)->count();
        $outOfStockCount = \App\Models\Product::where('stock_qty', '<=', 0)->count();
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Catalog</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                    <i class="fas fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($totalCount) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Products</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-emerald-600 mt-2">{{ number_format($activeCount) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Low Stock</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-amber-600 mt-2">{{ number_format($lowStockCount) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Out of Stock</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm">
                    <i class="fas fa-box-open"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-rose-600 mt-2">{{ number_format($outOfStockCount) }}</div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
        <form method="GET" action="{{ admin_route('products.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Search Products</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                    <input type="text" name="search" class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" value="{{ request('search') }}" placeholder="Product Name or SKU...">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Category</label>
                <select name="category_id" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status</label>
                <select name="status" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ admin_route('products.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Products Table Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Products List ({{ $products->total() }})</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-4">Product Info</th>
                        <th class="py-3.5 px-4">Category</th>
                        <th class="py-3.5 px-4">Pricing</th>
                        <th class="py-3.5 px-4">Stock Level</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-center">Featured</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-11 h-11 rounded-lg border border-slate-200 object-cover shadow-2xs flex-shrink-0 bg-slate-50" onerror="this.onerror=null;this.src='{{ asset('images/default-product.svg') }}';">
                                <div class="min-w-0">
                                    <a href="{{ admin_route('products.show', $product) }}" class="font-bold text-slate-900 hover:text-blue-600 line-clamp-1">
                                        {{ $product->name }}
                                    </a>
                                    <div class="text-[11px] text-slate-400 font-mono mt-0.5">SKU: {{ $product->sku }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700">
                                {{ $product->category?->name ?? 'Uncategorized' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            @php
                                $priceRange = $product->price_range;
                            @endphp
                            @if($priceRange && $priceRange['min'] < $priceRange['max'])
                                <div class="font-bold text-slate-900">₹{{ number_format($priceRange['min'], 2) }} - ₹{{ number_format($priceRange['max'], 2) }}</div>
                            @else
                                <div class="font-bold text-slate-900">₹{{ number_format($product->display_price, 2) }}</div>
                            @endif
                            @if($product->sale_price && $product->sale_price < $product->price && !$priceRange)
                                <div class="text-[11px] text-slate-400 line-through">₹{{ number_format($product->price, 2) }}</div>
                            @endif
                            @if($product->variations_count > 0 || ($product->relationLoaded('variations') && $product->variations->count() > 0))
                                <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 rounded mt-1">
                                    <i class="fas fa-layer-group text-[9px]"></i> {{ $product->variations_count ?? $product->variations->count() }} Variants
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            @if($product->stock_qty <= 0)
                                <span class="inline-flex items-center gap-1 text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded-full text-[11px]">
                                    <i class="fas fa-circle-xmark text-[10px]"></i> Out of Stock
                                </span>
                            @elseif($product->isLowStock())
                                <span class="inline-flex items-center gap-1 text-amber-600 font-bold bg-amber-50 px-2 py-0.5 rounded-full text-[11px]">
                                    <i class="fas fa-triangle-exclamation text-[10px]"></i> Low: {{ $product->stock_qty }} {{ $product->unit ?? 'pcs' }}
                                </span>
                            @else
                                <span class="text-slate-800 font-semibold">{{ $product->stock_qty }} {{ $product->unit ?? 'pcs' }}</span>
                            @endif
                            @if($product->reserved_stock > 0)
                                <div class="text-[10px] text-slate-400 mt-0.5">Avail: <span class="text-emerald-600 font-medium">{{ $product->available_stock }}</span></div>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <form action="{{ admin_route('products.toggleStatus', $product) }}" method="POST" class="inline-block">
                                @csrf @method('PATCH')
                                <button type="submit" title="Click to toggle status" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold transition {{ $product->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border border-slate-200 hover:bg-slate-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $product->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ ucfirst($product->status) }}
                                </button>
                            </form>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <form action="{{ admin_route('products.toggleFeatured', $product) }}" method="POST" class="inline-block">
                                @csrf @method('PATCH')
                                <button type="submit" title="Click to toggle featured" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold transition {{ $product->featured ? 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200' }}">
                                    <i class="fas fa-star text-[10px] mr-1 {{ $product->featured ? 'text-amber-500' : 'text-slate-400' }}"></i>
                                    {{ $product->featured ? 'Featured' : 'Standard' }}
                                </button>
                            </form>
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="{{ admin_route('products.show', $product) }}" title="View Details" class="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-blue-600 hover:bg-blue-50 flex items-center justify-center transition shadow-2xs">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                @if(auth()->user()->hasPermission('products.edit') || auth()->user()->isSuperAdmin())
                                    <a href="{{ admin_route('products.edit', $product) }}" title="Edit Product" class="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-blue-600 hover:bg-blue-50 flex items-center justify-center transition shadow-2xs">
                                        <i class="fas fa-pen-to-square text-xs"></i>
                                    </a>
                                @endif
                                @if(auth()->user()->hasPermission('products.delete') || auth()->user()->isSuperAdmin())
                                    <form action="{{ admin_route('products.destroy', $product) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Delete Product" class="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition shadow-2xs">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <i class="fas fa-box-open text-4xl mb-3 text-slate-300 block"></i>
                            <p class="font-medium text-slate-600">No products found</p>
                            <p class="text-xs text-slate-400 mt-1">Try adjusting your filters or click below to create a new product.</p>
                            <a href="{{ admin_route('products.create') }}" class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                                <i class="fas fa-plus"></i> Add Product
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $products->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
