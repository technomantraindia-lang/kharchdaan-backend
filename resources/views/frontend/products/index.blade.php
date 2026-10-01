@extends('frontend.layouts.app')

@section('title', 'Store Catalog & Products')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-orange-50 text-orange-700 border border-orange-200 mb-2 shadow-2xs">
                <i class="fas fa-boxes-stacked text-xs text-orange-600"></i> "तेरा तुझको अर्पण" Store
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">
                All Products & Starter Packages
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Explore our catalog of direct selling products eligible for Point Volume (PV) and 100% Cashback.
            </p>
        </div>

        <!-- Search Bar -->
        <form method="GET" action="{{ route('products.index') }}" class="flex items-center gap-2 max-w-md w-full">
            <div class="relative flex-grow">
                <i class="fas fa-search absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                <input type="text" name="search" class="w-full pl-9 pr-3 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none transition shadow-xs" placeholder="Search by name or SKU..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-xl transition shadow-xs">
                Search
            </button>
            @if(request('search'))
                <a href="{{ route('products.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Products Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($products as $product)
        <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs hover:shadow-xl hover:-translate-y-1 transition duration-200 flex flex-col group">
            <!-- Thumbnail -->
            <div class="relative bg-slate-50 aspect-square p-4 flex items-center justify-center overflow-hidden border-b border-slate-100">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="max-h-48 w-auto object-contain group-hover:scale-105 transition duration-300" onerror="this.onerror=null;this.src='{{ asset('images/default-product.svg') }}';">
                
                @if($product->sale_price && $product->sale_price < $product->price)
                    @php
                        $discount = round((($product->price - $product->sale_price) / $product->price) * 100);
                    @endphp
                    <span class="absolute top-3 left-3 bg-amber-500 text-slate-900 font-bold text-[10px] px-2 py-0.5 rounded-md shadow-xs">
                        {{ $discount }}% OFF
                    </span>
                @endif

                @if($product->featured)
                    <span class="absolute top-3 right-3 bg-gradient-to-r from-orange-600 to-amber-600 text-white font-bold text-[10px] px-2 py-0.5 rounded-md shadow-xs">
                        Featured
                    </span>
                @endif
            </div>

            <!-- Body -->
            <div class="p-5 flex flex-col flex-grow">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <span class="text-[11px] font-semibold text-orange-600 uppercase tracking-wider">
                        {{ $product->category?->name ?? 'Store Item' }}
                    </span>
                    <span class="text-[10px] font-mono text-slate-400">SKU: {{ $product->sku }}</span>
                </div>

                <h3 class="font-bold text-slate-900 text-sm leading-snug line-clamp-2 hover:text-orange-600 transition mb-2">
                    <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                </h3>

                <p class="text-xs text-slate-500 line-clamp-2 mb-4 flex-grow">
                    {{ $product->short_desc ?? 'Premium quality direct selling product with full GST compliance and cashback eligibility.' }}
                </p>

                <!-- Pricing & Actions -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2 mt-auto">
                    <div>
                        <div class="text-lg font-black text-slate-900 leading-none">
                            ₹{{ number_format(round($product->display_price)) }}
                        </div>
                        @if($product->sale_price && $product->sale_price < $product->price)
                            <div class="text-[11px] text-slate-400 line-through mt-0.5">
                                ₹{{ number_format(round($product->price)) }}
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-1.5">
                        <form action="{{ route('cart.add', $product) }}" method="POST">
                            @csrf
                            <button type="submit" title="Add to Cart" class="w-9 h-9 rounded-xl bg-orange-50 hover:bg-orange-600 text-orange-600 hover:text-white border border-orange-200 hover:border-orange-600 flex items-center justify-center transition shadow-2xs">
                                <i class="fas fa-cart-plus text-xs"></i>
                            </button>
                        </form>
                        <a href="{{ route('products.show', $product->slug) }}" class="px-3 py-2 text-xs font-bold text-white bg-slate-900 hover:bg-orange-600 rounded-xl transition">
                            View
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-4 py-16 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
            <i class="fas fa-box-open text-5xl mb-3 text-slate-300 block"></i>
            <h4 class="text-base font-bold text-slate-700">No products found</h4>
            <p class="text-xs text-slate-400 mt-1">Try different search keywords or check back soon.</p>
        </div>
        @endforelse
    </div>

    @if($products->hasPages())
    <div class="pt-6">
        {{ $products->links() }}
    </div>
    @endif
</div>
@endsection
