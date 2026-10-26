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
            <a href="{{ admin_route('mlm.manual') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200/80 rounded-lg hover:bg-rose-100 shadow-xs transition" title="Open complete step-by-step PDF manual in browser">
                <i class="fas fa-file-pdf text-rose-600"></i> Admin Manual (PDF)
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
                <div class="relative" id="memberSearchWrapper">
                    <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                    <input type="text" name="search" id="memberSearchInput" class="w-full pl-8 pr-8 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" value="{{ request('search') }}" placeholder="Member Code, Name, Mobile, Email" autocomplete="off">
                    <button type="button" id="memberSearchClearBtn" class="{{ request('search') ? '' : 'hidden' }} absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs" title="Clear search">
                        <i class="fas fa-times-circle"></i>
                    </button>
                    <!-- Auto-suggest Suggestions Dropdown -->
                    <div id="memberSearchDropdown" class="hidden absolute left-0 right-0 top-full mt-1.5 bg-white rounded-xl shadow-2xl border border-slate-200/90 max-h-80 overflow-y-auto z-50 text-xs divide-y divide-slate-100"></div>
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
                                    @if((auth()->user()->hasPermission('mlm.manage') || auth()->user()->hasPermission('mlm.move')) && $member->placement_parent_id !== null)
                                        <a href="{{ admin_route('mlm.tree.move.form', $member) }}" class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 hover:bg-amber-50 hover:border-amber-300 hover:text-amber-600 text-slate-600 flex items-center justify-center transition" title="Move Placement Subtree / Relocate Position">
                                            <i class="fas fa-arrows-up-down-left-right text-xs"></i>
                                        </a>
                                    @endif
                                    @if(auth()->user()->isSuperAdmin())
                                        <button type="button" 
                                                onclick="confirmDeleteMember({{ $member->id }}, '{{ addslashes($member->user?->name ?? 'Member') }}', '{{ $member->customer_id ?? 'ID#'.$member->id }}', {{ $member->placement_parent_id === null ? 'true' : 'false' }}, '{{ admin_route('mlm.members.destroy', $member) }}', '{{ addslashes($member->user?->email ?? '') }}')"
                                                class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 hover:bg-rose-50 hover:border-rose-300 hover:text-rose-600 text-slate-400 flex items-center justify-center transition" 
                                                title="{{ $member->placement_parent_id === null ? 'Delete Root Leader (Super Admin Only)' : 'Delete Member (Super Admin Only)' }}">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
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

{{-- Delete Member Confirmation Modal (Super Admin Exclusive) --}}
@if(auth()->user()->isSuperAdmin())
<div id="deleteMemberModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        <div class="p-6">
            <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl mb-4">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            
            <h3 class="text-base font-bold text-slate-900 tracking-tight" id="deleteModalTitle">Delete MLM Member</h3>
            
            <div id="rootWarningBanner" class="hidden mt-3 p-3.5 rounded-xl bg-amber-50 border border-amber-200/90 text-xs text-amber-950">
                <div class="font-bold flex items-center gap-1.5 text-amber-900">
                    <i class="fas fa-crown text-amber-600"></i> Root Leader Deletion
                </div>
                <p class="mt-1 leading-relaxed text-amber-800">
                    This member is the <strong>Matrix Root Leader</strong>. Deleting them will safely re-anchor the network: their primary placement child will be promoted to the new Root Leader and all downline sponsor paths will be preserved.
                </p>
            </div>

            <p class="text-xs text-slate-600 mt-3 leading-relaxed" id="deleteModalMessage">
                Are you sure you want to delete this member? All associated ledger lines, KYC records, and placement movements will be safely removed.
            </p>

            <form id="deleteMemberForm" method="POST" action="" class="mt-4 space-y-3">
                @csrf
                @method('DELETE')

                <label class="flex items-start gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer text-xs text-slate-700 hover:bg-slate-100/70 transition">
                    <input type="checkbox" name="delete_user" value="1" class="mt-0.5 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                    <div>
                        <div class="font-semibold text-slate-900">Also delete User account</div>
                        <div class="text-[11px] text-slate-500" id="deleteUserSubtitle">Permanently delete login credentials if no store orders exist.</div>
                    </div>
                </label>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-trash-alt text-xs"></i> Confirm & Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmDeleteMember(memberId, name, code, isRoot, actionUrl, email) {
        const modal = document.getElementById('deleteMemberModal');
        const form = document.getElementById('deleteMemberForm');
        const title = document.getElementById('deleteModalTitle');
        const msg = document.getElementById('deleteModalMessage');
        const rootBanner = document.getElementById('rootWarningBanner');
        const userSubtitle = document.getElementById('deleteUserSubtitle');

        form.action = actionUrl;

        if (isRoot) {
            title.innerHTML = `Delete Root Leader: <span class="text-rose-600 font-bold">${name}</span> (${code})`;
            rootBanner.classList.remove('hidden');
        } else {
            title.innerHTML = `Delete Member: <span class="text-rose-600 font-bold">${name}</span> (${code})`;
            rootBanner.classList.add('hidden');
        }

        msg.textContent = `Are you sure you want to permanently delete ${name} (${code})? This action cannot be reversed.`;
        if (email) {
            userSubtitle.textContent = `Permanently delete ${email} login if no ecommerce orders exist.`;
        }

        modal.classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteMemberModal').classList.add('hidden');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });
</script>
@endif

<script>
    // Live Member Search Auto-complete Suggestions
    (() => {
        const input = document.getElementById('memberSearchInput');
        const clearBtn = document.getElementById('memberSearchClearBtn');
        const dropdown = document.getElementById('memberSearchDropdown');
        const form = input?.closest('form');
        const searchUrl = @json(admin_route('search'));
        let debounceTimer = null;
        let highlightedIdx = -1;
        let currentSuggestions = [];

        if (!input || !dropdown) return;

        const escapeHtml = (val) => String(val ?? '').replace(/[&<>'"]/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
        })[c]);

        const highlightMatch = (text, term) => {
            if (!text || !term) return escapeHtml(text);
            const str = String(text);
            const escaped = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp(`(${escaped})`, 'gi');
            return escapeHtml(str).replace(regex, '<span class="bg-amber-100 text-amber-900 font-bold px-0.5 rounded">$1</span>');
        };

        const renderSuggestions = (list, query) => {
            currentSuggestions = list;
            highlightedIdx = -1;

            if (!list.length) {
                dropdown.innerHTML = `
                    <div class="p-3.5 text-slate-500 text-center text-xs">
                        No members found matching "<strong>${escapeHtml(query)}</strong>".
                        <div class="text-[11px] text-slate-400 mt-1">Press Enter or Filter to search all records.</div>
                    </div>`;
                dropdown.classList.remove('hidden');
                return;
            }

            let html = `
                <div class="px-3 py-1.5 bg-slate-50 border-b border-slate-100 text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center justify-between">
                    <span><i class="fas fa-users text-blue-500 mr-1"></i> Matching Members (${list.length})</span>
                    <span class="text-slate-400 font-normal lowercase">click to filter table</span>
                </div>
                <div class="divide-y divide-slate-50 max-h-72 overflow-y-auto">
            `;

            list.forEach((m, idx) => {
                const initial = (m.name || 'M').charAt(0).toUpperCase();
                const statusColor = (m.status === 'active') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                    (m.status === 'blocked') ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                    'bg-amber-50 text-amber-700 border-amber-200';

                html += `
                    <div class="member-suggestion-item p-2.5 hover:bg-blue-50/70 transition flex items-center justify-between gap-2.5 cursor-pointer" data-idx="${idx}">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center shrink-0 border border-blue-200">
                                ${escapeHtml(initial)}
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900 truncate text-xs flex items-center gap-1.5">
                                    <span>${highlightMatch(m.name, query)}</span>
                                    <span class="font-mono text-[11px] text-blue-600 font-semibold px-1.5 py-0.2 rounded bg-blue-50 border border-blue-100">
                                        ${highlightMatch(m.customer_id, query)}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500 truncate mt-0.5 flex items-center gap-2">
                                    ${m.phone ? `<span><i class="fas fa-phone-alt text-[9px] text-slate-400 mr-1"></i>${highlightMatch(m.phone, query)}</span>` : ''}
                                    ${m.sponsor_name && m.sponsor_name !== 'None' ? `<span>&bull; Sponsor: ${escapeHtml(m.sponsor_name)}</span>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border ${statusColor}">
                                ${escapeHtml(m.status)}
                            </span>
                            <a href="${m.url}" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white rounded transition shadow-2xs" title="View Profile ↗" onclick="event.stopPropagation()">
                                <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                            </a>
                        </div>
                    </div>
                `;
            });

            html += `</div>`;
            dropdown.innerHTML = html;
            dropdown.classList.remove('hidden');

            dropdown.querySelectorAll('.member-suggestion-item').forEach(item => {
                item.addEventListener('click', () => {
                    const idx = parseInt(item.getAttribute('data-idx'), 10);
                    selectSuggestion(currentSuggestions[idx]);
                });
            });
        };

        const selectSuggestion = (member) => {
            if (!member) return;
            input.value = member.customer_id || member.name;
            dropdown.classList.add('hidden');
            if (form) form.submit();
        };

        const fetchSuggestions = async () => {
            const q = input.value.trim();
            if (!q) {
                dropdown.classList.add('hidden');
                clearBtn?.classList.add('hidden');
                return;
            }

            clearBtn?.classList.remove('hidden');

            dropdown.innerHTML = `
                <div class="p-3 text-slate-500 text-center flex items-center justify-center gap-2 text-xs">
                    <i class="fas fa-spinner fa-spin text-blue-600"></i> Finding members...
                </div>`;
            dropdown.classList.remove('hidden');

            try {
                const response = await fetch(`${searchUrl}?type=members&q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('Search failed');
                const data = await response.json();
                const members = data.results?.members || [];
                renderSuggestions(members, q);
            } catch (err) {
                dropdown.innerHTML = `<div class="p-3 text-rose-500 text-center text-xs">Unable to load suggestions. Press Filter to search.</div>`;
            }
        };

        input.addEventListener('input', () => {
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchSuggestions, 200);
        });

        input.addEventListener('focus', () => {
            if (input.value.trim().length > 0) {
                fetchSuggestions();
            }
        });

        clearBtn?.addEventListener('click', () => {
            input.value = '';
            dropdown.classList.add('hidden');
            clearBtn.classList.add('hidden');
            input.focus();
        });

        input.addEventListener('keydown', (e) => {
            if (dropdown.classList.contains('hidden') || !currentSuggestions.length) return;

            const items = dropdown.querySelectorAll('.member-suggestion-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                highlightedIdx = (highlightedIdx + 1) % items.length;
                updateHighlight(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                highlightedIdx = (highlightedIdx - 1 + items.length) % items.length;
                updateHighlight(items);
            } else if (e.key === 'Enter') {
                if (highlightedIdx >= 0 && currentSuggestions[highlightedIdx]) {
                    e.preventDefault();
                    selectSuggestion(currentSuggestions[highlightedIdx]);
                }
            } else if (e.key === 'Escape') {
                dropdown.classList.add('hidden');
            }
        });

        const updateHighlight = (items) => {
            items.forEach((item, i) => {
                if (i === highlightedIdx) {
                    item.classList.add('bg-blue-100/80');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('bg-blue-100/80');
                }
            });
        };

        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target) && e.target !== input) {
                dropdown.classList.add('hidden');
            }
        });
    })();
</script>
@endsection
