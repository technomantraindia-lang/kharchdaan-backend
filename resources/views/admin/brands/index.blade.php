@extends('admin.layouts.app')

@section('title', 'Brands & Manufacturers')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-tags text-blue-600"></i> Brands & Manufacturers
            </h1>
            <p class="text-sm text-slate-500 mt-1">Manage product brands, logos, and manufacturer associations.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('products.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-boxes-stacked text-slate-400"></i> Products Catalog
            </a>
            @if(auth()->user()->hasPermission('brands.manage') || auth()->user()->isSuperAdmin())
                <a href="{{ admin_route('brands.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-plus"></i> Add Brand
                </a>
            @endif
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
        <form method="GET" action="{{ admin_route('brands.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Search</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                    <input type="text" name="search" class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" placeholder="Search brands by name or slug..." value="{{ request('search') }}">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status</label>
                <select name="status" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ admin_route('brands.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Brands Table Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Brands Directory</h3>
            <span class="text-xs text-slate-400 font-medium">{{ $brands->total() }} brands</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-5">Brand Details</th>
                        <th class="py-3.5 px-5">Slug</th>
                        <th class="py-3.5 px-5 text-center">Linked Products</th>
                        <th class="py-3.5 px-5 text-center">Status</th>
                        <th class="py-3.5 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($brands as $brand)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-5">
                            <div class="flex items-center gap-3">
                                @if($brand->image)
                                    <img src="{{ route('media.file', ['path' => $brand->image]) }}" alt="{{ $brand->name }}" class="w-10 h-10 rounded-lg border border-slate-200 object-contain p-1 bg-white flex-shrink-0 shadow-2xs">
                                @else
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 flex-shrink-0">
                                        <i class="fas fa-image text-sm"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="font-bold text-slate-900 text-sm">{{ $brand->name }}</div>
                                    <div class="text-[11px] text-slate-400">ID: #{{ $brand->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-5 font-mono text-slate-600 text-[11px]">
                            {{ $brand->slug }}
                        </td>
                        <td class="py-3.5 px-5 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                {{ $brand->products_count }} products
                            </span>
                        </td>
                        <td class="py-3.5 px-5 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $brand->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $brand->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ ucfirst($brand->status) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-5 text-right">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                @if(auth()->user()->hasPermission('brands.manage') || auth()->user()->isSuperAdmin())
                                    <a href="{{ admin_route('brands.edit', $brand) }}" title="Edit Brand" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-blue-600 hover:bg-blue-50 hover:border-blue-200 flex items-center justify-center transition shadow-2xs">
                                        <i class="fas fa-pen-to-square text-xs"></i>
                                    </a>
                                    <form action="{{ admin_route('brands.destroy', $brand) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this brand?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete Brand" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 flex items-center justify-center transition shadow-2xs">
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
                            <i class="fas fa-tags text-4xl mb-3 text-slate-300 block"></i>
                            <p class="font-medium text-slate-600">No brands found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($brands->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $brands->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
