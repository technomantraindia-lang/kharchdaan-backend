@extends('admin.layouts.app')

@section('title', 'Product Attributes')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-sliders-h text-blue-600"></i> Product Attributes
            </h1>
            <p class="text-sm text-slate-500 mt-1">Manage global product attributes (e.g. Size, Weight, Color, Pack Size) and option values for variations.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('products.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-boxes-stacked text-slate-400"></i> Products Catalog
            </a>
            @if(auth()->user()->hasPermission('attributes.manage') || auth()->user()->isSuperAdmin())
                <a href="{{ admin_route('attributes.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-plus"></i> Add Attribute
                </a>
            @endif
        </div>
    </div>

    <!-- Attributes Table Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Configured Attributes List</h3>
            <span class="text-xs text-slate-400 font-medium">{{ $attributes->total() }} total attributes</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-5">Attribute Name</th>
                        <th class="py-3.5 px-5">Configured Option Values</th>
                        <th class="py-3.5 px-5 text-center">Status</th>
                        <th class="py-3.5 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($attributes as $attribute)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0 text-sm">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 text-sm">{{ $attribute->name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono mt-0.5">slug: {{ \Illuminate\Support\Str::slug($attribute->name) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-5">
                            @if(isset($attribute->values) && $attribute->values->count())
                                <div class="flex flex-wrap gap-1.5 max-w-md">
                                    @foreach($attribute->values as $val)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200/80">
                                            {{ $val->value }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                    {{ $attribute->values_count ?? 0 }} values
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-5 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $attribute->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $attribute->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ ucfirst($attribute->status) }}
                            </span>
                        </td>
                        <td class="py-4 px-5 text-right">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                @if(auth()->user()->hasPermission('attributes.manage') || auth()->user()->isSuperAdmin())
                                    <a href="{{ admin_route('attributes.edit', $attribute) }}" title="Edit Attribute" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-blue-600 hover:bg-blue-50 hover:border-blue-200 flex items-center justify-center transition shadow-2xs">
                                        <i class="fas fa-pen-to-square text-xs"></i>
                                    </a>
                                    <form action="{{ admin_route('attributes.destroy', $attribute) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this attribute?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete Attribute" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 flex items-center justify-center transition shadow-2xs">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center text-slate-400">
                            <i class="fas fa-sliders-h text-4xl mb-3 text-slate-300 block"></i>
                            <p class="font-medium text-slate-600">No attributes found</p>
                            <p class="text-xs text-slate-400 mt-1">Create attributes like Size, Color, or Weight to use in product variations.</p>
                            <a href="{{ admin_route('attributes.create') }}" class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                                <i class="fas fa-plus"></i> Add First Attribute
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attributes->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $attributes->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
