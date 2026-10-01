@extends('admin.layouts.app')

@section('title', 'Customers Directory')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-address-book text-blue-600"></i> Customers Directory
            </h1>
            <p class="text-sm text-slate-500 mt-1">Manage platform customer accounts, order histories, and profile linking.</p>
        </div>
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-200">
                <i class="fas fa-users"></i> {{ $customers->total() }} Total Registered
            </span>
        </div>
    </div>

    <!-- Search / Filter Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
        <form method="GET" class="flex flex-col sm:flex-row items-center gap-3 text-xs">
            <div class="relative flex-1 w-full">
                <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                <input type="text" name="search" class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" placeholder="Search customer name, email address, phone..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="w-full sm:w-auto px-5 py-2 font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition shadow-xs flex items-center justify-center gap-1.5">
                <i class="fas fa-search"></i> Search
            </button>
            @if(request('search'))
                <a href="{{ admin_route('customers.index') }}" class="w-full sm:w-auto px-4 py-2 font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition text-center">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Customers Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Registered Customer Records</h2>
            <span class="text-xs text-slate-500">Page {{ $customers->currentPage() }} of {{ $customers->lastPage() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Customer Name</th>
                        <th class="py-3 px-4">Email Address</th>
                        <th class="py-3 px-4">Phone</th>
                        <th class="py-3 px-4 text-center">Orders</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 font-semibold text-slate-900">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 font-bold flex items-center justify-center text-xs">
                                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                                    </div>
                                    <span>{{ $customer->name }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-slate-600">{{ $customer->email }}</td>
                            <td class="py-3 px-4 font-mono text-slate-500">{{ $customer->phone ?? '—' }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                    {{ $customer->orders_count }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $customer->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $customer->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }} mr-1"></span>
                                    {{ ucfirst($customer->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ admin_route('customers.show', $customer) }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-md transition">
                                    <i class="fas fa-eye text-[11px]"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                No customer accounts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
