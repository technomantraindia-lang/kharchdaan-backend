@extends('admin.layouts.app')

@section('title', 'Levels & Income Matrix')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-layer-group text-blue-600"></i> Levels & Income Calculation Matrix
            </h1>
            <p class="text-sm text-slate-500 mt-1">Platform commission formulas, 20-tier compensation rules (Levels 0–19), and live calculation simulator.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('mlm.calculations.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                <i class="fas fa-calculator"></i> Calculation Engine
            </a>
        </div>
    </div>

    <!-- Formula Tier Explanation Cards (2 Columns) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- High PV Tier -->
        <div class="bg-gradient-to-br from-blue-50/80 to-indigo-50/60 rounded-xl border border-blue-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-blue-950 flex items-center gap-2">
                    <i class="fas fa-layer-group text-blue-600"></i> High PV Tier (Levels 0–7)
                </h2>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-600 text-white shadow-xs">8 Levels</span>
            </div>
            <div class="space-y-2.5 text-xs">
                <div>
                    <span class="text-slate-500 font-medium">PV FORMULA:</span>
                    <div class="font-mono font-bold text-slate-900 bg-white px-3.5 py-2 rounded-lg border border-blue-100 mt-1">
                        PV = (Eligible Amount &times; 13.50) / 3,000
                    </div>
                </div>
                <div>
                    <span class="text-slate-500 font-medium">DISTRIBUTOR INCOME:</span>
                    <div class="font-mono font-bold text-emerald-700 bg-white px-3.5 py-2 rounded-lg border border-blue-100 mt-1">
                        Income = PV &times; 20% (0.20)
                    </div>
                </div>
                <div class="pt-2 border-t border-blue-200/50 text-[11px] text-slate-700 flex items-center justify-between flex-wrap gap-1">
                    <span>Standard Example for ₹1,000:</span>
                    <span><strong>4.5000 PV</strong> &bull; <strong class="text-emerald-700 font-bold">₹0.90 per earner</strong></span>
                </div>
            </div>
        </div>

        <!-- Low PV Tier -->
        <div class="bg-gradient-to-br from-slate-50 to-amber-50/50 rounded-xl border border-amber-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-layer-group text-amber-600"></i> Low PV Tier (Levels 8–19)
                </h2>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-600 text-white shadow-xs">12 Levels</span>
            </div>
            <div class="space-y-2.5 text-xs">
                <div>
                    <span class="text-slate-500 font-medium">PV FORMULA:</span>
                    <div class="font-mono font-bold text-slate-900 bg-white px-3.5 py-2 rounded-lg border border-amber-100 mt-1">
                        PV = (Eligible Amount &times; 0.75) / 3,000
                    </div>
                </div>
                <div>
                    <span class="text-slate-500 font-medium">DISTRIBUTOR INCOME:</span>
                    <div class="font-mono font-bold text-emerald-700 bg-white px-3.5 py-2 rounded-lg border border-amber-100 mt-1">
                        Income = PV &times; 20% (0.20)
                    </div>
                </div>
                <div class="pt-2 border-t border-amber-200/50 text-[11px] text-slate-700 flex items-center justify-between flex-wrap gap-1">
                    <span>Standard Example for ₹1,000:</span>
                    <span><strong>0.2500 PV</strong> &bull; <strong class="text-emerald-700 font-bold">₹0.05 per earner</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Level-Wise Income Simulator -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-sliders text-blue-600"></i> Interactive Level-Wise Income Simulator
            </h2>
            <span class="text-xs text-slate-400">Inspect 20-level distribution</span>
        </div>

        <div class="p-5 space-y-4">
            <form method="GET" action="{{ admin_route('mlm.levels') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Eligible Amount (₹)</label>
                    <input type="number" name="simulate_amount" step="10" min="1" value="{{ $simAmount }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition font-semibold" placeholder="1000">
                </div>
                <div class="sm:col-span-2">
                    <label class="block font-semibold text-slate-600 mb-1">Purchasing Member Context</label>
                    <select name="simulate_member_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition">
                        <option value="">General Network Simulation (Levels 0–19)</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}" {{ ($simMember && $simMember->id === $m->id) ? 'selected' : '' }}>
                                {{ $m->customer_id ?? 'ID#'.$m->id }} &mdash; {{ $m->user?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5">
                        <i class="fas fa-play"></i> Simulate Distribution
                    </button>
                </div>
            </form>

            <!-- Simulation Summary Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 rounded-xl bg-slate-50/80 border border-slate-200/80">
                <div>
                    <div class="text-[11px] text-slate-500 font-semibold uppercase">Simulated Purchase</div>
                    <div class="text-lg font-bold text-slate-900 mt-0.5">₹{{ number_format($simulation['amount'], 2) }}</div>
                </div>
                <div>
                    <div class="text-[11px] text-slate-500 font-semibold uppercase">Total Cumulative PV</div>
                    <div class="text-lg font-mono font-bold text-indigo-600 mt-0.5">{{ number_format($simulation['total_pv'], 4) }} PV</div>
                </div>
                <div>
                    <div class="text-[11px] text-slate-500 font-semibold uppercase">Total 20-Level Commission</div>
                    <div class="text-lg font-bold text-emerald-600 mt-0.5">₹{{ number_format(round($simulation['total_income'])) }}</div>
                </div>
            </div>

            <!-- Level-by-Level Breakdown Table -->
            <div class="overflow-x-auto max-h-96 overflow-y-auto border border-slate-100 rounded-lg">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-slate-50/95 backdrop-blur-xs border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-4">Level</th>
                            <th class="py-2.5 px-4">Tier Classification</th>
                            <th class="py-2.5 px-4 text-center">PV Factor</th>
                            <th class="py-2.5 px-4 text-right">Simulated PV</th>
                            <th class="py-2.5 px-4 text-right">Commission Rate</th>
                            <th class="py-2.5 px-4 text-right">Level Income (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        @foreach($simulation['breakdown'] as $b)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-2 px-4">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $b['level'] <= 7 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                        Level {{ $b['level'] }}
                                    </span>
                                    @if($b['level'] === 0)
                                        <span class="text-[10px] text-slate-400 ml-1">(Own Purchase)</span>
                                    @endif
                                </td>
                                <td class="py-2 px-4 text-slate-500">{{ $b['tier'] }}</td>
                                <td class="py-2 px-4 text-center font-mono font-semibold text-slate-700">{{ $b['factor'] }}</td>
                                <td class="py-2 px-4 text-right font-mono font-bold text-indigo-600">{{ number_format($b['pv'], 4) }} PV</td>
                                <td class="py-2 px-4 text-right text-slate-600">{{ $b['income_rate'] }}</td>
                                <td class="py-2 px-4 text-right font-bold text-emerald-600">₹{{ number_format(round($b['income'])) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200 font-bold text-xs">
                        <tr>
                            <td colspan="3" class="py-2.5 px-4 text-slate-900 uppercase">Total 20-Level Output:</td>
                            <td class="py-2.5 px-4 text-right font-mono text-indigo-700">{{ number_format($simulation['total_pv'], 4) }} PV</td>
                            <td></td>
                            <td class="py-2.5 px-4 text-right text-emerald-700 text-sm">₹{{ number_format(round($simulation['total_income'])) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Comprehensive 20-Level Platform Statistics Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-database text-blue-600"></i> Live Database Statistics Across Levels 0–19
            </h2>
            <span class="text-xs text-slate-400">Aggregated from active member records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Level</th>
                        <th class="py-3 px-4">Tier</th>
                        <th class="py-3 px-4">PV Calculation Formula</th>
                        <th class="py-3 px-4 text-center">Database Members</th>
                        <th class="py-3 px-4 text-center">Active Members</th>
                        <th class="py-3 px-4 text-right">All-Time PV</th>
                        <th class="py-3 px-4 text-right">All-Time Income Earned</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @foreach($levelStats as $stat)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-2.5 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold uppercase {{ $stat['level'] <= 7 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                    Level {{ $stat['level'] }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-slate-500">{{ $stat['tier_name'] }}</td>
                            <td class="py-2.5 px-4 font-mono text-slate-600 text-[11px]">{{ $stat['pv_formula'] }} &bull; Rate: 20%</td>
                            <td class="py-2.5 px-4 text-center font-bold text-slate-900">{{ number_format($stat['member_count']) }}</td>
                            <td class="py-2.5 px-4 text-center font-semibold text-emerald-600">{{ number_format($stat['active_member_count']) }}</td>
                            <td class="py-2.5 px-4 text-right font-mono font-bold text-indigo-600">{{ number_format($stat['db_total_pv'], 4) }}</td>
                            <td class="py-2.5 px-4 text-right font-bold text-emerald-600">₹{{ number_format(round($stat['db_total_income'])) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
