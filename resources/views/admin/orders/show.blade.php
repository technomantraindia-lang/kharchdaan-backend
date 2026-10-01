@extends('admin.layouts.app')

@section('title', 'Order #' . $order->order_num)

@section('content')
@php
    $isCancelled = in_array($order->status, ['cancelled', 'failed', 'refunded']);
    $stepIndex = $order->statusStepIndex();
    $steps = \App\Models\Order::statusSteps();
@endphp

<div class="space-y-6">
    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-bag-shopping text-blue-600"></i> Order #{{ $order->order_num }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">Placed on {{ $order->created_at->format('l, F d, Y \a\t h:i A') }}</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if($order->invoice)
                <a href="{{ admin_route('invoices.show', $order->invoice) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 shadow-xs transition">
                    <i class="fas fa-file-invoice text-blue-500"></i> Invoice
                </a>
            @endif
            @if($order->payment)
                <a href="{{ admin_route('payments.show', $order->payment) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 shadow-xs transition">
                    <i class="fas fa-credit-card text-emerald-500"></i> Payment
                </a>
            @endif
            <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-print text-slate-400"></i> Print
            </button>
            <a href="{{ admin_route('orders.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-arrow-left text-slate-400"></i> All Orders
            </a>
        </div>
    </div>

    <!-- Status Pipeline -->
    @if(!$isCancelled)
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs overflow-x-auto flex items-center gap-2">
            @foreach($steps as $i => $step)
                @if($i > 0)
                    <i class="fas fa-chevron-right text-slate-300 text-[10px] mx-1"></i>
                @endif
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap {{ $i < $stepIndex ? 'bg-blue-50 text-blue-700 border border-blue-200' : ($i === $stepIndex ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-50 text-slate-400') }}">
                    @if($i < $stepIndex)
                        <i class="fas fa-check-circle text-blue-600"></i>
                    @elseif($step === 'pending')
                        <i class="fas fa-clock"></i>
                    @elseif($step === 'processing')
                        <i class="fas fa-gear"></i>
                    @elseif($step === 'packed')
                        <i class="fas fa-box"></i>
                    @elseif($step === 'shipped')
                        <i class="fas fa-truck-fast"></i>
                    @else
                        <i class="fas fa-check"></i>
                    @endif
                    <span>{{ ucfirst($step) }}</span>
                </div>
            @endforeach
        </div>
    @else
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 font-semibold flex items-center gap-2">
            <i class="fas fa-ban"></i> Order is currently <span class="uppercase font-bold">{{ $order->status }}</span>.
        </div>
    @endif

    <!-- 4-Card Order Info Tiles -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs border-l-4 border-l-blue-600">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Customer</span>
            <div class="text-base font-bold text-slate-900 mt-1 truncate">{{ $order->user?->name ?? 'Guest Customer' }}</div>
            <div class="text-xs text-slate-500 mt-0.5 truncate">{{ $order->user?->email ?? '-' }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs border-l-4 border-l-emerald-600">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Payment Status</span>
            <div class="text-base font-bold text-emerald-600 mt-1">{{ ucfirst($order->pay_status) }}</div>
            <div class="text-xs text-slate-500 mt-0.5">{{ $order->payment ? strtoupper(str_replace('_',' ',$order->payment->method)) : 'Offline / Gateway' }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs border-l-4 border-l-indigo-600">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Shipping</span>
            <div class="text-base font-bold text-slate-900 mt-1">{{ $order->shippingMethod?->name ?? ($order->courier ?? 'Standard Delivery') }}</div>
            <div class="text-xs text-slate-500 mt-0.5">₹{{ number_format($order->ship_charge, 2) }} @if($order->tracking_num)&bull; {{ $order->tracking_num }}@endif</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs border-l-4 border-l-amber-500">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Order Grand Total</span>
            <div class="text-base font-bold text-slate-900 mt-1">₹{{ number_format($order->total, 2) }}</div>
            <div class="text-xs text-slate-500 mt-0.5">{{ $order->items->sum('qty') }} item(s) ordered</div>
        </div>
    </div>

    <!-- Main Content & Management Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Products & Financial Summary -->
        <div class="lg:col-span-8 space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-boxes-stacked text-blue-600"></i> Purchased Items ({{ $order->items->count() }})
                    </h2>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach($order->items as $item)
                        <div class="p-4 flex items-center gap-4 hover:bg-slate-50/50 transition">
                            <div class="w-12 h-12 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 flex-shrink-0">
                                @if($item->product?->image)
                                    <img src="{{ asset('storage/'.$item->product->image) }}" class="w-full h-full object-cover rounded-lg" alt="">
                                @else
                                    <i class="fas fa-box text-sm"></i>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-slate-900 truncate">{{ $item->product?->name ?? 'Catalog Product' }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5 font-mono">SKU: {{ $item->product?->sku ?? 'N/A' }} @if($item->gst_pct > 0)&bull; GST {{ $item->gst_pct }}%@endif</div>
                            </div>
                            <div class="text-xs text-slate-600 font-medium">
                                &times; {{ $item->qty }}
                            </div>
                            <div class="text-xs font-bold text-slate-900 text-right min-w-[80px]">
                                ₹{{ number_format($item->qty * $item->price, 2) }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Financial Breakdown -->
                <div class="bg-slate-50/80 p-5 border-t border-slate-100 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Items Subtotal</span>
                        <span class="font-semibold text-slate-900">₹{{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    @if($order->discount > 0)
                        <div class="flex justify-between text-emerald-600">
                            <span>Coupon Discount @if($order->coupon)({{ $order->coupon->code }})@endif</span>
                            <span class="font-semibold">-₹{{ number_format($order->discount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-slate-600">
                        <span>GST / Tax Amount</span>
                        <span class="font-semibold text-slate-900">₹{{ number_format($order->gst_amt, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Shipping / Courier Charges</span>
                        <span class="font-semibold text-slate-900">₹{{ number_format($order->ship_charge, 2) }}</span>
                    </div>
                    <div class="flex justify-between pt-3 border-t border-slate-200 text-sm font-bold text-slate-900">
                        <span>Grand Total</span>
                        <span class="text-blue-600">₹{{ number_format($order->total, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Billing & Shipping Addresses -->
            @if($order->bill_addr || $order->ship_addr)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if($order->bill_addr)
                        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5">
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <i class="fas fa-file-invoice text-blue-500"></i> Billing Address
                            </h3>
                            <div class="text-xs text-slate-600 leading-relaxed">{!! nl2br(e($order->bill_addr)) !!}</div>
                        </div>
                    @endif
                    @if($order->ship_addr)
                        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5">
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <i class="fas fa-location-dot text-indigo-500"></i> Shipping Address
                            </h3>
                            <div class="text-xs text-slate-600 leading-relaxed">{!! nl2br(e($order->ship_addr)) !!}</div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Order Status Audit Log -->
            @if($order->statusHistory->count())
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i class="fas fa-clock-rotate-left text-blue-600"></i> Order Status Log
                        </h3>
                    </div>
                    <div class="p-5 divide-y divide-slate-100 text-xs">
                        @foreach($order->statusHistory as $history)
                            <div class="py-2.5 first:pt-0 last:pb-0 flex items-start gap-3">
                                <div class="w-2 h-2 rounded-full bg-blue-600 mt-1.5 flex-shrink-0"></div>
                                <div class="flex-1">
                                    <div class="font-bold text-slate-900">{{ ucfirst($history->status) }}</div>
                                    @if($history->note)
                                        <div class="text-slate-600 mt-0.5">{{ $history->note }}</div>
                                    @endif
                                </div>
                                <div class="text-[11px] font-mono text-slate-400">{{ $history->created_at->format('M d, Y h:i A') }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Side: Manage Order & Customer Info -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Manage Order Status Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-blue-50/50">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-sliders text-blue-600"></i> Manage Order State
                    </h3>
                </div>

                <form action="{{ admin_route('orders.update', $order) }}" method="POST" class="p-5 space-y-3.5 text-xs">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Order Status</label>
                        <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition">
                            @foreach(['pending','processing','packed','shipped','delivered','cancelled','failed','refunded'] as $s)
                                <option value="{{ $s }}" @selected($order->status===$s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Payment Status</label>
                        <select name="pay_status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition">
                            @foreach(['pending','paid','failed','refunded'] as $s)
                                <option value="{{ $s }}" @selected($order->pay_status===$s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tracking Number</label>
                        <input type="text" name="tracking_num" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition font-mono" value="{{ old('tracking_num', $order->tracking_num) }}" placeholder="e.g. TRK12345678">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Courier Partner</label>
                        <input type="text" name="courier" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" value="{{ old('courier', $order->courier) }}" placeholder="e.g. Blue Dart, Delhivery">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Status Note</label>
                        <input type="text" name="status_note" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" placeholder="Audit note for status change">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Admin Audit Note</label>
                        <textarea name="admin_note" rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition">{{ old('admin_note', $order->admin_note) }}</textarea>
                    </div>

                    <button type="submit" class="w-full px-4 py-2.5 font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5">
                        <i class="fas fa-save"></i> Save Order Changes
                    </button>
                </form>
            </div>

            <!-- Customer Profile Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-sm font-bold text-slate-900">Customer Details</h3>
                </div>
                <div class="p-5 text-xs space-y-3">
                    @if($order->user)
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-sm shadow-xs">
                                {{ strtoupper(substr($order->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-bold text-slate-900">{{ $order->user->name }}</div>
                                <div class="text-slate-400 text-[11px]">Member since {{ $order->user->created_at->format('M Y') }}</div>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-slate-100 space-y-2">
                            <div class="flex items-center gap-2 text-slate-600">
                                <i class="fas fa-envelope text-slate-400 w-4"></i>
                                <span>{{ $order->user->email }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-600">
                                <i class="fas fa-phone text-slate-400 w-4"></i>
                                <span>{{ $order->user->phone ?? 'Not provided' }}</span>
                            </div>
                        </div>
                        <a href="{{ admin_route('customers.show', $order->user) }}" class="block w-full text-center px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold transition mt-3">
                            View Customer Profile
                        </a>
                    @else
                        <div class="text-slate-400 italic">Guest Checkout Order</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
