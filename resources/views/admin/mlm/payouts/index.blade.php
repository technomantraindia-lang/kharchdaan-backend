@extends('admin.layouts.app')

@section('title', 'Weekly Direct Selling Settlements')

@php
    $money = static fn ($value): string => '₹' . number_format(round((float) ($value ?? 0)));
@endphp

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-wallet text-amber-500"></i> Weekly Direct Selling Settlements & Payouts
            </h1>
            <p class="text-sm text-slate-500 mt-1">Weekly Direct Selling income settlement cycles and member payment disbursements.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('mlm.calculations.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-calculator text-blue-500"></i> Calculation Engine
            </a>
            <a href="{{ admin_route('reports.payouts') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-chart-line text-emerald-500"></i> Payout Reports
            </a>
        </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-xl border border-slate-200/80 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Members</div>
            <div class="text-xl font-bold text-slate-900 mt-1.5">{{ number_format($summary['total_members']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Gross Income</div>
            <div class="text-xl font-bold text-blue-600 mt-1.5">{{ $money($summary['gross_income']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Reversals / Adj</div>
            <div class="text-xl font-bold text-amber-600 mt-1.5">{{ $money($summary['adjustment_amount']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Net Payable</div>
            <div class="text-xl font-bold text-emerald-600 mt-1.5">{{ $money($summary['net_payable']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Paid Amount</div>
            <div class="text-xl font-bold text-slate-900 mt-1.5">{{ $money($summary['paid_amount']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pending Amount</div>
            <div class="text-xl font-bold text-rose-600 mt-1.5">{{ $money($summary['pending_amount']) }}</div>
        </div>
    </div>

    <!-- Payout Generation & Adjustment Forms -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Generate Weekly Cycle -->
        <div class="lg:col-span-5 bg-white rounded-xl border border-slate-200 shadow-xs p-5">
            <h2 class="text-sm font-bold text-slate-900 mb-1 flex items-center gap-2">
                <i class="fas fa-calendar-plus text-blue-600"></i> Generate Weekly Settlement Cycle
            </h2>
            <p class="text-xs text-slate-500 mb-4">Creates a Monday&ndash;Sunday payout cycle aggregating calculated ledger records.</p>

            <form method="POST" action="{{ admin_route('mlm.payouts.cycles.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label for="periodStart" class="block text-xs font-semibold text-slate-600 mb-1">Target Date in Settlement Week</label>
                    <input type="date" name="period_start" id="periodStart" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" value="{{ old('period_start', now()->toDateString()) }}" required>
                </div>
                <div>
                    <label for="cycleNote" class="block text-xs font-semibold text-slate-600 mb-1">Admin Audit Note (Optional)</label>
                    <textarea name="admin_note" id="cycleNote" rows="2" maxlength="2000" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" placeholder="Optional notes for this weekly settlement cycle">{{ old('admin_note') }}</textarea>
                </div>
                <button type="submit" class="w-full px-4 py-2.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5">
                    <i class="fas fa-calendar-week"></i> Generate Settlement Cycle
                </button>
            </form>
        </div>

        <!-- Manual Adjustment Form -->
        <div class="lg:col-span-7 bg-white rounded-xl border border-slate-200 shadow-xs p-5">
            <h2 class="text-sm font-bold text-slate-900 mb-1 flex items-center gap-2">
                <i class="fas fa-sliders text-amber-600"></i> Record Manual Adjustment / Reversal
            </h2>
            <p class="text-xs text-slate-500 mb-4">Immutable ledger adjustments carrying forward into payable calculations.</p>

            <form method="POST" action="{{ admin_route('mlm.payouts.adjustments.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Member</label>
                        <select name="member_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" required>
                            <option value="">Select member</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}">{{ $member->customer_id }} — {{ $member->user?->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Level (0–19)</label>
                        <input name="level" type="number" min="0" max="19" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" value="0" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Rule Version</label>
                        <select name="rule_version_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" required>
                            @foreach($rules as $rule)
                                <option value="{{ $rule->id }}">{{ $rule->version }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Eligible Amount (₹)</label>
                        <input name="eligible_amount" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" placeholder="1000.00" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">PV</label>
                        <input name="pv" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition font-mono" placeholder="4.50" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Rate</label>
                        <input name="rate" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition font-mono" placeholder="0.20" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Calculated Amount (₹)</label>
                        <input name="calculated_amount" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition font-semibold" placeholder="0.90" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Reference</label>
                        <input name="transaction_reference" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition font-mono" placeholder="TXN-ADJ-..." required>
                    </div>
                </div>
                <div class="pt-2">
                    <input name="transaction_date" type="hidden" value="{{ now()->format('Y-m-d\TH:i') }}">
                    <button type="submit" class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg transition flex items-center gap-1.5">
                        <i class="fas fa-plus-circle text-blue-600"></i> Create Adjustment Entry
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Weekly Settlement Cycles Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Weekly Settlement Cycles</h2>
            <span class="text-xs text-slate-500">{{ $cycles->count() }} Total Cycles</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Cycle ID</th>
                        <th class="py-3 px-4">Period</th>
                        <th class="py-3 px-4 text-center">Members</th>
                        <th class="py-3 px-4 text-right">Net Payable</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Payment Details</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($cycles as $cycle)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $cycle->cycle_reference ?? 'CYCLE-'.$cycle->id }}</td>
                            <td class="py-3 px-4 text-slate-600 font-medium">
                                {{ $cycle->period_start?->format('M d') }} &ndash; {{ $cycle->period_end?->format('M d, Y') }}
                            </td>
                            <td class="py-3 px-4 text-center font-semibold text-slate-800">
                                {{ $cycle->total_members ?: $cycle->lines()->count() }}
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-600 text-sm">
                                {{ $money($cycle->net_payable ?? $cycle->total_amount) }}
                            </td>
                            <td class="py-3 px-4">
                                @if($cycle->status === 'paid')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                        <i class="fas fa-check text-[10px]"></i> Paid
                                    </span>
                                @elseif($cycle->status === 'approved')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">
                                        <i class="fas fa-thumbs-up text-[10px]"></i> Approved
                                    </span>
                                @elseif($cycle->status === 'processing')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                                        <i class="fas fa-spinner text-[10px] animate-spin"></i> Processing
                                    </span>
                                @elseif($cycle->status === 'on_hold')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/60">
                                        <i class="fas fa-pause text-[10px]"></i> On Hold
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200/60">
                                        <i class="fas fa-clock text-[10px]"></i> {{ ucwords(str_replace('_', ' ', $cycle->status)) }}
                                    </span>
                                @endif
                                @if($cycle->hold_reason)
                                    <div class="text-[10px] text-rose-600 mt-1 font-medium">{{ $cycle->hold_reason }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                @if($cycle->payment_reference)
                                    <div class="font-mono font-semibold text-slate-800">{{ $cycle->payment_reference }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $cycle->payment_date?->format('M d, Y H:i') }}</div>
                                @else
                                    <span class="text-slate-400 italic">Not paid</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($cycle->status === 'pending_approval')
                                        <form method="POST" action="{{ admin_route('mlm.payouts.cycles.approve', $cycle) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <button class="px-2.5 py-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 text-xs font-semibold transition">Approve</button>
                                        </form>
                                    @endif
                                    @if(in_array($cycle->status, ['approved', 'failed'], true))
                                        <form method="POST" action="{{ admin_route('mlm.payouts.cycles.process', $cycle) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <button class="px-2.5 py-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 text-xs font-semibold transition">Start Process</button>
                                        </form>
                                    @endif
                                    @if($cycle->status === 'on_hold')
                                        <form method="POST" action="{{ admin_route('mlm.payouts.cycles.releaseHold', $cycle) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <button class="px-2.5 py-1 rounded bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-300 text-xs font-semibold transition">Release</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fas fa-wallet text-3xl mb-2 text-slate-300 block"></i>
                                No weekly settlement cycles prepared yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payout Lines Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Weekly Member Payout Lines</h2>
            <span class="text-xs text-slate-500">{{ $payoutLines->total() }} Total Lines</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Payout Line</th>
                        <th class="py-3 px-4">Member</th>
                        <th class="py-3 px-4">Cycle Period</th>
                        <th class="py-3 px-4 text-right">Gross Income</th>
                        <th class="py-3 px-4 text-right">Adjustments</th>
                        <th class="py-3 px-4 text-right">Net Payable</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($payoutLines as $line)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">PAY-LINE-{{ $line->id }}</td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-800">{{ $line->member?->user?->name }}</div>
                                <div class="text-[11px] font-mono text-blue-600">{{ $line->member?->customer_id }}</div>
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                {{ $line->cycle?->period_start?->format('M d') }} &ndash; {{ $line->cycle?->period_end?->format('M d, Y') }}
                            </td>
                            <td class="py-3 px-4 text-right font-semibold text-slate-900">{{ $money($line->gross_income) }}</td>
                            <td class="py-3 px-4 text-right font-medium text-amber-600">{{ $money($line->adjustment_amount) }}</td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-600 text-sm">{{ $money($line->net_payable) }}</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ ucfirst($line->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fas fa-file-invoice text-3xl mb-2 text-slate-300 block"></i>
                                No payout lines found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payoutLines->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end">
                {{ $payoutLines->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
