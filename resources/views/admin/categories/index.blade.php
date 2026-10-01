@extends('admin.layouts.app')

@section('title', 'Product Categories')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-folder-tree text-blue-600"></i> Product Categories
            </h1>
            <p class="text-sm text-slate-500 mt-1">Organize products into hierarchical categories and sub-categories.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('products.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-boxes-stacked text-slate-400"></i> All Products
            </a>
            @if(auth()->user()->hasPermission('categories.create') || auth()->user()->isSuperAdmin())
                <a href="{{ admin_route('categories.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-plus"></i> Add Category
                </a>
            @endif
        </div>
    </div>

    <!-- Categories Table Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Categories List</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-4">Category Name</th>
                        <th class="py-3.5 px-4">Parent Category</th>
                        <th class="py-3.5 px-4 text-center">Linked Products</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-4 font-bold text-slate-900">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-folder text-xs"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900">{{ $category->name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ $category->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 text-slate-600">
                            {{ $category->parent?->parent_path ?? '— (Top Level)' }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                {{ $category->main_products_count + $category->sub_products_count + $category->sub_sub_products_count }} products
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $category->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $category->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ ucfirst($category->status) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="{{ admin_route('categories.edit', $category) }}" title="Edit Category" class="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-blue-600 hover:bg-blue-50 flex items-center justify-center transition shadow-2xs">
                                    <i class="fas fa-pen-to-square text-xs"></i>
                                </a>
                                <form action="{{ admin_route('categories.destroy', $category) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this category?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Delete Category" class="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition shadow-2xs">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400">
                            <i class="fas fa-folder-open text-4xl mb-3 text-slate-300 block"></i>
                            <p class="font-medium text-slate-600">No categories found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $categories->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
