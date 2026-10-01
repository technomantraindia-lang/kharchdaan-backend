@extends('admin.layouts.app')

@section('title', 'Direct Selling Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-white border border-slate-200/90 p-6 shadow-xs">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-orange-600 via-amber-500 to-orange-400"></div>
        <div class="relative z-10 flex flex-col xl:flex-row xl:items-center justify-between gap-5">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-orange-50 text-orange-700 border border-orange-200 shadow-xs">
                        <i class="fas fa-layer-group text-[10px] text-orange-600"></i> KharchDaan Direct Selling Engine
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> v2.0 Active
                    </span>
                </div>
                <h1 class="text-2xl lg:text-3xl font-extrabold tracking-tight text-slate-900">Direct Selling & 100% Cashback Platform</h1>
                <p class="text-xs text-slate-500 mt-1 max-w-2xl leading-relaxed">
                    <span class="font-bold text-orange-600">"तेरा तुझको अर्पण"</span> &mdash; 1:3 physical placement matrix, 20-level PV distribution engine, weekly settlements, and company profit cashback.
                </p>
            </div>
            <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2.5 flex-shrink-0">
                <a href="{{ admin_route('mlm.sponsor-network') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-2.5 text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl transition hover:text-orange-600 hover:border-orange-300 shadow-xs">
                    <i class="fas fa-user-friends text-orange-500 text-xs"></i>
                    <span>Sponsor Network</span>
                </a>
                <a href="{{ admin_route('mlm.tree.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-white bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 rounded-xl shadow-xs transition hover:shadow group">
                    <i class="fas fa-sitemap text-xs"></i>
                    <span>Placement Tree</span>
                </a>
                <a href="{{ admin_route('mlm.calculations.index') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-2.5 text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl transition hover:text-emerald-600 hover:border-emerald-300 shadow-xs">
                    <i class="fas fa-calculator text-emerald-500 text-xs"></i>
                    <span>Calculations</span>
                </a>
                <a href="{{ admin_route('mlm.payouts.index') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-2.5 text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl transition hover:text-amber-600 hover:border-amber-300 shadow-xs">
                    <i class="fas fa-wallet text-amber-500 text-xs"></i>
                    <span>Weekly Payouts</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Section 1: Main Platform KPI Grid (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Members -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs hover:shadow-sm transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Members</span>
                <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ number_format($stats['total_members']) }}</div>
                <div class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                    <span class="text-emerald-600 font-semibold"><i class="fas fa-circle-check text-[10px]"></i> {{ number_format($stats['active_members']) }} Active</span>
                    <span>&bull;</span>
                    <span class="text-amber-600 font-semibold">{{ number_format($stats['pending_members']) }} Pending</span>
                </div>
            </div>
        </div>

        <!-- KYC Approved -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs hover:shadow-sm transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">KYC Verification</span>
                <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-id-card"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ number_format($stats['kyc_approved']) }} <span class="text-xs font-normal text-slate-500">Approved</span></div>
                <div class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                    <span class="text-amber-600 font-semibold">{{ $stats['kyc_pending'] + $stats['kyc_under_review'] }} Under Review</span>
                    <span>&bull;</span>
                    <span class="text-rose-600 font-semibold">{{ $stats['kyc_rejected'] }} Rejected</span>
                </div>
            </div>
        </div>

        <!-- Total Network PV -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs hover:shadow-sm transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Network PV</span>
                <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-chart-column"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold font-mono text-indigo-600 tracking-tight">{{ number_format($stats['total_network_pv'], 4) }} <span class="text-xs font-normal text-slate-500 font-sans">PV</span></div>
                <div class="text-xs text-slate-500 mt-1">Levels 0&ndash;19 cumulative point volume</div>
            </div>
        </div>

        <!-- Direct Selling Income -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs hover:shadow-sm transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Direct Selling Income</span>
                <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-emerald-600 tracking-tight">₹{{ number_format(round($stats['total_direct_selling_income'])) }}</div>
                <div class="text-xs text-slate-500 mt-1">₹{{ number_format(round($stats['paid_payout'])) }} settled to distributors</div>
            </div>
        </div>
    </div>

    <!-- Section 2: Financial Settlements & Cashback Row (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Pending Payouts -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pending Payouts</span>
            <div class="text-xl font-bold text-amber-600 mt-2">₹{{ number_format(round($stats['pending_payout'])) }}</div>
            <div class="text-xs text-slate-400 mt-0.5">Awaiting calculation / approval</div>
        </div>

        <!-- Settled Payouts -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Paid / Settled Payouts</span>
            <div class="text-xl font-bold text-slate-900 mt-2">₹{{ number_format(round($stats['paid_payout'])) }}</div>
            <div class="text-xs text-slate-400 mt-0.5">Disbursed with bank audit proof</div>
        </div>

        <!-- 100% Cashback Eligible -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">100% Cashback Eligible</span>
            <div class="text-xl font-bold text-orange-600 mt-2">₹{{ number_format(round($stats['cashback_eligible_amount'])) }}</div>
            <div class="text-xs text-slate-400 mt-0.5">₹{{ number_format(round($stats['cashback_paid_amount'])) }} disbursed</div>
        </div>

        <!-- Current Weekly Cycle -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Current Weekly Cycle</span>
            <div class="text-base font-bold text-slate-900 mt-2 truncate" title="{{ $stats['current_weekly_cycle'] }}">{{ $stats['current_weekly_cycle'] }}</div>
            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700 mt-1">
                {{ str_replace('_', ' ', $stats['current_cycle_status']) }}
            </span>
        </div>
    </div>

    <!-- Section 3: Pending Administrative Actions (4 Cards Grid) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-triangle-exclamation text-amber-500"></i> Pending Administrative Actions
            </h2>
            <span class="text-xs text-slate-400 font-medium">Compliance & Settlement Queue</span>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-xl bg-amber-50/60 border border-amber-200/70 flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-amber-900">KYC Verification Pending</div>
                    <div class="text-2xl font-extrabold text-amber-700 mt-1">{{ $pendingActions['kyc_pending_count'] }}</div>
                </div>
                <a href="{{ admin_route('mlm.members.index', ['kyc_status' => 'pending']) }}" class="px-3 py-1.5 rounded-lg bg-amber-500 text-white text-xs font-bold hover:bg-amber-600 transition shadow-xs">
                    Review
                </a>
            </div>

            <div class="p-4 rounded-xl bg-blue-50/60 border border-blue-200/70 flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-blue-900">Payout Approval Pending</div>
                    <div class="text-2xl font-extrabold text-blue-700 mt-1">{{ $pendingActions['payout_approval_pending'] }}</div>
                </div>
                <a href="{{ admin_route('mlm.payouts.index') }}" class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition shadow-xs">
                    Approve
                </a>
            </div>

            <div class="p-4 rounded-xl bg-indigo-50/60 border border-indigo-200/70 flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-indigo-900">Cashback Awaiting Profit</div>
                    <div class="text-2xl font-extrabold text-indigo-700 mt-1">{{ $pendingActions['cashback_awaiting_profit_count'] }}</div>
                </div>
                <a href="{{ admin_route('cashback.index') }}" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 transition shadow-xs">
                    Declare
                </a>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-slate-800">Reconciliation Pending</div>
                    <div class="text-2xl font-extrabold text-slate-700 mt-1">{{ $pendingActions['reconciliation_issues_count'] }}</div>
                </div>
                <a href="{{ admin_route('mlm.reconciliation.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-700 text-white text-xs font-bold hover:bg-slate-800 transition shadow-xs">
                    Check
                </a>
            </div>
        </div>
    </div>

    <!-- Section 4: Member Growth Chart & Network Level Distribution (2 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- A. Member Growth Chart -->
        <div class="lg:col-span-6 bg-white rounded-xl border border-slate-200 shadow-xs p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-user-plus text-blue-600"></i> Member Registrations Timeline
                </h2>
                <span class="text-xs text-slate-400">Last 6 Months</span>
            </div>
            <div class="mt-4 flex-1">
                <canvas id="memberGrowthChart" class="w-full" style="max-height: 240px;"></canvas>
            </div>
        </div>

        <!-- B. Network Level Distribution (Levels 0–19) -->
        <div class="lg:col-span-6 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-layer-group text-indigo-600"></i> Network Level Distribution (Levels 0–19)
                </h2>
                <a href="{{ admin_route('mlm.levels') }}" class="text-xs font-semibold text-blue-600 hover:underline">
                    View Matrix Rules
                </a>
            </div>
            <div class="overflow-x-auto max-h-64 overflow-y-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-slate-50/95 backdrop-blur-xs border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-4">Level</th>
                            <th class="py-2.5 px-4">Tier</th>
                            <th class="py-2.5 px-4 text-center">Members</th>
                            <th class="py-2.5 px-4 text-center">Active</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        @foreach($levelsDistribution as $lvl)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-2 px-4">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $lvl['level'] <= 7 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                        Level {{ $lvl['level'] }}
                                    </span>
                                </td>
                                <td class="py-2 px-4 text-slate-500">{{ $lvl['tier'] }}</td>
                                <td class="py-2 px-4 text-center font-bold text-slate-900">{{ $lvl['count'] }}</td>
                                <td class="py-2 px-4 text-center font-semibold text-emerald-600">{{ $lvl['active'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 5: Direct Selling Commission by Level Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-coins text-emerald-600"></i> Direct Selling Commission by Level (Levels 0–19)
            </h2>
            <a href="{{ admin_route('reports.income') }}" class="text-xs font-semibold text-emerald-600 hover:underline">
                View Full Income Report &rarr;
            </a>
        </div>
        <div class="overflow-x-auto max-h-72 overflow-y-auto">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0 bg-slate-50/95 backdrop-blur-xs border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Level</th>
                        <th class="py-3 px-4">Tier Description</th>
                        <th class="py-3 px-4 text-center">Distinct Earners</th>
                        <th class="py-3 px-4 text-right">Total PV Generated</th>
                        <th class="py-3 px-4 text-right">Total Income (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @foreach($incomeByLevel as $inc)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-2.5 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold uppercase {{ $inc['level'] <= 7 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                    Level {{ $inc['level'] }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-slate-500">{{ $inc['tier'] }}</td>
                            <td class="py-2.5 px-4 text-center font-semibold text-slate-800">{{ $inc['member_count'] }}</td>
                            <td class="py-2.5 px-4 text-right font-mono font-bold text-indigo-600">{{ number_format($inc['pv'], 4) }} PV</td>
                            <td class="py-2.5 px-4 text-right font-bold text-emerald-600 text-sm">₹{{ number_format($inc['income'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 6: Recent Activity (2 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Recent Calculations -->
        <div class="lg:col-span-6 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-calculator text-blue-600"></i> Recent Calculations
                </h2>
                <a href="{{ admin_route('mlm.calculations.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Transaction Ref</th>
                            <th class="py-3 px-4">Member</th>
                            <th class="py-3 px-4 text-right">Eligible</th>
                            <th class="py-3 px-4 text-right">PV</th>
                            <th class="py-3 px-4 text-right">Income</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        @forelse($recentCalculations as $calc)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-mono font-semibold text-slate-900">
                                    {{ $calc->source_transaction_reference }}
                                    <div class="text-[10px] text-slate-400 font-sans">{{ $calc->processed_at?->format('M d, H:i') }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-800">{{ $calc->purchasingMember?->user?->name ?? 'Member' }}</div>
                                    <div class="text-[11px] font-mono text-slate-500">{{ $calc->purchasingMember?->customer_id }}</div>
                                </td>
                                <td class="py-3 px-4 text-right font-medium text-slate-900">₹{{ number_format($calc->eligible_amount, 2) }}</td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-indigo-600">{{ number_format($calc->total_pv, 4) }}</td>
                                <td class="py-3 px-4 text-right font-bold text-emerald-600">₹{{ number_format(round($calc->total_income)) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-slate-400">No recent calculations recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Weekly Cycles -->
        <div class="lg:col-span-6 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-wallet text-amber-500"></i> Recent Settlement Cycles
                </h2>
                <a href="{{ admin_route('mlm.payouts.index') }}" class="text-xs font-semibold text-amber-600 hover:underline">Manage Payouts</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Cycle Ref</th>
                            <th class="py-3 px-4">Period</th>
                            <th class="py-3 px-4 text-center">Members</th>
                            <th class="py-3 px-4 text-right">Net Payable</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        @forelse($recentPayoutCycles as $cycle)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-mono font-semibold text-slate-900">{{ $cycle->cycle_reference }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $cycle->period_start?->format('M d') }} &ndash; {{ $cycle->period_end?->format('M d, Y') }}</td>
                                <td class="py-3 px-4 text-center font-bold text-slate-900">{{ $cycle->lines_count }}</td>
                                <td class="py-3 px-4 text-right font-bold text-emerald-600">₹{{ number_format($cycle->net_payable, 2) }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ str_replace('_', ' ', $cycle->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-slate-400">No payout cycles generated yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const growthData = @json($memberGrowth ?? []);
if (document.getElementById('memberGrowthChart') && growthData.length > 0) {
    new Chart(document.getElementById('memberGrowthChart'), {
        type: 'line',
        data: {
            labels: growthData.map(d => d.label),
            datasets: [{
                label: 'New Registrations',
                data: growthData.map(d => d.count),
                borderColor: '#ea580c',
                backgroundColor: 'rgba(234, 88, 12, 0.1)',
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointBackgroundColor: '#ea580c',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
}
</script>
@endpush
@endsection
