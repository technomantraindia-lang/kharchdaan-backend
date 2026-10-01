@extends('frontend.layouts.app')

@section('title', $product->name . ' - Store')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex items-center text-xs font-semibold text-slate-500 gap-2">
        <a href="{{ route('home') }}" class="hover:text-orange-600 transition"><i class="fas fa-house"></i></a>
        <span>/</span>
        <a href="{{ route('products.index') }}" class="hover:text-orange-600 transition">Products</a>
        @if($product->category)
            <span>/</span>
            <span class="text-slate-600">{{ $product->category->name }}</span>
        @endif
        <span>/</span>
        <span class="text-slate-900 font-bold truncate max-w-xs">{{ $product->name }}</span>
    </nav>

    <!-- Main 2-Column Product Detail -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-10 shadow-xs">
        
        <!-- Left: Image Gallery (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="relative bg-slate-50 rounded-2xl border border-slate-200/80 p-6 aspect-square flex items-center justify-center overflow-hidden">
                <img id="mainImg" src="{{ $product->image_url }}" alt="{{ $product->name }}" class="max-h-80 w-auto object-contain transition duration-200" onerror="this.onerror=null;this.src='{{ asset('images/default-product.svg') }}';">
            </div>

            @if($product->images->count())
            <div class="flex items-center gap-3 overflow-x-auto pb-2">
                <button type="button" onclick="document.getElementById('mainImg').src='{{ $product->image_url }}'" class="w-16 h-16 rounded-xl border-2 border-orange-600 p-1 bg-slate-50 flex items-center justify-center flex-shrink-0">
                    <img src="{{ $product->image_url }}" alt="main thumbnail" class="max-h-full max-w-full object-contain">
                </button>
                @foreach($product->images as $img)
                <button type="button" onclick="document.getElementById('mainImg').src='{{ $img->image_url }}'" class="w-16 h-16 rounded-xl border border-slate-200 hover:border-orange-600 p-1 bg-slate-50 flex items-center justify-center flex-shrink-0 transition">
                    <img src="{{ $img->image_url }}" alt="gallery thumbnail" class="max-h-full max-w-full object-contain">
                </button>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Right: Product Information & Buy Controls (7 cols) -->
        <div class="lg:col-span-7 space-y-6">
            <div class="space-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-50 text-orange-700 border border-orange-200">
                        {{ $product->category?->name ?? 'Direct Selling Product' }}
                    </span>
                    <span class="text-xs font-mono text-slate-400">SKU: {{ $product->sku }}</span>
                    @if($product->featured)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gradient-to-r from-orange-600 to-amber-600 text-white shadow-2xs">
                            <i class="fas fa-star text-amber-200 mr-1 text-[10px]"></i> Featured
                        </span>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    {{ $product->name }}
                </h1>

                @if($product->brand)
                    <div class="text-xs text-slate-500">Brand: <span class="font-bold text-slate-700">{{ $product->brand->name }}</span></div>
                @endif
            </div>

            <!-- Pricing Box -->
            <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200/80 flex items-center justify-between flex-wrap gap-4">
                <div>
                    <div class="text-xs text-slate-500 font-medium">Special Member / Retail Price</div>
                    <div class="flex items-baseline gap-3 mt-1">
                        <span class="text-3xl font-black text-slate-900">₹{{ number_format(round($product->display_price)) }}</span>
                        @if($product->sale_price && $product->sale_price < $product->price)
                            <span class="text-base text-slate-400 line-through">₹{{ number_format(round($product->price)) }}</span>
                            @php
                                $discountPct = round((($product->price - $product->sale_price) / $product->price) * 100);
                            @endphp
                            <span class="text-xs font-bold bg-amber-500 text-slate-900 px-2 py-0.5 rounded-md">{{ $discountPct }}% OFF</span>
                        @endif
                    </div>
                </div>

                <div class="text-right text-xs">
                    <div class="font-bold {{ $product->isInStock() ? 'text-emerald-600' : 'text-rose-600' }}">
                        <i class="fas {{ $product->isInStock() ? 'fa-circle-check' : 'fa-circle-xmark' }} mr-1"></i>
                        {{ $product->isInStock() ? 'In Stock (' . $product->stock_qty . ' available)' : 'Out of Stock' }}
                    </div>
                    <div class="text-slate-400 mt-0.5">GST Rate: {{ $product->gst_percentage }}% included</div>
                </div>
            </div>

            <!-- Short description -->
            @if($product->short_desc)
                <p class="text-sm text-slate-600 leading-relaxed">
                    {{ $product->short_desc }}
                </p>
            @endif

            <!-- Add to Cart & Buy Now -->
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <form action="{{ route('cart.add', $product) }}" method="POST" class="flex-grow sm:flex-grow-0">
                    @csrf
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 text-xs font-bold text-orange-700 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-xl transition">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                </form>

                <form action="{{ route('cart.buyNow', $product) }}" method="POST" class="flex-grow">
                    @csrf
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-8 py-3.5 text-xs font-extrabold text-white bg-gradient-to-r from-orange-600 via-orange-500 to-amber-500 hover:from-orange-700 hover:to-amber-600 rounded-xl shadow-md shadow-orange-500/25 transition">
                        <i class="fas fa-bolt"></i> Buy Now & Earn PV
                    </button>
                </form>
            </div>

            <!-- Product Specs -->
            <div class="pt-6 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                <div>
                    <span class="text-slate-400 block">Min. Order Qty</span>
                    <span class="font-bold text-slate-800">{{ $product->min_order_qty }} {{ $product->unit ?? 'pcs' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block">Item Weight</span>
                    <span class="font-bold text-slate-800">{{ $product->weight ? $product->weight . ' kg' : 'Standard' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block">Cashback Eligible</span>
                    <span class="font-bold text-orange-600">100% Conditional Pool ("तेरा तुझको अर्पण")</span>
                </div>
            </div>

            <!-- Full Description -->
            @if($product->description)
            <div class="pt-6 border-t border-slate-100 space-y-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Product Details & Specifications</h3>
                <div class="text-xs text-slate-600 leading-relaxed whitespace-pre-line bg-slate-50 p-4 rounded-xl border border-slate-200/70">
                    {!! nl2br(e($product->description)) !!}
                </div>
            </div>
            @endif

        </div>

    </div>
</div>
@endsection
