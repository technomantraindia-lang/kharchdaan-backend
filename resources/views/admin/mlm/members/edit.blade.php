@extends('admin.layouts.app')

@section('title', 'Edit Member: ' . ($member->customer_id ?? 'ID#'.$member->id))

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-user-edit text-blue-600"></i> Edit Direct Selling Member: {{ $member->user?->name ?? 'Distributor' }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">Update profile information, KYC status, and distributor banking details.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ admin_route('mlm.members.show', $member) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-eye text-blue-500"></i> View Profile
            </a>
            <a href="{{ admin_route('mlm.members.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-arrow-left text-slate-400"></i> Back to Directory
            </a>
        </div>
    </div>

    <!-- Form Container Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Edit MLM Member Form &bull; <span class="font-mono text-blue-600">{{ $member->customer_id }}</span></h2>
            <span class="text-xs text-slate-500">Last updated: {{ $member->updated_at?->format('M d, Y') }}</span>
        </div>

        <form method="POST" action="{{ admin_route('mlm.members.update', $member) }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @include('admin.mlm.members._form')
            </div>

            <div class="pt-5 border-t border-slate-100 flex items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-save"></i> Update Distributor
                </button>
                <a href="{{ admin_route('mlm.members.show', $member) }}" class="inline-flex items-center px-4 py-2.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
