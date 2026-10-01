@extends('admin.layouts.app')

@section('title', 'Activity & Audit Logs')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-clock-rotate-left text-blue-600"></i> Platform Audit & Activity Logs
            </h1>
            <p class="text-sm text-slate-500 mt-1">Immutable audit trail of admin actions, system events, and data changes.</p>
        </div>
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-200">
                <i class="fas fa-shield-halved"></i> Audit Security Active
            </span>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
        <form method="GET" action="{{ admin_route('activity-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 text-xs">
            <div class="lg:col-span-2">
                <label class="block font-semibold text-slate-600 mb-1">Search Keywords</label>
                <input type="text" name="search" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" placeholder="Search description, IP address..." value="{{ request('search') }}">
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Admin User</label>
                <select name="user_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Module</label>
                <select name="module" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition">
                    <option value="">All Modules</option>
                    @foreach($modules as $mod)
                        <option value="{{ $mod }}" @selected(request('module') === $mod)>{{ ucfirst($mod) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Date Range</label>
                <div class="flex gap-1">
                    <input type="date" name="date_from" class="w-1/2 px-2 py-2 bg-slate-50 border border-slate-200 rounded-lg text-[11px]" value="{{ request('date_from') }}">
                    <input type="date" name="date_to" class="w-1/2 px-2 py-2 bg-slate-50 border border-slate-200 rounded-lg text-[11px]" value="{{ request('date_to') }}">
                </div>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-3 py-2 font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition shadow-xs">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ admin_route('activity-logs.index') }}" class="px-3 py-2 font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Activity Log Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Recorded Audit Entries</h2>
            <span class="text-xs text-slate-500">{{ $logs->total() }} Total Log Entries</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">Admin / User</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Module</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 font-mono text-slate-500 text-[11px] whitespace-nowrap">
                                {{ $log->created_at?->format('M d, Y h:i:s A') }}
                            </td>
                            <td class="py-3 px-4">
                                @if($log->user)
                                    <div class="font-bold text-slate-900">{{ $log->user->name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-slate-400">System / Automatic</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 uppercase">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ ucfirst($log->module) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 max-w-xs truncate text-slate-800" title="{{ $log->description }}">
                                {{ $log->description ?? '-' }}
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-500">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                No activity logs recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
