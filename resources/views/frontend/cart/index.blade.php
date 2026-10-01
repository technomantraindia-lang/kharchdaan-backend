@extends('frontend.layouts.app')

@section('title', 'Your Shopping Cart')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
            <i class="fas fa-bag-shopping text-orange-600"></i> Shopping Cart
        </h1>
        <p class="text-sm text-slate-500 mt-1">Review your selected items before proceeding to secure direct checkout.</p>
    </div>

    @if(count($items))
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Items Table (8 cols) -->
            <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                                <th class="py-3.5 px-5">Product</th>
                                <th class="py-3.5 px-5 text-center">Qty</th>
                                <th class="py-3.5 px-5">Price</th>
                                <th class="py-3.5 px-5">Total</th>
                                <th class="py-3.5 px-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($items as $item)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $item['product']->image_url }}" alt="{{ $item['product']->name }}" class="w-12 h-12 rounded-xl object-contain border border-slate-200 bg-slate-50 flex-shrink-0" onerror="this.onerror=null;this.src='{{ asset('images/default-product.svg') }}';">
                                        <div>
                                            <div class="font-bold text-slate-900 text-sm hover:text-orange-600 transition">{{ $item['product']->name }}</div>
                                            <div class="text-[11px] text-slate-400 font-mono mt-0.5">SKU: {{ $item['product']->sku }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-5 text-center font-bold text-slate-900 text-sm">
                                    {{ $item['qty'] }}
                                </td>
                                <td class="py-4 px-5 text-slate-700 font-semibold">
                                    ₹{{ number_format(round($item['price'])) }}
                                </td>
                                <td class="py-4 px-5 font-bold text-slate-900 text-sm">
                                    ₹{{ number_format(round($item['line_total'])) }}
                                </td>
                                <td class="py-4 px-5 text-right">
                                    <form action="{{ route('cart.remove', $item['product']) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" title="Remove Item" class="w-8 h-8 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-orange-600 hover:underline">
                        <i class="fas fa-arrow-left text-[10px]"></i> Continue Shopping
                    </a>
                </div>
            </div>

            <!-- Order Summary (4 cols) -->
            <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-5">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">
                    Order Summary
                </h3>

                <dl class="space-y-3 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <dt>Subtotal ({{ count($items) }} items)</dt>
                        <dd class="font-bold text-slate-900">₹{{ number_format(round($subtotal)) }}</dd>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <dt>100% Cashback Eligibility</dt>
                        <dd class="font-bold text-orange-600">Qualified ("तेरा तुझको अर्पण")</dd>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <dt>Estimated Taxes (GST)</dt>
                        <dd class="font-medium text-slate-700">Calculated at Checkout</dd>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex justify-between text-sm font-black text-slate-900">
                        <dt>Total Amount</dt>
                        <dd class="text-xl text-orange-600">₹{{ number_format(round($subtotal)) }}</dd>
                    </div>
                </dl>

                @auth
                    <a href="{{ route('products.index') }}" class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 text-xs font-extrabold text-white bg-gradient-to-r from-orange-600 via-orange-500 to-amber-500 hover:from-orange-700 hover:to-amber-600 rounded-xl shadow-md shadow-orange-500/25 transition">
                        <i class="fas fa-lock"></i> Proceed to Secure Checkout
                    </a>
                @else
                    <div class="space-y-2">
                        <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 text-xs font-extrabold text-white bg-gradient-to-r from-orange-600 via-orange-500 to-amber-500 hover:from-orange-700 hover:to-amber-600 rounded-xl shadow-md shadow-orange-500/25 transition">
                            <i class="fas fa-right-to-bracket"></i> Login to Checkout
                        </a>
                        <a href="{{ route('register') }}" class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 text-xs font-bold text-slate-900 bg-amber-400 hover:bg-amber-300 rounded-xl transition">
                            Create New Member Account
                        </a>
                    </div>
                @endauth
            </div>

        </div>
    @else
        <div class="py-20 text-center bg-white rounded-3xl border border-slate-200/80 p-8 shadow-xs max-w-xl mx-auto space-y-4">
            <div class="w-16 h-16 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center text-2xl mx-auto">
                <i class="fas fa-basket-shopping"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-900">Your Cart is Currently Empty</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">
                Explore our catalog of direct selling starter packs, wellness, and daily essential products.
            </p>
            <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-6 py-3 text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-xl shadow-md shadow-orange-500/20 transition">
                <i class="fas fa-bag-shopping"></i> Browse Store Catalog
            </a>
        </div>
    @endif
</div>
@endsection
