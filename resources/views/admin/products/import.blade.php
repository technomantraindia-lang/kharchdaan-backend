@extends('admin.layouts.app')

@section('title', 'Import Products CSV')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-file-csv text-blue-600"></i> Import Products (CSV)
            </h1>
            <p class="text-sm text-slate-500 mt-1">Upload a CSV file to bulk import products into the catalog with automated inventory allocation.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ admin_route('products.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-arrow-left text-slate-400"></i> Back to Products
            </a>
        </div>
    </div>

    <!-- Upload Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
        <form action="{{ admin_route('products.importStore') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Select CSV Spreadsheet *</label>
                <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                <div class="text-[11px] text-slate-400 mt-1.5">Supported formats: .csv, .txt (UTF-8 encoded)</div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-upload"></i> Start CSV Import
                </button>
                <a href="{{ admin_route('products.index') }}" class="px-4 py-2.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Cancel
                </a>
            </div>
        </form>

        <div class="border-t border-slate-100 pt-6 space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                <i class="fas fa-circle-info text-blue-500"></i> CSV Format & Sample Template
            </h4>
            <p class="text-xs text-slate-500">Required and optional column headers:</p>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-700">
                name, sku, category, price, sale_price, stock_qty, unit, status, gst_percentage
            </div>
            <pre class="bg-navy-950 text-slate-200 p-4 rounded-xl text-xs font-mono overflow-x-auto">name,sku,category,price,sale_price,stock_qty,unit,status,gst_percentage
Direct Selling Starter Pack,KD001,Packages,1500,1000,100,pack,active,5
KharchDaan Wellness Pack,KD002,Wellness,2000,1800,50,packet,active,5</pre>
        </div>
    </div>
</div>
@endsection
