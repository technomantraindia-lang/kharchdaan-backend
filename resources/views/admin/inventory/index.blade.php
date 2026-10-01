@extends('admin.layouts.app')

@section('title', 'Inventory & Stock Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-warehouse text-blue-600"></i> Stock & Inventory Control
            </h1>
            <p class="text-sm text-slate-500 mt-1">Monitor real-time physical stock levels, adjust inventory quantities, and review audit movement logs.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('products.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-boxes-stacked text-slate-400"></i> Products Catalog
            </a>
            <a href="{{ admin_route('products.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                <i class="fas fa-plus"></i> Add Product
            </a>
        </div>
    </div>

    <!-- Stats KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Managed Products</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                    <i class="fas fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($products->total()) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Low Stock Warnings</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-amber-600 mt-2">{{ number_format($lowStockCount) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Out of Stock Alerts</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm">
                    <i class="fas fa-circle-xmark"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-rose-600 mt-2">{{ number_format($outOfStockCount) }}</div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
        <form method="GET" action="{{ admin_route('inventory.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="sm:col-span-3">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Search Inventory</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                    <input type="text" name="search" class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" placeholder="Search product name or SKU..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-filter mr-1"></i> Search
                </button>
                <a href="{{ admin_route('inventory.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Inventory Adjustment Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Current Stock Levels & Quick Adjustment</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-4">Product Info</th>
                        <th class="py-3.5 px-4">Category</th>
                        <th class="py-3.5 px-4">Current Stock</th>
                        <th class="py-3.5 px-4">Stock Status</th>
                        <th class="py-3.5 px-4">Stock Adjustment Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-slate-900">{{ $product->name }}</div>
                            <div class="text-[11px] text-slate-400 font-mono mt-0.5">SKU: {{ $product->sku }}</div>
                        </td>
                        <td class="py-3.5 px-4 text-slate-600">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] bg-slate-100 text-slate-700">
                                {{ $product->category?->name ?? 'Uncategorized' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-slate-900 text-sm">{{ $product->stock_qty }} <span class="text-xs font-normal text-slate-500">{{ $product->unit ?? 'pcs' }}</span></div>
                            @if($product->reserved_stock > 0)
                                <div class="text-[10px] text-slate-400 mt-0.5">Reserved: <span class="text-amber-600 font-medium">{{ $product->reserved_stock }}</span></div>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            @if($product->stock_qty <= 0)
                                <span class="inline-flex items-center gap-1 text-rose-600 font-bold bg-rose-50 px-2.5 py-0.5 rounded-full text-[11px] border border-rose-200">
                                    <i class="fas fa-circle-xmark text-[10px]"></i> Out of Stock
                                </span>
                            @elseif($product->isLowStock())
                                <span class="inline-flex items-center gap-1 text-amber-600 font-bold bg-amber-50 px-2.5 py-0.5 rounded-full text-[11px] border border-amber-200">
                                    <i class="fas fa-triangle-exclamation text-[10px]"></i> Low: &le; {{ $product->low_stock_qty }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-emerald-600 font-semibold bg-emerald-50 px-2.5 py-0.5 rounded-full text-[11px] border border-emerald-200">
                                    <i class="fas fa-circle-check text-[10px]"></i> Healthy Stock
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            <form action="{{ admin_route('inventory.update', $product) }}" method="POST" class="inline-flex items-center gap-1.5 flex-wrap">
                                @csrf @method('PUT')
                                <select name="action" class="form-select text-xs py-1 px-2 rounded-lg bg-slate-50 border border-slate-200" style="width:90px">
                                    <option value="add">+ Add</option>
                                    <option value="reduce">- Reduce</option>
                                    <option value="set">= Set To</option>
                                </select>
                                <input type="number" name="quantity" class="form-control text-xs py-1 px-2 rounded-lg bg-slate-50 border border-slate-200" style="width:75px" min="0" placeholder="Qty" required>
                                <input type="text" name="note" class="form-control text-xs py-1 px-2 rounded-lg bg-slate-50 border border-slate-200" style="width:130px" placeholder="Audit note...">
                                <button type="submit" class="px-3 py-1 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                                    Apply
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400">
                            <i class="fas fa-warehouse text-4xl mb-3 text-slate-300 block"></i>
                            <p class="font-medium text-slate-600">No inventory records found</p>
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

    <!-- Recent Inventory Logs Audit Table -->
    @if($recentLogs->count())
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-clock-rotate-left text-slate-500"></i> Recent Stock Audit Logs
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider text-[10px]">
                        <th class="py-2.5 px-4">Date / Time</th>
                        <th class="py-2.5 px-4">Product</th>
                        <th class="py-2.5 px-4">Adjustment Type</th>
                        <th class="py-2.5 px-4 text-center">Previous</th>
                        <th class="py-2.5 px-4 text-center">Change</th>
                        <th class="py-2.5 px-4 text-center">New Total</th>
                        <th class="py-2.5 px-4">Changed By</th>
                        <th class="py-2.5 px-4">Audit Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($recentLogs as $log)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-2.5 px-4 text-slate-500 whitespace-nowrap">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                        <td class="py-2.5 px-4 font-bold text-slate-900">{{ $log->product?->name ?? 'Deleted Item' }}</td>
                        <td class="py-2.5 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                {{ ucwords(str_replace('_', ' ', $log->type)) }}
                            </span>
                        </td>
                        <td class="py-2.5 px-4 text-center font-mono text-slate-500">{{ $log->old_qty }}</td>
                        <td class="py-2.5 px-4 text-center font-mono font-bold {{ $log->change_qty > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $log->change_qty > 0 ? '+' : '' }}{{ $log->change_qty }}
                        </td>
                        <td class="py-2.5 px-4 text-center font-mono font-bold text-slate-900">{{ $log->new_qty }}</td>
                        <td class="py-2.5 px-4 text-slate-700 font-medium">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="py-2.5 px-4 text-slate-500 max-w-xs truncate">{{ $log->note ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
