@extends('admin.layouts.app')

@section('title', 'Direct Selling Reconciliation')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-check-double text-blue-600"></i> Direct Selling Order Reconciliation
            </h1>
            <p class="text-sm text-slate-500 mt-1">Reconcile purchase order transactions, payment status, reversals, and up to 100% cashback eligibility.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('mlm.calculations.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-calculator text-blue-500"></i> Calculation Engine
            </a>
            <a href="{{ admin_route('cashback.reconciliation') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-hand-holding-dollar text-emerald-500"></i> Cashback Reconciliation
            </a>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
            <div class="lg:col-span-2">
                <label class="block font-semibold text-slate-600 mb-1">Search Keywords</label>
                <input name="search" value="{{ request('search') }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" placeholder="Search order #, customer name, member code">
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Processing Status</label>
                <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Date Range</label>
                <div class="flex gap-1">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-1/2 px-2 py-2 bg-slate-50 border border-slate-200 rounded-lg text-[11px]">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-1/2 px-2 py-2 bg-slate-50 border border-slate-200 rounded-lg text-[11px]">
                </div>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-3 py-2 font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">Filter</button>
                <a href="{{ admin_route('mlm.reconciliation.index') }}" class="px-3 py-2 font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Orders Reconciliation Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Reconciliation Audit Records</h2>
            <span class="text-xs text-slate-500">{{ $orders->total() }} Total Orders</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Order #</th>
                        <th class="py-3 px-4">Customer / Member</th>
                        <th class="py-3 px-4">Order / Payment</th>
                        <th class="py-3 px-4 text-right">Eligible Amount</th>
                        <th class="py-3 px-4">Calculation Status</th>
                        <th class="py-3 px-4">Calculation Run</th>
                        <th class="py-3 px-4">Up to 100% Cashback</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($orders as $order)
                        @php($run = $order->mlmCalculationRuns->sortByDesc('id')->first())
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4">
                                <a href="{{ admin_route('orders.show', $order) }}" class="font-bold text-blue-600 hover:underline">
                                    {{ $order->order_num }}
                                </a>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ $order->created_at?->format('M d, Y') }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-800">{{ $order->user?->name ?? 'Guest' }}</div>
                                <div class="text-[11px] font-mono text-slate-500">{{ $order->user?->mlmMember?->customer_id ?? 'Not registered' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-800">{{ ucfirst($order->status) }}</div>
                                <div class="text-[11px] text-slate-400">Pay: {{ ucfirst($order->pay_status) }}</div>
                            </td>
                            <td class="py-3 px-4 text-right font-semibold text-slate-900">
                                {{ $order->mlm_eligible_amount ? '₹' . number_format((float)$order->mlm_eligible_amount, 2) : '—' }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ str_replace('_', ' ', $order->mlm_processing_status ?? 'not_started') }}
                                </span>
                                @if($order->mlm_error_message)
                                    <div class="text-[10px] text-rose-600 mt-1">{{ $order->mlm_error_message }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($run)
                                    <a href="{{ admin_route('mlm.calculations.index') }}" class="font-mono text-blue-600 font-semibold hover:underline">#{{ $run->id }}</a>
                                    <div class="text-[11px] text-slate-400">{{ $run->ruleVersion?->version }} &bull; {{ $run->status }}</div>
                                @else
                                    <span class="text-slate-400">&mdash;</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ str_replace('_', ' ', $order->cashbackEligibility?->status ?? 'not_created') }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if($order->mlm_processing_status === \App\Models\Order::MLM_FAILED)
                                    <form method="POST" action="{{ admin_route('mlm.reconciliation.retry', $order) }}" class="inline">
                                        @csrf
                                        <button class="px-2.5 py-1 text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-md transition">Retry</button>
                                    </form>
                                @else
                                    <span class="text-slate-400">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                No orders found for reconciliation.
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
