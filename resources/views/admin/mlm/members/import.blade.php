@extends('admin.layouts.app')

@section('title', 'Import Customer Master')

@section('content')
<div class="space-y-6 max-w-3xl">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Upload Customer Master (CSV)</h1>
            <p class="text-sm text-slate-500 mt-1">Non-destructive import — creates/updates members without wiping the placement tree.</p>
        </div>
        <a href="{{ admin_route('mlm.members.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">← Back to Members</a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
        <div class="text-xs text-slate-600 leading-relaxed">
            <p class="font-semibold text-slate-800 mb-1">Required CSV columns</p>
            <code class="text-[11px] bg-slate-50 border border-slate-200 rounded px-2 py-1 inline-block">code, name, mobile, email, sponsor_code, status</code>
            <p class="mt-2">Aliases accepted: <code>customer_id</code>, <code>phone</code>, <code>referral_id</code>/<code>sponsor</code>.</p>
        </div>

        <form method="POST" action="{{ admin_route('mlm.members.import.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Customer Master CSV *</label>
                <input type="file" name="customer_master_csv" accept=".csv,text/csv" required
                       class="w-full text-xs file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-orange-50 file:text-orange-800 file:font-semibold">
            </div>
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-xs">
                <i class="fas fa-file-upload"></i> Import Customer Master
            </button>
        </form>
    </div>
</div>
@endsection
