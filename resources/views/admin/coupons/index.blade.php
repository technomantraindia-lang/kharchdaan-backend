@extends('admin.layouts.app')

@section('title', 'Coupons & Discounts')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-ticket text-blue-600"></i> Coupons & Promo Codes
            </h1>
            <p class="text-sm text-slate-500 mt-1">Create and manage discount codes, usage limitations, and promotional offers.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('products.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-boxes-stacked text-slate-400"></i> Products Catalog
            </a>
            @if(auth()->user()->hasPermission('coupons.manage') || auth()->user()->isSuperAdmin())
                <a href="{{ admin_route('coupons.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-plus"></i> Add Coupon
                </a>
            @endif
        </div>
    </div>

    <!-- Coupons Table Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Active Coupons</h3>
            <span class="text-xs text-slate-400 font-medium">{{ $coupons->total() }} total</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-5">Coupon Code</th>
                        <th class="py-3.5 px-5">Discount Type</th>
                        <th class="py-3.5 px-5">Discount Value</th>
                        <th class="py-3.5 px-5">Min Order</th>
                        <th class="py-3.5 px-5 text-center">Usage Count</th>
                        <th class="py-3.5 px-5">Valid Until</th>
                        <th class="py-3.5 px-5 text-center">Status</th>
                        <th class="py-3.5 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($coupons as $coupon)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-5">
                            <span class="font-mono font-bold text-sm text-blue-700 bg-blue-50 px-2.5 py-1 rounded-md border border-blue-200">
                                {{ $coupon->code }}
                            </span>
                        </td>
                        <td class="py-3.5 px-5 text-slate-600 capitalize">
                            {{ $coupon->type }}
                        </td>
                        <td class="py-3.5 px-5 font-bold text-slate-900">
                            {{ $coupon->type === 'percentage' ? $coupon->value . '%' : '₹' . number_format($coupon->value, 2) }}
                        </td>
                        <td class="py-3.5 px-5 text-slate-600">
                            ₹{{ number_format($coupon->min_order ?? 0, 0) }}
                        </td>
                        <td class="py-3.5 px-5 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                {{ $coupon->usages_count }}{{ $coupon->usage_limit ? ' / ' . $coupon->usage_limit : '' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-5 text-slate-500">
                            {{ $coupon->end_date?->format('M d, Y') ?? 'No Expiry' }}
                        </td>
                        <td class="py-3.5 px-5 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $coupon->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $coupon->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ ucfirst($coupon->status) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-5 text-right">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                @if(auth()->user()->hasPermission('coupons.manage') || auth()->user()->isSuperAdmin())
                                    <a href="{{ admin_route('coupons.edit', $coupon) }}" title="Edit Coupon" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-blue-600 hover:bg-blue-50 flex items-center justify-center transition shadow-2xs">
                                        <i class="fas fa-pen-to-square text-xs"></i>
                                    </a>
                                    <form action="{{ admin_route('coupons.destroy', $coupon) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this coupon?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Delete Coupon" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition shadow-2xs">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <i class="fas fa-ticket text-4xl mb-3 text-slate-300 block"></i>
                            <p class="font-medium text-slate-600">No coupons found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($coupons->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $coupons->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
