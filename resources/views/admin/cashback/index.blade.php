@extends('admin.layouts.app')

@section('title', 'Up to 100% Cashback Management')

@php
    $label = fn (string $value) => ucwords(str_replace('_', ' ', $value));
    $money = fn ($val) => '₹' . number_format((float) ($val ?? 0), 2);
@endphp

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-hand-holding-dollar text-emerald-600"></i> Up to 100% Conditional Cashback Program
            </h1>
            <p class="text-sm text-slate-500 mt-1">Cashback recovery strictly conditional on declared company profit pool allocations.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('cashback.reconciliation') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-clipboard-check text-blue-500"></i> Cashback Reconciliation
            </a>
            <a href="{{ admin_route('reports.cashback') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-piggy-bank text-indigo-500"></i> Pool Analytics
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Approved Profit Pool</span>
            <div class="text-2xl font-bold text-blue-600 mt-2">{{ $money($summary['approved']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Selected Allocation</span>
            <div class="text-2xl font-bold text-amber-600 mt-2">{{ $money($summary['selected']) }} <span class="text-xs font-normal text-slate-500">({{ $summary['selected_count'] }} records)</span></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Available Pool Amount</span>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ $money($summary['available']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Remaining Pool Capacity</span>
            <div class="text-2xl font-bold text-emerald-600 mt-2">{{ $money($summary['remaining']) }}</div>
        </div>
    </div>

    <!-- Declare Profit Pool Form -->
    @if(auth()->user()->hasPermission('cashback.pool.manage'))
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5">
        <h2 class="text-sm font-bold text-slate-900 mb-1 flex items-center gap-2">
            <i class="fas fa-coins text-amber-500"></i> Declare Approved Company Profit Pool
        </h2>
        <p class="text-xs text-slate-500 mb-4">Set aside verified company profits for conditional up to 100% cashback disbursement.</p>

        <form method="POST" action="{{ admin_route('cashback.pools.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            @csrf
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Pool Reference</label>
                <input name="pool_reference" value="{{ old('pool_reference') }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition font-mono" placeholder="e.g. POOL-2026-Q1" required>
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Approved Amount (₹)</label>
                <input name="approved_available_amount" inputmode="decimal" value="{{ old('approved_available_amount') }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition font-semibold" placeholder="50000.00" required>
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Admin Audit Description</label>
                <input name="admin_note" value="{{ old('admin_note') }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" placeholder="Optional audit description">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5">
                    <i class="fas fa-plus"></i> Declare Profit Pool
                </button>
            </div>
        </form>
    </div>
    @endif

    <!-- Company Profit Pools List -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Declared Company Profit Pools</h2>
            <span class="text-xs text-slate-500">Select a pool to allocate eligible cashback records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Pool Reference</th>
                        <th class="py-3 px-4 text-right">Approved Amount</th>
                        <th class="py-3 px-4 text-right">Allocated</th>
                        <th class="py-3 px-4 text-right">Remaining Capacity</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Declared Date</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($pools as $pool)
                        @php($poolRem = max(0, (float)$pool->approved_available_amount - (float)$pool->allocated_amount))
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $pool->pool_reference }}</td>
                            <td class="py-3 px-4 text-right font-semibold text-slate-900">{{ $money($pool->approved_available_amount) }}</td>
                            <td class="py-3 px-4 text-right text-amber-600 font-medium">{{ $money($pool->allocated_amount) }}</td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-600">{{ $money($poolRem) }}</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold {{ $pool->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $label($pool->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-500">{{ $pool->created_at?->format('M d, Y') }}</td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ admin_route('cashback.index', ['pool_id' => $pool->id] + request()->except('pool_id')) }}" class="px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition inline-flex items-center gap-1">
                                    <i class="fas fa-hand-pointer text-[10px]"></i> Use Pool
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                No company profit pools declared. Eligible records remain awaiting company profit.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cashback Records Filter & Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Member Cashback Eligibility Records</h2>
            <span class="text-xs text-slate-500">{{ $cashbacks->total() }} Total Records</span>
        </div>

        <div class="p-5 border-b border-slate-100 bg-slate-50/50">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Member</label>
                    <select name="member_id" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg focus:border-blue-500 outline-none transition">
                        <option value="">All Members</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" @selected((string) request('member_id') === (string) $member->id)>{{ $member->customer_id }} &ndash; {{ $member->user?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Order #</label>
                    <input name="order_num" value="{{ request('order_num') }}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg focus:border-blue-500 outline-none transition font-mono" placeholder="Order #">
                </div>
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg focus:border-blue-500 outline-none transition">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Date Range</label>
                    <div class="flex gap-1">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-1/2 px-2 py-2 bg-white border border-slate-200 rounded-lg text-[11px]">
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-1/2 px-2 py-2 bg-white border border-slate-200 rounded-lg text-[11px]">
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 px-3 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">Filter</button>
                    <a href="{{ admin_route('cashback.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-200 hover:bg-slate-300 rounded-lg transition">Clear</a>
                </div>
            </form>
        </div>

        @if($selectedPool && auth()->user()->hasPermission('cashback.select'))
        <form method="POST" action="{{ admin_route('cashback.select') }}" id="cashbackSelectionForm">
            @csrf
            <input type="hidden" name="pool_id" value="{{ $selectedPool->id }}">
            <input type="hidden" name="select_all" id="selectAllValue" value="0">
            @foreach(['date_from','date_to','member_id','order_num','amount_min','amount_max','status'] as $filter)<input type="hidden" name="{{ $filter }}" value="{{ request($filter) }}">@endforeach
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">
                            @if($selectedPool && auth()->user()->hasPermission('cashback.select'))
                                <input type="checkbox" id="selectAll" class="rounded text-blue-600">
                            @endif
                        </th>
                        <th class="py-3 px-4">Order #</th>
                        <th class="py-3 px-4">Member</th>
                        <th class="py-3 px-4 text-right">Purchase Amount</th>
                        <th class="py-3 px-4 text-right">100% Max Cashback</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($cashbacks as $cashback)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4">
                                @if($selectedPool && auth()->user()->hasPermission('cashback.select') && $cashback->status === \App\Models\CashbackEligibility::STATUS_ELIGIBLE_AWAITING_COMPANY_PROFIT)
                                    <input type="checkbox" name="cashback_ids[]" value="{{ $cashback->id }}" class="cashback-row rounded text-blue-600">
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono font-semibold text-blue-600">
                                {{ $cashback->order?->order_num ?? ('#'.$cashback->order_id) }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-900">{{ $cashback->member?->user?->name }}</div>
                                <div class="text-[11px] font-mono text-slate-500">{{ $cashback->member?->customer_id }}</div>
                            </td>
                            <td class="py-3 px-4 text-right font-semibold text-slate-900">{{ $money($cashback->final_eligible_amount) }}</td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-600 text-sm">{{ $money($cashback->maximum_cashback_amount) }}</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold {{ $cashback->status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                    {{ $label($cashback->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                No cashback records match the current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($selectedPool && auth()->user()->hasPermission('cashback.select'))
            <div class="p-4 bg-slate-50/60 border-t border-slate-100 flex items-center justify-between">
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition flex items-center gap-1.5">
                    <i class="fas fa-check-square"></i> Select Checked Records
                </button>
                <span class="text-xs text-slate-500">Selected records will be allocated to pool: <strong>{{ $selectedPool->pool_reference }}</strong></span>
            </div>
        </form>
        @endif

        @if($cashbacks->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end">
                {{ $cashbacks->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('selectAll')?.addEventListener('change', function () {
    document.getElementById('selectAllValue').value = this.checked ? '1' : '0';
    document.querySelectorAll('.cashback-row').forEach((checkbox) => checkbox.checked = this.checked);
});
</script>
@endpush
