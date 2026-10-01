@extends('admin.layouts.app')

@section('title', 'Weekly Payout Reports')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Weekly Payout Reports & Cycles</h1>
            <p class="text-xs text-slate-500 mt-0.5">Financial reconciliation, weekly payout batches, distributor settlements, and payout lines</p>
        </div>
        <div>
            <a href="{{ admin_route('mlm.payouts.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                <i class="fas fa-wallet text-xs"></i> Manage Weekly Cycles
            </a>
        </div>
    </div>

    @include('admin.reports.partials.nav')
    @include('admin.reports.partials.filter')

    <!-- Payout KPIs (4-card Grid) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Gross Income Payable</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-2">₹{{ number_format(round($summary['gross_income'])) }}</div>
            <div class="text-xs text-slate-400 mt-1">Total earnings across weekly cycles</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Net Payable Amount</div>
            <div class="text-2xl font-extrabold text-blue-600 mt-2">₹{{ number_format(round($summary['net_payable'])) }}</div>
            <div class="text-xs text-slate-400 mt-1">After ₹{{ number_format(round($summary['adjustments_reversals'])) }} adjustments</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Amount Paid</div>
            <div class="text-2xl font-extrabold text-emerald-600 mt-2">₹{{ number_format(round($summary['paid_amount'])) }}</div>
            <div class="text-xs text-emerald-600 font-semibold mt-1"><i class="fas fa-circle-check text-[10px] mr-1"></i>Successfully transferred to bank</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pending / Hold Payouts</div>
            <div class="text-2xl font-extrabold text-amber-600 mt-2">₹{{ number_format(round($summary['pending_amount'] + $summary['on_hold_amount'])) }}</div>
            <div class="text-xs text-slate-400 mt-1">Pending approval or processing</div>
        </div>
    </div>

    <!-- Weekly Cycles Summary Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-calendar-week text-blue-600"></i> Weekly Payout Cycle Summaries
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200/70 text-[10px]">
                    <tr>
                        <th class="px-5 py-3">Cycle Reference</th>
                        <th class="px-5 py-3">Cycle Period</th>
                        <th class="px-5 py-3 text-center">Member Count</th>
                        <th class="px-5 py-3 text-right">Gross Income</th>
                        <th class="px-5 py-3 text-right">Adjustments</th>
                        <th class="px-5 py-3 text-right">Net Payable</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Created By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($cycles as $c)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-3 font-mono font-bold text-blue-600">{{ $c->cycle_reference }}</td>
                        <td class="px-5 py-3 text-slate-600">
                            {{ $c->period_start?->format('M d, Y') }} &ndash; {{ $c->period_end?->format('M d, Y') }}
                        </td>
                        <td class="px-5 py-3 text-center font-bold text-slate-900">{{ $c->lines_count }}</td>
                        <td class="px-5 py-3 text-right text-slate-800">₹{{ number_format(round($c->gross_income)) }}</td>
                        <td class="px-5 py-3 text-right text-slate-400">₹{{ number_format(round($c->adjustments_reversals)) }}</td>
                        <td class="px-5 py-3 text-right font-bold text-slate-900">₹{{ number_format(round($c->net_payable)) }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $c->status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($c->status === 'approved' ? 'bg-blue-50 text-blue-700 border border-blue-200' : ($c->status === 'on_hold' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200')) }}">
                                {{ ucfirst(str_replace('_', ' ', $c->status)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-400 text-[11px]">{{ $c->createdBy?->name ?? 'System' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-slate-400">No weekly payout cycles found for the selected period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($cycles->hasPages())
        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
            {{ $cycles->links() }}
        </div>
        @endif
    </div>

    <!-- Payout Lines Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-file-invoice-dollar text-blue-600"></i> Individual Member Payout Lines
            </h2>
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="date_preset" value="{{ $range['preset'] }}">
                <input type="hidden" name="date_from" value="{{ $range['date_from'] }}">
                <input type="hidden" name="date_to" value="{{ $range['date_to'] }}">
                
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="text-xs bg-slate-50 border border-slate-300 text-slate-800 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 focus:outline-none placeholder-slate-400" placeholder="Search Member...">
                <select name="status" class="text-xs bg-slate-50 border border-slate-300 text-slate-800 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">All Status</option>
                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="on_hold" {{ ($filters['status'] ?? '') === 'on_hold' ? 'selected' : '' }}>On Hold</option>
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
                        <th class="px-5 py-3">Member Code</th>
                        <th class="px-5 py-3">Member Name</th>
                        <th class="px-5 py-3">Cycle Reference</th>
                        <th class="px-5 py-3 text-right">Gross Income</th>
                        <th class="px-5 py-3 text-right">Adjustment</th>
                        <th class="px-5 py-3 text-right">Net Payable</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Bank Snapshot</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($lines as $line)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-3 font-mono font-bold text-blue-600">{{ $line->member?->customer_id ?? 'ID#'.$line->member_id }}</td>
                        <td class="px-5 py-3">
                            <div class="font-bold text-slate-900">{{ $line->member?->user?->name ?? 'Unknown Member' }}</div>
                            <div class="text-slate-400 text-[11px]">{{ $line->member?->user?->email }}</div>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px] border border-slate-200">
                                {{ $line->cycle?->cycle_reference ?? '-' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right text-slate-800">₹{{ number_format(round($line->gross_income)) }}</td>
                        <td class="px-5 py-3 text-right text-slate-400">₹{{ number_format(round($line->adjustment_amount)) }}</td>
                        <td class="px-5 py-3 text-right font-bold text-emerald-600">₹{{ number_format(round($line->net_payable)) }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $line->status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($line->status === 'on_hold' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                {{ ucfirst(str_replace('_', ' ', $line->status)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500 text-[11px]">
                            <div class="font-medium">{{ $line->bank_name_snapshot ?? $line->member?->bank_name ?? 'N/A' }}</div>
                            <div class="font-mono text-slate-400 text-[10px]">{{ $line->member?->masked_bank_account ?? '****' }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-slate-400">No payout lines recorded for this selection.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($lines->hasPages())
        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
            {{ $lines->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
