@extends('admin.layouts.app')

@section('title', 'Tax Rates & GST')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-receipt text-blue-600"></i> Tax Rates (GST)
            </h1>
            <p class="text-sm text-slate-500 mt-1">Configure applicable GST tax slabs and compliance rates for store products.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('products.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-boxes-stacked text-slate-400"></i> Products Catalog
            </a>
            @if(auth()->user()->hasPermission('taxes.manage') || auth()->user()->isSuperAdmin())
                <a href="{{ admin_route('taxes.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-plus"></i> Add Tax Slab
                </a>
            @endif
        </div>
    </div>

    <!-- Taxes Table Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Tax Slabs</h3>
            <span class="text-xs text-slate-400 font-medium">{{ $taxes->total() }} slabs</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-5">Tax Slab Name</th>
                        <th class="py-3.5 px-5">Tax Rate (%)</th>
                        <th class="py-3.5 px-5">Description</th>
                        <th class="py-3.5 px-5 text-center">Status</th>
                        <th class="py-3.5 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($taxes as $tax)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-5 font-bold text-slate-900 text-sm">
                            {{ $tax->name }}
                        </td>
                        <td class="py-3.5 px-5 font-bold text-slate-900 text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                {{ $tax->percentage }}%
                            </span>
                        </td>
                        <td class="py-3.5 px-5 text-slate-600">
                            {{ $tax->desc ?? '—' }}
                        </td>
                        <td class="py-3.5 px-5 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $tax->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $tax->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ ucfirst($tax->status) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-5 text-right">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                @if(auth()->user()->hasPermission('taxes.manage') || auth()->user()->isSuperAdmin())
                                    <a href="{{ admin_route('taxes.edit', $tax) }}" title="Edit Tax" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-blue-600 hover:bg-blue-50 flex items-center justify-center transition shadow-2xs">
                                        <i class="fas fa-pen-to-square text-xs"></i>
                                    </a>
                                    <form action="{{ admin_route('taxes.destroy', $tax) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this tax slab?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Delete Tax" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition shadow-2xs">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400">
                            <i class="fas fa-receipt text-4xl mb-3 text-slate-300 block"></i>
                            <p class="font-medium text-slate-600">No tax slabs configured</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($taxes->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $taxes->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
