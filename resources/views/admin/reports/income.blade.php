@extends('admin.layouts.app')

@section('title', 'Direct Selling Income Reports')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Direct Selling Income & PV Reports</h1>
            <p class="text-xs text-slate-500 mt-0.5">Level-wise commissions, PV calculations, distributor earnings, and ledger audit</p>
        </div>
        <div>
            <a href="{{ admin_route('reports.export', ['module' => 'financial', 'date_preset' => $range['preset'], 'date_from' => $range['date_from'], 'date_to' => $range['date_to']]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                <i class="fas fa-file-export text-xs"></i> Export Financial CSV
            </a>
        </div>
    </div>

    @include('admin.reports.partials.nav')
    @include('admin.reports.partials.filter')

    <!-- Income KPIs (4-card Grid) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Network PV Generated</div>
            <div class="text-2xl font-extrabold font-mono text-blue-600 mt-2">{{ number_format($summary['total_pv'], 4) }} <span class="text-xs font-sans text-slate-400 font-normal">PV</span></div>
            <div class="text-xs text-slate-400 mt-1">Points volume across all 20 levels</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Gross Direct Selling Income</div>
            <div class="text-2xl font-extrabold text-emerald-600 mt-2">₹{{ number_format(round($summary['total_income'])) }}</div>
            <div class="text-xs text-emerald-600 font-semibold mt-1"><i class="fas fa-circle-check text-[10px] mr-1"></i>₹{{ number_format(round($summary['paid_income'])) }} paid out</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pending / In-Cycle Income</div>
            <div class="text-2xl font-extrabold text-amber-600 mt-2">₹{{ number_format(round($summary['pending_income'])) }}</div>
            <div class="text-xs text-slate-400 mt-1">Awaiting weekly cycle payout</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Reversals & Adjustments</div>
            <div class="text-2xl font-extrabold text-rose-600 mt-2">₹{{ number_format(round($summary['reversed_income'])) }}</div>
            <div class="text-xs text-slate-400 mt-1">Returns / clawbacks processed</div>
        </div>
    </div>

    <!-- Level-wise Income Matrix Table & Top Earners -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-sitemap text-blue-600"></i> Income & PV Breakdown by Level (0–19)
                </h2>
                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Income Rate: 20% of PV
                </span>
            </div>
            <div class="overflow-x-auto max-h-96">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50/90 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200/70 text-[10px] sticky top-0">
                        <tr>
                            <th class="px-5 py-3">Level</th>
                            <th class="px-5 py-3">PV Formula Tier</th>
                            <th class="px-5 py-3 text-center">Transactions</th>
                            <th class="px-5 py-3 text-center">Earners</th>
                            <th class="px-5 py-3 text-right">Total PV</th>
                            <th class="px-5 py-3 text-right">Total Income (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach($levelIncome as $lvl)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-2.5">
                                <span class="inline-flex px-2 py-0.5 rounded font-bold text-[11px] {{ $lvl['level'] <= 7 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                    Level {{ $lvl['level'] }}
                                </span>
                            </td>
                            <td class="px-5 py-2.5 text-slate-500 text-[11px]">{{ $lvl['tier'] }}</td>
                            <td class="px-5 py-2.5 text-center font-semibold text-slate-800">{{ $lvl['tx_count'] }}</td>
                            <td class="px-5 py-2.5 text-center font-semibold text-slate-800">{{ $lvl['member_count'] }}</td>
                            <td class="px-5 py-2.5 text-right font-mono font-semibold text-blue-600">{{ number_format($lvl['total_pv'], 4) }}</td>
                            <td class="px-5 py-2.5 text-right font-bold text-emerald-600">₹{{ number_format(round($lvl['total_income'])) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lg:col-span-4 bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs flex flex-col">
            <h2 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i class="fas fa-trophy text-amber-500"></i> Top Earning Members
            </h2>
            <div class="space-y-3 flex-1 overflow-y-auto max-h-80 pr-1">
                @forelse($topEarners as $earner)
                <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-200/60">
                    <div>
                        <div class="font-bold text-xs text-slate-900">{{ $earner->member?->user?->name ?? 'Unknown Member' }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">ID: {{ $earner->member?->customer_id }} &bull; {{ $earner->entries_count }} entries</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-xs text-emerald-600">₹{{ number_format(round($earner->total_income)) }}</div>
                        <div class="text-[10px] font-mono text-slate-400">{{ number_format($earner->total_pv, 2) }} PV</div>
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-slate-400">
                    <i class="far fa-folder-open text-2xl mb-1 block"></i>
                    <p class="text-xs">No earner records found in this period.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Income Ledger Transactions Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-list-alt text-blue-600"></i> Direct Selling Income Ledgers
            </h2>
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="date_preset" value="{{ $range['preset'] }}">
                <input type="hidden" name="date_from" value="{{ $range['date_from'] }}">
                <input type="hidden" name="date_to" value="{{ $range['date_to'] }}">
                
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="text-xs bg-slate-50 border border-slate-300 text-slate-800 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 focus:outline-none placeholder-slate-400" placeholder="Search Ref / Member...">
                <select name="level" class="text-xs bg-slate-50 border border-slate-300 text-slate-800 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">All Levels</option>
                    @for($i = 0; $i < 20; $i++)
                        <option value="{{ $i }}" {{ (isset($filters['level']) && $filters['level'] !== '' && (int)$filters['level'] === $i) ? 'selected' : '' }}>Level {{ $i }}</option>
                    @endfor
                </select>
                <select name="status" class="text-xs bg-slate-50 border border-slate-300 text-slate-800 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">All Status</option>
                    <option value="calculated" {{ ($filters['status'] ?? '') === 'calculated' ? 'selected' : '' }}>Calculated</option>
                    <option value="pending_approval" {{ ($filters['status'] ?? '') === 'pending_approval' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="reversed" {{ ($filters['status'] ?? '') === 'reversed' ? 'selected' : '' }}>Reversed</option>
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
                        <th class="px-5 py-3">Transaction Ref</th>
                        <th class="px-5 py-3">Beneficiary Member</th>
                        <th class="px-5 py-3">Purchaser</th>
                        <th class="px-5 py-3">Level</th>
                        <th class="px-5 py-3 text-right">Eligible Amt</th>
                        <th class="px-5 py-3 text-right">PV Generated</th>
                        <th class="px-5 py-3 text-right">Rate</th>
                        <th class="px-5 py-3 text-right">Income Amount</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($ledgers as $ledger)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-3 font-mono">
                            <span class="font-bold text-slate-900">{{ $ledger->source_transaction_reference }}</span>
                            @if(str_contains(strtoupper($ledger->source_transaction_reference), 'TEST') || str_contains(strtoupper($ledger->source_transaction_reference), 'DEMO'))
                                <span class="inline-flex px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 text-[9px] font-bold ml-1">TEST DATA</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="font-bold text-slate-900">{{ $ledger->member?->user?->name ?? 'Unknown' }}</div>
                            <div class="text-slate-400 text-[11px]">ID: {{ $ledger->member?->customer_id }}</div>
                        </td>
                        <td class="px-5 py-3">
                            <div class="font-medium text-slate-800">{{ $ledger->purchasingMember?->user?->name ?? 'Self' }}</div>
                            <div class="text-slate-400 text-[11px]">ID: {{ $ledger->purchasingMember?->customer_id ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded font-bold text-[10px] {{ $ledger->level <= 7 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                Level {{ $ledger->level }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right text-slate-800">₹{{ number_format($ledger->eligible_amount, 2) }}</td>
                        <td class="px-5 py-3 text-right font-mono font-semibold text-blue-600">{{ number_format($ledger->pv, 4) }} PV</td>
                        <td class="px-5 py-3 text-right text-slate-500">{{ number_format($ledger->rate * 100, 0) }}%</td>
                        <td class="px-5 py-3 text-right font-bold text-emerald-600">₹{{ number_format(round($ledger->calculated_amount)) }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $ledger->status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($ledger->status === 'reversed' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                {{ ucfirst(str_replace('_', ' ', $ledger->status)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-400 text-[11px]">{{ $ledger->transaction_date?->format('M d, Y') ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-5 py-8 text-center text-slate-400">No income ledger records found for the selected period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($ledgers->hasPages())
        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
            {{ $ledgers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
