@extends('admin.layouts.app')

@section('title', 'Profit & Margin Report')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Profit & Margin Report</h1>
            <p class="text-sm text-slate-500 mt-1">Financial margins, Cost of Goods Sold (COGS), and product-wise profitability.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ admin_route('reports.export', ['module' => 'profit', 'date_preset' => request('date_preset', 'this_month'), 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 shadow-sm transition">
                <i class="fas fa-file-csv text-emerald-600"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Navigation & Filters -->
    @include('admin.reports.partials.nav', ['active' => 'profit'])
    @include('admin.reports.partials.filter', ['action' => admin_route('reports.profit'), 'range' => $range])

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Revenue</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fas fa-arrow-trend-up"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold text-slate-900">₹{{ number_format($profit['revenue'], 2) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Cost of Goods (COGS)</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fas fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold text-slate-900">₹{{ number_format($profit['cogs'], 2) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Gross Profit</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                    <i class="fas fa-sack-dollar"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold text-slate-900">₹{{ number_format($profit['gross_profit'], 2) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Gross Margin</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                    <i class="fas fa-percent"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold {{ $profit['gross_margin_pct'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($profit['gross_margin_pct'], 2) }}%</span>
            </div>
        </div>
    </div>

    <!-- Product Breakdown Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-800">Product Profitability Breakdown</h2>
                <p class="text-xs text-slate-500">Margin and revenue per catalog product for the selected date range.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Product Name</th>
                        <th class="py-3 px-4">SKU</th>
                        <th class="py-3 px-4 text-right">Units Sold</th>
                        <th class="py-3 px-4 text-right">Revenue</th>
                        <th class="py-3 px-4 text-right">Cost (COGS)</th>
                        <th class="py-3 px-4 text-right">Gross Profit</th>
                        <th class="py-3 px-4 text-right">Margin %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($profit['product_breakdown'] as $prod)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 font-semibold text-slate-900">{{ $prod['name'] }}</td>
                            <td class="py-3 px-4 text-slate-500 font-mono">{{ $prod['sku'] ?? 'N/A' }}</td>
                            <td class="py-3 px-4 text-right font-medium">{{ number_format($prod['units_sold']) }}</td>
                            <td class="py-3 px-4 text-right font-semibold text-slate-900">₹{{ number_format($prod['revenue'], 2) }}</td>
                            <td class="py-3 px-4 text-right text-slate-600">₹{{ number_format($prod['cost'], 2) }}</td>
                            <td class="py-3 px-4 text-right font-bold {{ $prod['gross_profit'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">₹{{ number_format($prod['gross_profit'], 2) }}</td>
                            <td class="py-3 px-4 text-right font-bold {{ $prod['margin_pct'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($prod['margin_pct'], 2) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                <i class="fas fa-inbox text-3xl mb-2 text-slate-300 block"></i>
                                No product sales recorded for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
