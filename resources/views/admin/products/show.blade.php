@extends('admin.layouts.app')

@section('title', 'Product: ' . $product->name)

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Top Action Bar & Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-mono font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    SKU: {{ $product->sku }}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $product->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $product->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                    {{ ucfirst($product->status) }}
                </span>
                @if($product->featured)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                        <i class="fas fa-star text-amber-500 mr-1 text-[10px]"></i> Featured
                    </span>
                @endif
                @if($product->stock_qty <= 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                        <i class="fas fa-circle-xmark mr-1 text-[10px]"></i> Out of Stock
                    </span>
                @elseif($product->isLowStock())
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        <i class="fas fa-triangle-exclamation mr-1 text-[10px]"></i> Low Stock
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                        <i class="fas fa-circle-check mr-1 text-[10px]"></i> In Stock
                    </span>
                @endif
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                {{ $product->name }}
            </h1>
        </div>

        <!-- Controls / Actions -->
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ admin_route('products.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-arrow-left text-slate-400"></i> Back to Products
            </a>
            <a href="{{ admin_route('products.variations.index', $product) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-sliders-h text-indigo-500"></i> Variations ({{ $product->variations->count() }})
            </a>
            @if(auth()->user()->hasPermission('products.edit') || auth()->user()->isSuperAdmin())
                <a href="{{ admin_route('products.edit', $product) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-pen-to-square"></i> Edit Product
                </a>
            @endif
            @if(auth()->user()->hasPermission('products.delete') || auth()->user()->isSuperAdmin())
                <form action="{{ admin_route('products.destroy', $product) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- 4 Key Metric Stat Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <!-- Display Price Card -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Selling Price</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                    <i class="fas fa-indian-rupee-sign"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">
                ₹{{ number_format($product->display_price, 2) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1.5">
                @if($product->sale_price && $product->sale_price < $product->price)
                    <span class="line-through text-slate-400">₹{{ number_format($product->price, 2) }}</span>
                    @php
                        $discountPct = round((($product->price - $product->sale_price) / $product->price) * 100);
                    @endphp
                    <span class="text-emerald-600 font-bold bg-emerald-50 px-1.5 py-0.2 rounded text-[10px]">{{ $discountPct }}% OFF</span>
                @else
                    <span class="text-slate-400">Regular retail price</span>
                @endif
            </div>
        </div>

        <!-- Stock Availability Card -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock Available</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fas fa-warehouse"></i>
                </div>
            </div>
            <div class="text-2xl font-bold {{ $product->available_stock > 0 ? 'text-emerald-600' : 'text-rose-600' }} mt-2">
                {{ $product->available_stock }} <span class="text-xs font-normal text-slate-500">{{ $product->unit ?? 'pcs' }}</span>
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                Physical: <span class="font-semibold text-slate-700">{{ $product->stock_qty }}</span> | Reserved: <span class="text-amber-600 font-semibold">{{ $product->reserved_stock ?? 0 }}</span>
            </div>
        </div>

        <!-- Cost Price & Margin Card -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Cost Price</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">
                ₹{{ number_format($product->cost_price ?? 0, 2) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                @if($product->cost_price > 0 && $product->display_price > $product->cost_price)
                    @php
                        $margin = (($product->display_price - $product->cost_price) / $product->display_price) * 100;
                    @endphp
                    Est. Margin: <span class="text-purple-600 font-bold">{{ round($margin, 1) }}%</span>
                @else
                    <span class="text-slate-400">Internal procurement cost</span>
                @endif
            </div>
        </div>

        <!-- Tax & HSN Card -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tax & Compliance</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">
                {{ $product->gst_percentage ?? 5 }}% <span class="text-xs font-normal text-slate-500">GST</span>
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                HSN: <span class="font-mono font-semibold text-slate-700">{{ $product->hsn_code ?? 'Not Specified' }}</span>
            </div>
        </div>
    </div>

    <!-- Main 2-Column Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left Column: Media Gallery, Stock Parameters, Quick Toggles (5 cols) -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- Product Media Card -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                        <i class="fas fa-image text-blue-500"></i> Product Media
                    </h3>
                    <span class="text-[11px] text-slate-400">{{ 1 + $product->images->count() }} image(s)</span>
                </div>

                <!-- Main Featured Image -->
                <div class="relative bg-slate-50 rounded-xl border border-slate-200/80 p-4 flex items-center justify-center min-h-[260px] overflow-hidden group">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="max-h-64 w-auto object-contain rounded-lg transition duration-300 group-hover:scale-105" onerror="this.onerror=null;this.src='{{ asset('images/default-product.svg') }}';">
                </div>

                <!-- Additional Gallery Images -->
                @if($product->images->count())
                <div class="mt-4">
                    <div class="text-[11px] font-semibold text-slate-500 mb-2">Gallery Assets</div>
                    <div class="grid grid-cols-4 gap-2.5">
                        @foreach($product->images as $img)
                        <div class="relative bg-slate-50 rounded-lg border border-slate-200 p-1 aspect-square flex items-center justify-center overflow-hidden">
                            <img src="{{ $img->image_url }}" alt="{{ $product->name }} thumbnail" class="h-full w-full object-cover rounded">
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Inventory & Stock Settings Card -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                        <i class="fas fa-cubes text-emerald-500"></i> Stock & Thresholds
                    </h3>
                    <a href="{{ admin_route('inventory.index') }}" class="text-[11px] font-semibold text-blue-600 hover:underline">
                        Inventory Manager &rarr;
                    </a>
                </div>

                <dl class="divide-y divide-slate-100 text-xs">
                    <div class="py-2.5 flex justify-between items-center">
                        <dt class="text-slate-500">Physical Stock</dt>
                        <dd class="font-bold text-slate-900">{{ $product->stock_qty }} {{ $product->unit ?? 'pcs' }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between items-center">
                        <dt class="text-slate-500">Reserved in Orders/Cart</dt>
                        <dd class="font-semibold text-amber-600">{{ $product->reserved_stock ?? 0 }} {{ $product->unit ?? 'pcs' }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between items-center">
                        <dt class="text-slate-500">Net Available for Sale</dt>
                        <dd class="font-bold text-emerald-600">{{ $product->available_stock }} {{ $product->unit ?? 'pcs' }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between items-center">
                        <dt class="text-slate-500">Low Stock Alert Threshold</dt>
                        <dd class="font-semibold text-slate-700">{{ $product->low_stock_qty ?? 5 }} {{ $product->unit ?? 'pcs' }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between items-center">
                        <dt class="text-slate-500">Min. Order Quantity</dt>
                        <dd class="font-semibold text-slate-700">{{ $product->min_order_qty ?? 1 }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between items-center">
                        <dt class="text-slate-500">Item Weight</dt>
                        <dd class="font-semibold text-slate-700">{{ $product->weight ? $product->weight . ' kg' : 'Not specified' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Quick Status Toggles Card -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-2">
                    <i class="fas fa-toggle-on text-blue-500"></i> Quick Status Controls
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <form action="{{ admin_route('products.toggleStatus', $product) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-2 px-3 text-xs font-semibold rounded-lg border transition {{ $product->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                            <span class="w-2 h-2 rounded-full {{ $product->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                            {{ $product->status === 'active' ? 'Active Status' : 'Inactive Status' }}
                        </button>
                    </form>

                    <form action="{{ admin_route('products.toggleFeatured', $product) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-2 px-3 text-xs font-semibold rounded-lg border transition {{ $product->featured ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                            <i class="fas fa-star text-[11px] {{ $product->featured ? 'text-amber-500' : 'text-slate-400' }}"></i>
                            {{ $product->featured ? 'Featured' : 'Standard' }}
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <!-- Right Column: Catalog Details, Description, Variations, History (7 cols) -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- Categorization & Hierarchy Card -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                        <i class="fas fa-sitemap text-blue-500"></i> Category & Brand Information
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="bg-slate-50 rounded-lg p-3 border border-slate-200/60">
                        <div class="text-slate-400 font-medium text-[11px] mb-1">Category Hierarchy</div>
                        <div class="font-bold text-slate-900 flex items-center gap-1.5 flex-wrap">
                            @if($product->category)
                                <span>{{ $product->category->name }}</span>
                                @if($product->subCategory)
                                    <i class="fas fa-angle-right text-slate-400 text-[10px]"></i>
                                    <span>{{ $product->subCategory->name }}</span>
                                @endif
                                @if($product->subSubCategory)
                                    <i class="fas fa-angle-right text-slate-400 text-[10px]"></i>
                                    <span>{{ $product->subSubCategory->name }}</span>
                                @endif
                            @else
                                <span class="text-slate-400">Uncategorized</span>
                            @endif
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-lg p-3 border border-slate-200/60">
                        <div class="text-slate-400 font-medium text-[11px] mb-1">Brand / Manufacturer</div>
                        <div class="font-bold text-slate-900">
                            {{ $product->brand?->name ?? 'None / House Brand' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Description & Overview Card -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                        <i class="fas fa-file-lines text-blue-500"></i> Product Descriptions
                    </h3>
                </div>

                @if($product->short_desc)
                <div>
                    <div class="text-xs font-bold text-slate-700 mb-1">Short Summary</div>
                    <div class="p-3 bg-blue-50/50 border border-blue-100 rounded-lg text-xs text-slate-700 leading-relaxed">
                        {{ $product->short_desc }}
                    </div>
                </div>
                @endif

                <div>
                    <div class="text-xs font-bold text-slate-700 mb-1">Detailed Description</div>
                    @if($product->description)
                        <div class="prose prose-sm max-w-none text-xs text-slate-600 bg-slate-50/60 p-4 rounded-lg border border-slate-200/70 leading-relaxed whitespace-pre-line">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    @else
                        <div class="text-xs text-slate-400 italic py-2">No full description provided.</div>
                    @endif
                </div>
            </div>

            <!-- Variations & Attributes Card -->
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                        <i class="fas fa-layer-group text-indigo-500"></i> Product Variations ({{ $product->variations->count() }})
                    </h3>
                    <a href="{{ admin_route('products.variations.index', $product) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700">
                        <i class="fas fa-plus text-[10px]"></i> Manage Variations
                    </a>
                </div>

                @if($product->variations->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider text-[10px]">
                                <th class="py-2.5 px-4">Variant SKU</th>
                                <th class="py-2.5 px-4">Attribute / Option</th>
                                <th class="py-2.5 px-4">Price</th>
                                <th class="py-2.5 px-4">Stock</th>
                                <th class="py-2.5 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($product->variations as $variation)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-2.5 px-4 font-mono font-semibold text-slate-800">{{ $variation->sku }}</td>
                                <td class="py-2.5 px-4 text-slate-700 font-medium">
                                    @if($variation->attribute)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            {{ $variation->attribute->name }}: {{ $variation->attr_val }}
                                        </span>
                                    @elseif($variation->attr_val)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ $variation->attr_val }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">Default</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4">
                                    <div class="font-bold text-slate-900">₹{{ number_format($variation->sale_price ?? $variation->price, 2) }}</div>
                                    @if($variation->sale_price && $variation->sale_price < $variation->price)
                                        <div class="text-[10px] text-slate-400 line-through">₹{{ number_format($variation->price, 2) }}</div>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4">
                                    <span class="font-semibold text-slate-800">{{ $variation->stock_qty }}</span>
                                    @if($variation->reserved_stock > 0)
                                        <span class="text-[10px] text-amber-600 ml-1">(Res: {{ $variation->reserved_stock }})</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $variation->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                        {{ ucfirst($variation->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="p-6 text-center text-slate-400">
                    <i class="fas fa-sliders-h text-2xl text-slate-300 mb-1.5 block"></i>
                    <span class="text-xs">No variations configured for this product.</span>
                </div>
                @endif
            </div>

            <!-- Inventory History Audit Log Card -->
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                        <i class="fas fa-clock-rotate-left text-slate-600"></i> Stock Movement & Audit Logs
                    </h3>
                </div>

                @if($product->inventoryLogs->count())
                <div class="overflow-x-auto max-h-80">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="sticky top-0 bg-slate-50/95 backdrop-blur-xs z-10">
                            <tr class="border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider text-[10px]">
                                <th class="py-2.5 px-4">Date / Time</th>
                                <th class="py-2.5 px-4">Event Type</th>
                                <th class="py-2.5 px-4 text-center">Prev</th>
                                <th class="py-2.5 px-4 text-center">Change</th>
                                <th class="py-2.5 px-4 text-center">New</th>
                                <th class="py-2.5 px-4">User / Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($product->inventoryLogs as $log)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-2.5 px-4 text-slate-500 whitespace-nowrap">
                                    {{ $log->created_at->format('M d, Y h:i A') }}
                                </td>
                                <td class="py-2.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        {{ ucwords(str_replace('_', ' ', $log->type)) }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-center text-slate-500 font-mono">{{ $log->old_qty }}</td>
                                <td class="py-2.5 px-4 text-center font-bold font-mono {{ $log->change_qty > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $log->change_qty > 0 ? '+' : '' }}{{ $log->change_qty }}
                                </td>
                                <td class="py-2.5 px-4 text-center font-bold text-slate-900 font-mono">{{ $log->new_qty }}</td>
                                <td class="py-2.5 px-4 text-slate-600">
                                    <div class="text-[11px] font-medium text-slate-800">{{ $log->user?->name ?? 'System' }}</div>
                                    <div class="text-[10px] text-slate-400 truncate max-w-xs">{{ $log->note ?? '-' }}</div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="p-6 text-center text-slate-400">
                    <i class="fas fa-list-check text-2xl text-slate-300 mb-1.5 block"></i>
                    <span class="text-xs">No inventory movements recorded yet.</span>
                </div>
                @endif
            </div>

            <!-- SEO Meta Card -->
            @if($product->seo_title || $product->seo_desc || $product->seo_keywords || $product->slug)
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                    <i class="fas fa-globe text-blue-500"></i> SEO & Search Visibility
                </h3>
                <div class="bg-slate-50 rounded-lg p-3 border border-slate-200 text-xs space-y-1">
                    <div class="text-blue-700 font-bold">{{ $product->seo_title ?? $product->name }}</div>
                    <div class="text-emerald-700 font-mono text-[11px]">{{ url('/products/' . $product->slug) }}</div>
                    <div class="text-slate-600 text-[11px]">{{ $product->seo_desc ?? $product->short_desc ?? 'No meta description set.' }}</div>
                </div>
            </div>
            @endif

        </div>

    </div>
</div>
@endsection
