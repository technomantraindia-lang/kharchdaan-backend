@extends('admin.layouts.app')

@section('title', 'Customer Profile: ' . $customer->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-user text-blue-600"></i> Customer Profile: {{ $customer->name }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">Customer ID #{{ $customer->id }} &bull; Registered {{ $customer->created_at->format('M d, Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if($customer->mlmMember)
                <a href="{{ admin_route('mlm.members.show', $customer->mlmMember) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-id-card"></i> View Direct Selling Member
                </a>
            @endif
            <a href="{{ admin_route('customers.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-arrow-left text-slate-400"></i> Back to Directory
            </a>
        </div>
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Profile & Addresses -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-sm font-bold text-slate-900">Account Information</h2>
                </div>
                <div class="p-5 space-y-3 text-xs">
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Email Address</span>
                        <span class="font-semibold text-slate-900">{{ $customer->email }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Phone Number</span>
                        <span class="font-mono text-slate-900">{{ $customer->phone ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Account Status</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $customer->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                            {{ ucfirst($customer->status) }}
                        </span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Direct Selling Member</span>
                        <span>
                            @if($customer->mlmMember)
                                <span class="font-mono font-bold text-blue-600">{{ $customer->mlmMember->customer_id }}</span>
                            @else
                                <span class="text-slate-400">Not converted</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-slate-500 font-medium">Registration Date</span>
                        <span class="text-slate-900">{{ $customer->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                </div>
            </div>

            @if($customer->addresses->count())
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-sm font-bold text-slate-900">Saved Addresses</h2>
                    </div>
                    <div class="p-5 space-y-3">
                        @foreach($customer->addresses as $addr)
                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 text-xs">
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 uppercase mb-1.5">{{ ucfirst($addr->type) }}</span>
                                <div class="font-semibold text-slate-900">{{ $addr->fname }} {{ $addr->lname }}</div>
                                <div class="text-slate-600 mt-0.5">{{ $addr->address }}, {{ $addr->city }}, {{ $addr->state }} - {{ $addr->zip }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Orders Table -->
        <div class="lg:col-span-8">
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900">Purchase Orders History</h2>
                    <span class="text-xs text-slate-500">{{ $customer->orders->count() }} Orders</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3 px-4">Order #</th>
                                <th class="py-3 px-4 text-right">Order Total</th>
                                <th class="py-3 px-4">Order Status</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                            @forelse ($customer->orders as $order)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3 px-4">
                                        <a href="{{ admin_route('orders.show', $order) }}" class="font-bold text-blue-600 hover:underline">
                                            {{ $order->order_num }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-4 text-right font-semibold text-slate-900">₹{{ number_format($order->total, 2) }}</td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-500">{{ $order->created_at->format('M d, Y') }}</td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="{{ admin_route('orders.show', $order) }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition">
                                            <i class="fas fa-eye text-[11px]"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400">
                                        No purchase orders placed by this customer yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
