@extends('admin.layouts.app')

@section('title', 'Orders Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-bag-shopping text-blue-600"></i> Customer Orders & Purchases
            </h1>
            <p class="text-sm text-slate-500 mt-1">Manage e-commerce order transactions, payments, deliveries, and calculation status.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ admin_route('mlm.reconciliation.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-check-double text-blue-500"></i> Direct Selling Reconciliation
            </a>
        </div>
    </div>

    <!-- Order Stats KPIs -->
    @php
        $pending = \App\Models\Order::where('status','pending')->count();
        $processing = \App\Models\Order::where('status','processing')->count();
        $delivered = \App\Models\Order::where('status','delivered')->count();
        $revenue = \App\Models\Order::where('pay_status','paid')->sum('total');
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($pending) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Processing</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                    <i class="fas fa-gear"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-blue-600 mt-2">{{ number_format($processing) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Delivered</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fas fa-truck-fast"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-emerald-600 mt-2">{{ number_format($delivered) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Paid Revenue</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                    <i class="fas fa-indian-rupee-sign"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">₹{{ number_format($revenue, 2) }}</div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
        <form class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3 text-xs" method="GET">
            <div class="sm:col-span-2">
                <label class="block font-semibold text-slate-600 mb-1">Search Orders</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                    <input type="text" name="search" class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" placeholder="Search order #, customer name, mobile..." value="{{ request('search') }}">
                </div>
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition">
                    <option value="">All Statuses</option>
                    @foreach(['pending','processing','packed','shipped','delivered','cancelled','failed','refunded'] as $s)
                        <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="flex-1 px-4 py-2 font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition shadow-xs">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ admin_route('orders.index') }}" class="px-4 py-2 font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Orders Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Order Transactions</h2>
            <span class="text-xs text-slate-500">{{ $orders->total() }} Total Orders</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Order #</th>
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4 text-center">Items</th>
                        <th class="py-3 px-4 text-right">Total Amount</th>
                        <th class="py-3 px-4">Order Status</th>
                        <th class="py-3 px-4">Payment</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4">
                                <a href="{{ admin_route('orders.show', $order) }}" class="font-bold text-blue-600 hover:underline">
                                    {{ $order->order_num }}
                                </a>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-800">{{ $order->user?->name ?? 'Guest' }}</div>
                                @if($order->user)
                                    <div class="text-[11px] text-slate-400">{{ $order->user->email }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                                    {{ $order->items_count ?? $order->items()->count() }} items
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-slate-900">₹{{ number_format($order->total, 2) }}</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $order->status === 'delivered' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($order->status === 'cancelled' || $order->status === 'failed' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-blue-50 text-blue-700 border border-blue-200') }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $order->pay_status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                    {{ ucfirst($order->pay_status) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">{{ $order->created_at->format('M d, Y h:i A') }}</td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ admin_route('orders.show', $order) }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition">
                                    <i class="fas fa-eye text-[11px]"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <i class="fas fa-cart-shopping text-3xl mb-2 text-slate-300 block"></i>
                                No orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
