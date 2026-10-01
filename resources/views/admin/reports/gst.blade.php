@extends('admin.layouts.app')

@section('title', 'GST & Tax Report')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">GST & Tax Compliance Report</h1>
            <p class="text-sm text-slate-500 mt-1">Tax liabilities breakdown by CGST, SGST, IGST and HSN summary.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ admin_route('reports.export', ['module' => 'gst', 'date_preset' => request('date_preset', 'this_month'), 'date_from' => request('date_from'), 'date_to' => request('date_to'), 'hsn' => request('hsn')]) }}" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 shadow-sm transition">
                <i class="fas fa-file-csv text-emerald-600"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Navigation & Filters -->
    @include('admin.reports.partials.nav', ['active' => 'gst'])
    @include('admin.reports.partials.filter', ['action' => admin_route('reports.gst'), 'range' => $range])

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Taxable Value</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                    <i class="fas fa-calculator"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-xl font-bold text-slate-900">₹{{ number_format($gst['total_taxable'], 2) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total GST</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-xl font-bold text-slate-900">₹{{ number_format($gst['total_gst'], 2) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">CGST (Intra-State)</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fas fa-building-columns"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-xl font-bold text-slate-900">₹{{ number_format($gst['total_cgst'], 2) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">SGST (Intra-State)</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fas fa-landmark"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-xl font-bold text-slate-900">₹{{ number_format($gst['total_sgst'], 2) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">IGST (Inter-State)</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm">
                    <i class="fas fa-plane-departure"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-xl font-bold text-slate-900">₹{{ number_format($gst['total_igst'], 2) }}</span>
            </div>
        </div>
    </div>

    <!-- HSN Summary Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-800">HSN Code Breakdown</h2>
                <p class="text-xs text-slate-500">Tax summary grouped by Harmonized System of Nomenclature (HSN).</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">HSN Code</th>
                        <th class="py-3 px-4 text-right">Total Units Sold</th>
                        <th class="py-3 px-4 text-right">Total Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($gst['hsn_summary'] as $hsn)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 font-mono font-semibold text-slate-900">{{ $hsn['hsn_code'] }}</td>
                            <td class="py-3 px-4 text-right font-medium">{{ number_format($hsn['total_qty']) }}</td>
                            <td class="py-3 px-4 text-right font-semibold text-slate-900">₹{{ number_format($hsn['revenue'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-slate-400">
                                <i class="fas fa-inbox text-3xl mb-2 text-slate-300 block"></i>
                                No HSN breakdown data available for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
