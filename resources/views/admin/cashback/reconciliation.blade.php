@extends('admin.layouts.app')

@section('title', 'Up to 100% Cashback Reconciliation')

@section('content')
@php
    $money = fn ($val) => '₹' . number_format((float) ($val ?? 0), 2);
@endphp

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-clipboard-check text-blue-600"></i> Up to 100% Cashback Reconciliation
            </h1>
            <p class="text-sm text-slate-500 mt-1">Reconcile conditional cashback allocations, recoveries, profit pools, and settlement statuses.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('cashback.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-hand-holding-dollar text-emerald-500"></i> Cashback Program & Pools
            </a>
            <a href="{{ admin_route('reports.cashback') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-chart-pie text-indigo-500"></i> Pool Analytics
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Order Number</label>
                <input name="order" value="{{ request('order') }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" placeholder="e.g. ORD-2026-0001">
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Cashback Status</label>
                <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition">
                    <option value="">All Statuses</option>
                    @foreach(\App\Models\CashbackEligibility::STATUSES as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition shadow-xs">
                    <i class="fas fa-filter mr-1"></i> Filter Records
                </button>
                <a href="{{ admin_route('cashback.reconciliation') }}" class="px-4 py-2 font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Reconciliation Records Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Cashback Audit & Disbursement Ledger</h2>
            <span class="text-xs text-slate-500">{{ $records->total() }} Total Records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Order #</th>
                        <th class="py-3 px-4">Distributor</th>
                        <th class="py-3 px-4 text-right">Eligible Amount</th>
                        <th class="py-3 px-4 text-right">Max Cashback Cap</th>
                        <th class="py-3 px-4 text-right">Recovered</th>
                        <th class="py-3 px-4">Profit Pool / Batch</th>
                        <th class="py-3 px-4">Cashback Status</th>
                        <th class="py-3 px-4">Refund / Notes</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($records as $record)
                        @php
                            $recovered = $record->adjustments->where('adjustment_type', \App\Models\CashbackAdjustment::TYPE_REFUND_RECOVERY)->reduce(fn ($sum, $adjustment) => bcadd($sum, ltrim((string) $adjustment->amount, '-'), 18), '0');
                            $batch = $record->payoutBatchItems->first()?->batch;
                            $mismatch = $record->last_processing_error ?: ($record->refund_status === 'partially_refunded' ? 'Partial refund reconciled' : '—');
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4">
                                <a href="{{ admin_route('orders.show', $record->order) }}" class="font-bold text-blue-600 hover:underline">
                                    {{ $record->order?->order_num }}
                                </a>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ $record->created_at?->format('M d, Y') }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-800">{{ $record->member?->user?->name ?? 'Distributor' }}</div>
                                <div class="text-[11px] font-mono text-blue-600">{{ $record->member?->customer_id ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4 text-right font-semibold text-slate-900">
                                {{ $money($record->original_eligible_amount) }}
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-600">
                                {{ $money($record->original_cashback_amount ?? $record->maximum_cashback_amount) }}
                            </td>
                            <td class="py-3 px-4 text-right font-semibold text-amber-600">
                                {{ $money($recovered) }}
                            </td>
                            <td class="py-3 px-4">
                                @if($batch)
                                    <div class="font-mono text-slate-900">{{ $batch->batch_reference }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $batch->pool?->pool_reference }}</div>
                                @else
                                    <span class="text-slate-400">Unallocated</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold {{ match($record->status) { 'paid' => 'bg-emerald-50 text-emerald-700 border border-emerald-200', 'allocated_to_pool' => 'bg-blue-50 text-blue-700 border border-blue-200', default => 'bg-amber-50 text-amber-700 border border-amber-200' } }}">
                                    {{ ucwords(str_replace('_', ' ', $record->status)) }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="{{ $record->last_processing_error ? 'text-rose-600 font-semibold' : 'text-slate-500' }}">
                                    {{ $mismatch }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if($record->last_processing_error)
                                    <form method="POST" action="{{ admin_route('cashback.reconciliation.retry', $record) }}" class="inline">
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
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                No cashback reconciliation records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
