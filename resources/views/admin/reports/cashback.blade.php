@extends('admin.layouts.app')

@section('title', '100% Cashback Reports')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">100% Cashback & Profit Pool Reports</h1>
            <p class="text-xs text-slate-500 mt-0.5">Customer cashback recovery, company profit allocations, distribution batches, and reconciliation</p>
        </div>
        <div>
            <a href="{{ admin_route('cashback.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                <i class="fas fa-hand-holding-dollar text-xs"></i> Manage Cashback
            </a>
        </div>
    </div>

    @include('admin.reports.partials.nav')
    @include('admin.reports.partials.filter')

    <!-- Cashback KPIs (4-card Grid) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Eligible Purchases</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-2">₹{{ number_format($summary['total_eligible'], 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">₹{{ number_format($summary['range_eligible'], 2) }} in selected range</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Maximum Cashback Cap</div>
            <div class="text-2xl font-extrabold text-blue-600 mt-2">₹{{ number_format($summary['max_cashback'], 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">100% principal recovery ceiling</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Cashback Disbursed (Paid)</div>
            <div class="text-2xl font-extrabold text-emerald-600 mt-2">₹{{ number_format($summary['paid_amount'], 2) }}</div>
            <div class="text-xs text-emerald-600 font-semibold mt-1"><i class="fas fa-circle-check text-[10px] mr-1"></i>Recovered via profit pools</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Awaiting Company Profit</div>
            <div class="text-2xl font-extrabold text-amber-600 mt-2">₹{{ number_format($summary['awaiting_profit_amount'], 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">Queued for future pool declarations</div>
        </div>
    </div>

    <!-- Profit Pools & Batches Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-piggy-bank text-blue-600"></i> Company Profit Pools
                </h2>
            </div>
            <div class="overflow-x-auto max-h-72">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200/70 text-[10px]">
                        <tr>
                            <th class="px-5 py-3">Pool Date</th>
                            <th class="px-5 py-3 text-right">Declared Profit</th>
                            <th class="px-5 py-3 text-right">Distributed</th>
                            <th class="px-5 py-3 text-right">Remaining</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($profitPools as $pool)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3 font-bold text-slate-900">{{ $pool->pool_date?->format('M d, Y') ?? '-' }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-slate-900">₹{{ number_format($pool->total_declared_profit, 2) }}</td>
                            <td class="px-5 py-3 text-right text-emerald-600 font-semibold">₹{{ number_format($pool->distributed_amount, 2) }}</td>
                            <td class="px-5 py-3 text-right text-slate-400">₹{{ number_format($pool->remaining_amount, 2) }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $pool->status === 'allocated' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                    {{ ucfirst($pool->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-400">No profit pools declared yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-money-check-dollar text-emerald-600"></i> Recent Cashback Payout Batches
                </h2>
            </div>
            <div class="overflow-x-auto max-h-72">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200/70 text-[10px]">
                        <tr>
                            <th class="px-5 py-3">Batch Ref</th>
                            <th class="px-5 py-3 text-center">Recipients</th>
                            <th class="px-5 py-3 text-right">Total Amount</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($payoutBatches as $batch)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3 font-mono font-bold text-blue-600">{{ $batch->batch_reference ?? 'Batch #'.$batch->id }}</td>
                            <td class="px-5 py-3 text-center font-bold text-slate-900">{{ $batch->items_count }}</td>
                            <td class="px-5 py-3 text-right font-bold text-emerald-600">₹{{ number_format($batch->total_amount, 2) }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $batch->status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($batch->status === 'approved' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                    {{ ucfirst($batch->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-400 text-[11px]">{{ $batch->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-400">No payout batches generated yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Cashback Eligibility Records Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-list text-blue-600"></i> Member 100% Cashback Records
            </h2>
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="date_preset" value="{{ $range['preset'] }}">
                <input type="hidden" name="date_from" value="{{ $range['date_from'] }}">
                <input type="hidden" name="date_to" value="{{ $range['date_to'] }}">
                
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="text-xs bg-slate-50 border border-slate-300 text-slate-800 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 focus:outline-none placeholder-slate-400" placeholder="Search Order / Member...">
                <select name="status" class="text-xs bg-slate-50 border border-slate-300 text-slate-800 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">All Status</option>
                    <option value="eligible_awaiting_profit" {{ ($filters['status'] ?? '') === 'eligible_awaiting_profit' ? 'selected' : '' }}>Awaiting Profit</option>
                    <option value="allocated_to_pool" {{ ($filters['status'] ?? '') === 'allocated_to_pool' ? 'selected' : '' }}>Allocated</option>
                    <option value="in_payout_batch" {{ ($filters['status'] ?? '') === 'in_payout_batch' ? 'selected' : '' }}>In Batch</option>
                    <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                    Filter
                </button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200/70 text-[10px]">
                    <tr>
                        <th class="px-5 py-3">Order Ref</th>
                        <th class="px-5 py-3">Member</th>
                        <th class="px-5 py-3 text-right">Original Eligible</th>
                        <th class="px-5 py-3 text-right">Max Cashback Cap</th>
                        <th class="px-5 py-3 text-right">Recovered</th>
                        <th class="px-5 py-3 text-right">Remaining</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Created Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($records as $rec)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-3 font-mono">
                            <span class="font-bold text-slate-900">{{ $rec->order_reference }}</span>
                            <div class="text-slate-400 text-[10px]">ID: #{{ $rec->id }}</div>
                        </td>
                        <td class="px-5 py-3">
                            <div class="font-bold text-slate-900">{{ $rec->member?->user?->name ?? 'Unknown Member' }}</div>
                            <div class="text-slate-400 text-[11px]">ID: {{ $rec->member?->customer_id }}</div>
                        </td>
                        <td class="px-5 py-3 text-right text-slate-800">₹{{ number_format($rec->original_eligible_amount, 2) }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-blue-600">₹{{ number_format($rec->max_cashback_amount, 2) }}</td>
                        <td class="px-5 py-3 text-right text-emerald-600 font-semibold">₹{{ number_format($rec->recovered_amount, 2) }}</td>
                        <td class="px-5 py-3 text-right font-bold text-amber-600">₹{{ number_format($rec->remaining_cashback_amount, 2) }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $rec->status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($rec->status === 'allocated_to_pool' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                {{ ucfirst(str_replace('_', ' ', $rec->status)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-400 text-[11px]">{{ $rec->created_at->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-slate-400">No cashback eligibility records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
            {{ $records->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
