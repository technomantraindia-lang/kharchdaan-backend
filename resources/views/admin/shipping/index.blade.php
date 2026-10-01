@extends('admin.layouts.app')

@section('title', 'Shipping Methods')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-truck-fast text-blue-600"></i> Shipping Methods & Delivery
            </h1>
            <p class="text-sm text-slate-500 mt-1">Configure shipping rates, delivery rules, and minimum cart thresholds for free shipping.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('products.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-boxes-stacked text-slate-400"></i> Products Catalog
            </a>
            @if(auth()->user()->hasPermission('shipping.manage') || auth()->user()->isSuperAdmin())
                <a href="{{ admin_route('shipping.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-plus"></i> Add Shipping Method
                </a>
            @endif
        </div>
    </div>

    <!-- Shipping Table Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Available Shipping Methods</h3>
            <span class="text-xs text-slate-400 font-medium">{{ $methods->total() }} methods</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-5">Method Name</th>
                        <th class="py-3.5 px-5">Flat Shipping Fee</th>
                        <th class="py-3.5 px-5">Free Shipping Min Order</th>
                        <th class="py-3.5 px-5 text-center">Status</th>
                        <th class="py-3.5 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($methods as $method)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-5">
                            <div class="font-bold text-slate-900 text-sm">{{ $method->name }}</div>
                            <div class="text-[11px] text-slate-400">ID: #{{ $method->id }}</div>
                        </td>
                        <td class="py-3.5 px-5 font-bold text-slate-900">
                            ₹{{ number_format($method->charge, 2) }}
                        </td>
                        <td class="py-3.5 px-5 text-slate-600">
                            {{ $method->min_free_order ? '₹' . number_format($method->min_free_order, 2) : 'No Free Threshold' }}
                        </td>
                        <td class="py-3.5 px-5 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $method->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $method->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ ucfirst($method->status) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-5 text-right">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                @if(auth()->user()->hasPermission('shipping.manage') || auth()->user()->isSuperAdmin())
                                    <a href="{{ admin_route('shipping.edit', $method) }}" title="Edit Method" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-blue-600 hover:bg-blue-50 flex items-center justify-center transition shadow-2xs">
                                        <i class="fas fa-pen-to-square text-xs"></i>
                                    </a>
                                    <form action="{{ admin_route('shipping.destroy', $method) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this shipping method?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Delete Method" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition shadow-2xs">
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
                            <i class="fas fa-truck text-4xl mb-3 text-slate-300 block"></i>
                            <p class="font-medium text-slate-600">No shipping methods configured</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($methods->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $methods->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
