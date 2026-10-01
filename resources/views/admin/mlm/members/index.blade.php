@extends('admin.layouts.app')

@section('title', 'Direct Selling Members')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-users text-blue-600"></i> Direct Selling Members Directory
            </h1>
            <p class="text-sm text-slate-500 mt-1">Manage registered members, sponsor uplines, placement slots, and KYC compliance.</p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="{{ admin_route('mlm.sponsor-network') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-user-friends text-blue-500"></i> Sponsor Network
            </a>
            <a href="{{ admin_route('mlm.tree.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-sitemap text-indigo-500"></i> Placement Tree (1:3)
            </a>
            @if(auth()->user()->hasPermission('mlm.manage') || auth()->user()->hasPermission('mlm.create'))
                <a href="{{ admin_route('mlm.members.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-user-plus"></i> Add New Member
                </a>
            @endif
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
        <form method="GET" action="{{ admin_route('mlm.members.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Search</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                    <input type="text" name="search" class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" value="{{ request('search') }}" placeholder="Member Code, Name, Mobile, Email">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Account Status</label>
                <select name="status" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition">
                    <option value="">All Statuses</option>
                    @foreach(\App\Models\Member::STATUSES as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">KYC Status</label>
                <select name="kyc_status" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition">
                    <option value="">All KYC Status</option>
                    @foreach(\App\Models\Member::KYC_STATUSES as $status)
                        <option value="{{ $status }}" @selected(request('kyc_status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ admin_route('mlm.members.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Members Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Direct Selling Registered Members</h2>
            <span class="text-xs text-slate-500 font-medium">{{ $members->total() }} Total Registered</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Member Info</th>
                        <th class="py-3 px-4">Direct Sponsor</th>
                        <th class="py-3 px-4">Placement Matrix</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">KYC Status</th>
                        <th class="py-3 px-4">Joined Date</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($members as $member)
                        @php
                            $isTest = str_starts_with($member->customer_id ?? '', 'TEST-') || str_starts_with($member->user?->name ?? '', 'Test Leader');
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs ring-1 ring-slate-200/70 flex-shrink-0">
                                        {{ strtoupper(substr($member->user?->name ?? 'M', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <a href="{{ admin_route('mlm.members.show', $member) }}" class="font-bold text-slate-900 hover:text-blue-600 transition">
                                                {{ $member->user?->name ?? 'Unknown Member' }}
                                            </a>
                                            @if($isTest)
                                                <span class="px-1.5 py-0.2 rounded bg-purple-50 text-purple-700 border border-purple-200 text-[9px] font-bold uppercase">Test Data</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $member->customer_id }} &bull; {{ $member->user?->phone ?? 'No Phone' }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3 px-4">
                                @if($member->sponsor)
                                    <div class="font-semibold text-slate-800">{{ $member->sponsor->user?->name ?? 'Sponsor' }}</div>
                                    <div class="text-[11px] font-mono text-slate-500">{{ $member->sponsor->customer_id }}</div>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-600">Root Node</span>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                @if($member->placementParent)
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $member->placement_position === 'left' ? 'bg-blue-50 text-blue-700 border border-blue-200' : ($member->placement_position === 'middle' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200') }}">
                                            Slot: {{ ucfirst($member->placement_position) }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-1">Parent: <span class="font-mono font-medium text-slate-700">{{ $member->placementParent->customer_id }}</span></div>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-600">Matrix Root</span>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                @if($member->status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @elseif($member->status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending
                                    </span>
                                @elseif($member->status === 'blocked')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Blocked
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">
                                        {{ ucfirst($member->status) }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                @if($member->kyc_status === 'approved')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                        <i class="fas fa-check text-[10px]"></i> Approved
                                    </span>
                                @elseif($member->kyc_status === 'pending' || $member->kyc_status === 'under_review')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200/60">
                                        <i class="fas fa-clock text-[10px]"></i> Review Needed
                                    </span>
                                @elseif($member->kyc_status === 'rejected')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/60">
                                        <i class="fas fa-times text-[10px]"></i> Rejected
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">
                                        {{ ucfirst($member->kyc_status) }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-slate-600 font-medium">
                                {{ $member->joined_at?->format('M d, Y') ?? '-' }}
                            </td>

                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ admin_route('mlm.members.show', $member) }}" class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-600 text-slate-600 flex items-center justify-center transition" title="View Full 9-Tab Profile">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    @if(auth()->user()->hasPermission('mlm.manage') || auth()->user()->hasPermission('mlm.edit'))
                                        <a href="{{ admin_route('mlm.members.edit', $member) }}" class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition" title="Edit Member">
                                            <i class="fas fa-pen-to-square text-xs"></i>
                                        </a>
                                        <form action="{{ admin_route('mlm.members.toggleStatus', $member) }}" method="POST" class="inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $member->status === 'blocked' ? 'active' : ($member->status === 'active' ? 'inactive' : 'active') }}">
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 hover:bg-amber-50 hover:border-amber-300 hover:text-amber-600 text-slate-600 flex items-center justify-center transition" title="{{ $member->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                                <i class="fas fa-{{ $member->status === 'active' ? 'pause' : 'play' }} text-xs"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if(auth()->user()->hasPermission('mlm.kyc.view'))
                                        <a href="{{ admin_route('mlm.members.kyc', $member) }}" class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 text-slate-600 flex items-center justify-center transition" title="Review KYC Compliance">
                                            <i class="fas fa-id-card text-xs"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fas fa-users-slash text-3xl mb-2 text-slate-300 block"></i>
                                No Direct Selling members found matching your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($members->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end">
                {{ $members->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
