@extends('admin.layouts.app')

@section('title', 'Direct Selling Calculations')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-calculator text-blue-600"></i> Direct Selling Calculation Engine
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Automatic 20-level PV and commission engine. Income is created from eligible orders — no manual posting.
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('mlm.levels') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-layer-group text-blue-500"></i> Levels & Income Rules
            </a>
            <a href="{{ admin_route('mlm.payouts.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-wallet text-amber-500"></i> Weekly Settlements
            </a>
        </div>
    </div>

    <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 px-5 py-4 text-xs text-emerald-900">
        <div class="font-bold flex items-center gap-2 mb-1">
            <i class="fas fa-bolt text-emerald-600"></i> How income is calculated
        </div>
        <p class="text-emerald-800/90 leading-relaxed">
            When a customer order becomes eligible (paid / completed), the system automatically applies the High PV and Low PV formulas
            across up to 20 upline levels and writes the result to the audit log below. Admins do not need to enter or commit transactions on this page.
        </p>
    </div>

    <!-- Formula Reference Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gradient-to-br from-blue-50/70 to-indigo-50/50 rounded-xl border border-blue-200/70 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-600 text-white shadow-xs">
                    HIGH PV TIER (Levels 0 to 7)
                </span>
                <span class="text-xs font-semibold text-blue-700">8 Upline Levels</span>
            </div>
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-slate-500 font-medium">PV Formula:</span>
                    <div class="font-mono font-bold text-slate-900 bg-white/80 px-3 py-1.5 rounded-lg border border-blue-100 mt-1">
                        PV = (Eligible Amount &times; 13.5) / 3000
                    </div>
                </div>
                <div>
                    <span class="text-slate-500 font-medium">Income Rate:</span>
                    <div class="font-mono font-bold text-emerald-700 bg-white/80 px-3 py-1.5 rounded-lg border border-blue-100 mt-1">
                        Income = PV &times; 0.20 (20%)
                    </div>
                </div>
                <div class="pt-1 text-[11px] text-slate-600">
                    Example on ₹1,000 purchase: <strong class="text-slate-900">4.50 PV</strong> &bull; <strong class="text-emerald-600">₹0.90 Income</strong>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-50 to-amber-50/40 rounded-xl border border-amber-200/70 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-600 text-white shadow-xs">
                    LOW PV TIER (Levels 8 to 19)
                </span>
                <span class="text-xs font-semibold text-amber-700">12 Upline Levels</span>
            </div>
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-slate-500 font-medium">PV Formula:</span>
                    <div class="font-mono font-bold text-slate-900 bg-white/80 px-3 py-1.5 rounded-lg border border-amber-100 mt-1">
                        PV = (Eligible Amount &times; 0.75) / 3000
                    </div>
                </div>
                <div>
                    <span class="text-slate-500 font-medium">Income Rate:</span>
                    <div class="font-mono font-bold text-emerald-700 bg-white/80 px-3 py-1.5 rounded-lg border border-amber-100 mt-1">
                        Income = PV &times; 0.20 (20%)
                    </div>
                </div>
                <div class="pt-1 text-[11px] text-slate-600">
                    Example on ₹1,000 purchase: <strong class="text-slate-900">0.25 PV</strong> &bull; <strong class="text-emerald-600">₹0.05 Income</strong>
                </div>
            </div>
        </div>
    </div>

    @if(($activeRule ?? null))
        <div class="text-[11px] text-slate-500">
            Active rule:
            <span class="font-semibold text-slate-700">{{ $activeRule->version }} — {{ $activeRule->name }}</span>
            (High {{ $activeRule->high_pv_rate }} / Low {{ $activeRule->low_pv_rate }} ÷ {{ $activeRule->pv_divisor }} × {{ number_format((float) $activeRule->income_rate * 100, 0) }}%)
        </div>
    @endif

    <!-- Calculation Audit History Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-clock-rotate-left text-slate-400"></i> Calculation History & Audit Log
            </h2>
            <span class="text-xs text-slate-500">{{ $runs->total() }} Total Calculation Runs</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Transaction Ref</th>
                        <th class="py-3 px-4">Purchasing Member</th>
                        <th class="py-3 px-4">Rule Version</th>
                        <th class="py-3 px-4 text-right">Eligible Amount</th>
                        <th class="py-3 px-4 text-right">Total PV</th>
                        <th class="py-3 px-4 text-right">Total Income</th>
                        <th class="py-3 px-4">Processed Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($runs as $run)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $run->source_transaction_reference }}</td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-800">{{ $run->purchasingMember?->user?->name ?? 'Member' }}</div>
                                <div class="text-[11px] font-mono text-slate-500">{{ $run->purchasingMember?->customer_id }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $run->ruleVersion?->version ?? 'v1.0' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-semibold text-slate-900">₹{{ number_format((float) $run->eligible_amount, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-indigo-600">{{ number_format((float) $run->total_pv, 2) }} PV</td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-600">₹{{ number_format(round((float) $run->total_income)) }}</td>
                            <td class="py-3 px-4 text-slate-500 text-[11px]">
                                {{ $run->processed_at?->format('M d, Y H:i') ?? $run->created_at?->format('M d, Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fas fa-calculator text-3xl mb-2 text-slate-300 block"></i>
                                No automatic calculation history yet. Runs appear here after eligible orders are processed.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($runs->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end">
                {{ $runs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
