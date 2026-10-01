@extends('admin.layouts.app')

@section('title', 'Add New Product')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-plus-circle text-blue-600"></i> Add New Product
            </h1>
            <p class="text-sm text-slate-500 mt-1">Add a new item to the store catalog with pricing, stock tracking, and direct selling attributes.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ admin_route('products.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-arrow-left text-slate-400"></i> Back to Products
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <form action="{{ admin_route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('admin.products._form')
            
            <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ admin_route('products.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-check"></i> Save & Publish Product
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
