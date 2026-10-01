@extends('admin.layouts.app')

@section('title', 'Genealogy View')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Genealogy & Network Hierarchy (Levels 0–19)</h1>
            <p class="text-xs text-slate-500 mt-0.5">Multi-tier network visualization, upline-downline relationships, and level distribution explorer</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ admin_route('mlm.sponsor-network') }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-lg text-xs font-semibold transition">
                <i class="fas fa-user-friends text-xs"></i> Sponsor Network
            </a>
            <a href="{{ admin_route('mlm.tree.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                <i class="fas fa-sitemap text-xs"></i> 1:3 Placement Matrix
            </a>
        </div>
    </div>

    <!-- Root Selector & Search Bar -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-4 shadow-xs">
        <form method="GET" action="{{ admin_route('mlm.genealogy') }}" class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 text-xs font-bold text-slate-600">
                    <i class="fas fa-sitemap text-blue-500"></i>
                    <span>Select Root Member:</span>
                </div>
                <div>
                    <select name="root" class="text-xs bg-slate-50 border border-slate-300 text-slate-800 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none max-w-xs sm:max-w-md" onchange="this.form.submit()">
                        @foreach($allMembers as $m)
                            <option value="{{ $m->id }}" {{ ($root && $root->id === $m->id) ? 'selected' : '' }}>
                                {{ $m->customer_id ?? 'ID#'.$m->id }} &mdash; {{ $m->user?->name ?? 'Unnamed' }} ({{ ucfirst($m->status) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <a href="{{ admin_route('mlm.genealogy') }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
                        <i class="fas fa-rotate-left text-xs"></i> Reset to Top Root
                    </a>
                </div>
            </div>
            @if($root)
            <div class="hidden lg:flex items-center gap-2">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-50 text-slate-700 rounded-lg text-xs font-medium border border-slate-200">
                    Viewing Root: <strong class="text-blue-600">{{ $root->user?->name }}</strong> ({{ $root->customer_id }})
                </span>
            </div>
            @endif
        </form>
    </div>

    @if($root && $genealogyData)
    <!-- Genealogy Overview Stats (3 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Root Member</div>
            <div class="flex items-center gap-3 mt-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-orange-600 to-amber-600 text-white font-black flex items-center justify-center text-sm shadow-xs">
                    {{ strtoupper(substr($root->user?->name ?? 'M', 0, 1)) }}
                </div>
                <div>
                    <div class="font-bold text-sm text-slate-900">{{ $root->user?->name }}</div>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="px-1.5 py-0.2 rounded bg-blue-50 text-blue-700 text-[10px] font-mono font-bold border border-blue-200">{{ $root->customer_id }}</span>
                        <span class="inline-flex px-1.5 py-0.2 rounded-full text-[9px] font-bold uppercase {{ $root->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">{{ ucfirst($root->status) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Network Depth Detected</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-2">
                {{ $genealogyData['total_levels_found'] }} Levels
                <span class="text-xs font-normal text-slate-400 font-sans">(Levels 0&ndash;{{ $genealogyData['total_levels_found'] - 1 }})</span>
            </div>
            <div class="text-xs text-slate-400 mt-1">Platform supports 20-level (0–19) PV distribution</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Downline Nodes</div>
            <div class="text-2xl font-extrabold text-emerald-600 mt-2">
                {{ $genealogyData['total_nodes'] }} Members
            </div>
            <div class="text-xs text-slate-400 mt-1">Downline subtree under current root</div>
        </div>
    </div>

    <!-- Level-by-Level Accordion Explorer -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-layer-group text-blue-600"></i> Level-by-Level Network Members (Levels 0–19)
            </h2>
            <div class="flex items-center gap-2">
                <button type="button" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition" onclick="toggleAllGenealogy(true)">Expand All</button>
                <button type="button" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition" onclick="toggleAllGenealogy(false)">Collapse All</button>
            </div>
        </div>
        <div class="p-4 space-y-3">
            @foreach($genealogyData['levels'] as $depth => $levelGroup)
            <div class="border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                <button type="button" onclick="toggleGenealogyLevel('level-content-{{ $depth }}', 'level-icon-{{ $depth }}')" class="w-full flex items-center justify-between p-4 bg-slate-50 hover:bg-slate-100/80 text-left transition">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex px-2.5 py-1 rounded-md font-bold text-xs {{ $depth <= 7 ? 'bg-blue-600 text-white' : 'bg-slate-700 text-white' }}">
                            Level {{ $depth }}
                        </span>
                        <span class="font-bold text-xs text-slate-900">{{ $depth === 0 ? 'Self / Root Member' : 'Placement Level ' . $depth }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-medium bg-white text-slate-600 border border-slate-200">{{ $levelGroup['tier'] }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-200 text-slate-800">{{ $levelGroup['count'] }} member(s)</span>
                        <i id="level-icon-{{ $depth }}" class="fas fa-chevron-down text-xs text-slate-400 transition-transform {{ $depth <= 2 ? 'rotate-180' : '' }}"></i>
                    </div>
                </button>
                <div id="level-content-{{ $depth }}" class="{{ $depth <= 2 ? '' : 'hidden' }} border-t border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700">
                            <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200/70 text-[10px]">
                                <tr>
                                    <th class="px-5 py-3">Member ID</th>
                                    <th class="px-5 py-3">Distributor Name</th>
                                    <th class="px-5 py-3">Position</th>
                                    <th class="px-5 py-3">Sponsor</th>
                                    <th class="px-5 py-3">Placement Parent</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3">KYC</th>
                                    <th class="px-5 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                @foreach($levelGroup['members'] as $node)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="px-5 py-3 font-mono font-bold text-blue-600">{{ $node['customer_id'] ?? 'ID#'.$node['id'] }}</td>
                                    <td class="px-5 py-3">
                                        <div class="font-bold text-slate-900">{{ $node['name'] }}</div>
                                        <div class="text-slate-400 text-[11px]">{{ $node['email'] ?? $node['phone'] }}</div>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($node['placement_position'] !== 'root')
                                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200">{{ $node['placement_position'] }}</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-medium bg-slate-50 text-slate-400 border border-slate-200">Root</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="font-semibold text-slate-800">{{ $node['sponsor_name'] }}</div>
                                        <div class="text-slate-400 text-[10px] font-mono">ID: {{ $node['sponsor_id'] }}</div>
                                    </td>
                                    <td class="px-5 py-3 text-slate-600">{{ $node['placement_parent_name'] }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $node['status'] === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                            {{ ucfirst($node['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $node['kyc_status'] === 'approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                            {{ ucfirst($node['kyc_status'] ?? 'pending') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ admin_route('mlm.genealogy', ['root' => $node['id']]) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold border border-blue-200 transition" title="Make Genealogy Root">
                                                <i class="fas fa-sitemap text-[10px]"></i> Subtree
                                            </a>
                                            <a href="{{ $node['details_url'] }}" class="inline-flex items-center px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition" title="View Details">
                                                <i class="fas fa-eye text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl border border-slate-200/80 p-12 text-center text-slate-400">
        <i class="fas fa-project-diagram text-3xl mb-2 text-slate-300 block"></i>
        <h3 class="text-sm font-bold text-slate-700">No Member Selected</h3>
        <p class="text-xs text-slate-400 mt-1">Please select a member above to inspect the multi-tier Level 0–19 genealogy hierarchy.</p>
    </div>
    @endif
</div>

@push('scripts')
<script>
function toggleGenealogyLevel(contentId, iconId) {
    const el = document.getElementById(contentId);
    const icon = document.getElementById(iconId);
    if (el) {
        el.classList.toggle('hidden');
        if (icon) {
            icon.classList.toggle('rotate-180');
        }
    }
}

function toggleAllGenealogy(expand) {
    const contents = document.querySelectorAll('[id^="level-content-"]');
    const icons = document.querySelectorAll('[id^="level-icon-"]');
    contents.forEach(c => {
        if (expand) {
            c.classList.remove('hidden');
        } else {
            c.classList.add('hidden');
        }
    });
    icons.forEach(i => {
        if (expand) {
            i.classList.add('rotate-180');
        } else {
            i.classList.remove('rotate-180');
        }
    });
}
</script>
@endpush
@endsection
