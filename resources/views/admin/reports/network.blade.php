@extends('admin.layouts.app')

@section('title', 'Network Reports')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Network & Direct Selling Reports</h1>
            <p class="text-xs text-slate-500 mt-0.5">Member distribution, Level 0–19 depth, sponsor hierarchy, and placement analytics</p>
        </div>
        <div>
            <a href="{{ admin_route('reports.export', ['module' => 'members', 'date_preset' => $range['preset'], 'date_from' => $range['date_from'], 'date_to' => $range['date_to']]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                <i class="fas fa-file-export text-xs"></i> Export Network CSV
            </a>
        </div>
    </div>

    @include('admin.reports.partials.nav')
    @include('admin.reports.partials.filter')

    <!-- Network Summary Cards (4-card Grid) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Network Members</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-2">{{ number_format($summary['total_members']) }}</div>
            <div class="text-xs text-emerald-600 font-semibold mt-1"><i class="fas fa-arrow-up text-[10px] mr-1"></i>{{ $summary['new_members'] }} joined in period</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active vs Inactive</div>
            <div class="text-2xl font-extrabold text-emerald-600 mt-2">
                {{ number_format($summary['active_members']) }}
                <span class="text-xs font-normal text-slate-400">/ {{ number_format($summary['inactive_members'] + $summary['pending_members']) }} Inactive</span>
            </div>
            <div class="text-xs text-slate-400 mt-1">{{ $summary['blocked_members'] }} account(s) blocked</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">KYC Verification</div>
            <div class="text-2xl font-extrabold text-blue-600 mt-2">
                {{ number_format($summary['kyc_approved']) }}
                <span class="text-xs font-normal text-slate-400">Approved</span>
            </div>
            <div class="text-xs text-amber-600 font-semibold mt-1">{{ $summary['kyc_pending'] + $summary['kyc_under_review'] }} pending / {{ $summary['kyc_rejected'] }} rejected</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">1:3 Placement Matrix Slots</div>
            <div class="flex items-center gap-1.5 mt-2">
                <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-xs font-bold border border-blue-200">L: {{ $summary['placement_left'] }}</span>
                <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 text-xs font-bold border border-indigo-200">M: {{ $summary['placement_middle'] }}</span>
                <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200">R: {{ $summary['placement_right'] }}</span>
            </div>
            <div class="text-xs text-slate-400 mt-1">{{ $summary['placement_roots'] }} top root member(s)</div>
        </div>
    </div>

    <!-- Network Level 0-19 Distribution Table & Top Sponsors -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-layer-group text-blue-600"></i> Members by Network Level (Level 0 &ndash; 19)
                </h2>
                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                    Placement Tree Depth
                </span>
            </div>
            <div class="overflow-x-auto max-h-96">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50/90 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200/70 text-[10px] sticky top-0">
                        <tr>
                            <th class="px-5 py-3">Level</th>
                            <th class="px-5 py-3">Tier Rule</th>
                            <th class="px-5 py-3 text-center">Member Count</th>
                            <th class="px-5 py-3 text-center">Active</th>
                            <th class="px-5 py-3">Share (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach($levelsDistribution as $lvl)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-2.5">
                                <span class="inline-flex px-2 py-0.5 rounded font-bold text-[11px] {{ $lvl['level'] <= 7 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                    Level {{ $lvl['level'] }}
                                </span>
                                @if($lvl['level'] == 0)
                                    <span class="text-slate-400 text-[10px] ml-1">(Roots)</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-slate-500 text-[11px]">{{ $lvl['tier'] }}</td>
                            <td class="px-5 py-2.5 text-center font-bold text-slate-900">{{ $lvl['count'] }}</td>
                            <td class="px-5 py-2.5 text-center font-semibold text-emerald-600">{{ $lvl['active_count'] }}</td>
                            <td class="px-5 py-2.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full {{ $lvl['level'] <= 7 ? 'bg-blue-600' : 'bg-slate-500' }}" style="width: {{ $lvl['percentage'] }}%;"></div>
                                    </div>
                                    <span class="text-[10px] text-slate-400 w-8">{{ $lvl['percentage'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lg:col-span-4 bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs flex flex-col">
            <h2 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i class="fas fa-user-friends text-emerald-600"></i> Top Referral Sponsors
            </h2>
            <div class="space-y-3 flex-1 overflow-y-auto max-h-80 pr-1">
                @forelse($topSponsors as $sponsor)
                <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-200/60">
                    <div>
                        <div class="font-bold text-xs text-slate-900">{{ $sponsor['name'] }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">ID: {{ $sponsor['customer_id'] }} &bull; Joined {{ $sponsor['joined_at'] }}</div>
                    </div>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ $sponsor['referrals_count'] }} refs
                    </span>
                </div>
                @empty
                <div class="text-center py-8 text-slate-400">
                    <i class="far fa-folder-open text-2xl mb-1 block"></i>
                    <p class="text-xs">No sponsor referral records found.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Filtered Members Directory Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-users text-blue-600"></i> Network Members Directory
            </h2>
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="date_preset" value="{{ $range['preset'] }}">
                <input type="hidden" name="date_from" value="{{ $range['date_from'] }}">
                <input type="hidden" name="date_to" value="{{ $range['date_to'] }}">
                
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="text-xs bg-slate-50 border border-slate-300 text-slate-800 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 focus:outline-none placeholder-slate-400" placeholder="Search ID / Name...">
                <select name="status" class="text-xs bg-slate-50 border border-slate-300 text-slate-800 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">All Status</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="blocked" {{ ($filters['status'] ?? '') === 'blocked' ? 'selected' : '' }}>Blocked</option>
                </select>
                <select name="placement_position" class="text-xs bg-slate-50 border border-slate-300 text-slate-800 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">All Positions</option>
                    <option value="left" {{ ($filters['placement_position'] ?? '') === 'left' ? 'selected' : '' }}>Left</option>
                    <option value="middle" {{ ($filters['placement_position'] ?? '') === 'middle' ? 'selected' : '' }}>Middle</option>
                    <option value="right" {{ ($filters['placement_position'] ?? '') === 'right' ? 'selected' : '' }}>Right</option>
                </select>
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                    Filter
                </button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200/70 text-[10px]">
                    <tr>
                        <th class="px-5 py-3">Member Code</th>
                        <th class="px-5 py-3">Member Name</th>
                        <th class="px-5 py-3">Sponsor</th>
                        <th class="px-5 py-3">Placement Parent</th>
                        <th class="px-5 py-3">Position</th>
                        <th class="px-5 py-3">Placement Level</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">KYC Status</th>
                        <th class="px-5 py-3">Joined Date</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($members as $m)
                    @php
                        $lvl = $memberLevelsMap[$m->id] ?? 0;
                    @endphp
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-3 font-mono font-bold text-blue-600">{{ $m->customer_id ?? 'ID#'.$m->id }}</td>
                        <td class="px-5 py-3">
                            <div class="font-bold text-slate-900">{{ $m->user?->name ?? 'N/A' }}</div>
                            <div class="text-slate-400 text-[11px]">{{ $m->user?->email ?? $m->user?->phone }}</div>
                        </td>
                        <td class="px-5 py-3">
                            @if($m->sponsor)
                                <div class="font-semibold text-slate-800">{{ $m->sponsor->user?->name }}</div>
                                <div class="text-slate-400 text-[10px] font-mono">ID: {{ $m->sponsor->customer_id }}</div>
                            @else
                                <span class="text-slate-400 text-[11px]">None (Top Root)</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            @if($m->placementParent)
                                <div class="font-semibold text-slate-800">{{ $m->placementParent->user?->name }}</div>
                                <div class="text-slate-400 text-[10px] font-mono">ID: {{ $m->placementParent->customer_id }}</div>
                            @else
                                <span class="text-slate-400 text-[11px]">None (Tree Root)</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            @if($m->placement_position)
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200">{{ $m->placement_position }}</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-medium bg-slate-50 text-slate-400 border border-slate-200">Root</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded font-bold text-[10px] {{ $lvl <= 7 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                Level {{ $lvl }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $m->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($m->status === 'blocked' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                {{ ucfirst($m->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $m->kyc_status === 'approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($m->kyc_status === 'rejected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                {{ ucfirst(str_replace('_', ' ', $m->kyc_status ?? 'pending')) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-400 text-[11px]">{{ $m->joined_at?->format('M d, Y') ?? '-' }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ admin_route('mlm.members.show', $m) }}" class="inline-flex items-center px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-5 py-8 text-center text-slate-400">No members found matching the criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($members->hasPages())
        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
            {{ $members->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
