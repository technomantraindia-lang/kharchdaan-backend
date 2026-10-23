@extends('admin.layouts.app')

@section('title', 'Placement Tree (1:3 Matrix Hierarchy)')

@push('styles')
<style>
    /* Viewport & Canvas */
    .tree-viewport {
        position: relative;
        overflow: auto;
        min-height: 580px;
        max-height: calc(100vh - 230px);
        background-color: #f8fafc;
        background-image: radial-gradient(#cbd5e1 1.2px, transparent 1.2px);
        background-size: 20px 20px;
        cursor: grab;
        user-select: none;
        border-radius: 0 0 0.75rem 0.75rem;
    }
    .tree-viewport:active {
        cursor: grabbing;
    }
    .tree-pan-container {
        display: inline-flex;
        justify-content: center;
        min-width: 100%;
        padding: 2rem 2rem 5rem 2rem;
        transform-origin: 50% 30px;
        transition: transform 0.15s ease-out;
    }

    /* Org Chart Hierarchy Branching */
    .org-tree-branch {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
    }

    /* Node Wrapper */
    .org-node-wrapper {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        z-index: 5;
    }

    /* Downward stem from parent node when children are visible */
    .org-tree-branch.has-expanded-children > .org-node-wrapper::after {
        content: '';
        position: absolute;
        bottom: -22px;
        left: 50%;
        transform: translateX(-50%);
        width: 2px;
        height: 22px;
        background-color: #94a3b8;
        z-index: 1;
    }

    /* Children container */
    .org-children-wrapper {
        display: flex;
        justify-content: center;
        position: relative;
        padding-top: 22px;
        margin-top: 0;
        transition: all 0.25s ease;
    }

    .org-children-wrapper.hidden {
        display: none !important;
    }

    /* Horizontal branch bar spanning children */
    .org-child-slot {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        padding: 0 12px;
        transition: padding 0.2s ease;
    }

    /* Vertical line dropping from horizontal bar into child */
    .org-child-slot::before {
        content: '';
        position: absolute;
        top: -22px;
        left: 50%;
        transform: translateX(-50%);
        width: 2px;
        height: 22px;
        background-color: #94a3b8;
        z-index: 1;
    }

    /* Horizontal connector bar */
    .org-child-slot::after {
        content: '';
        position: absolute;
        top: -22px;
        width: 100%;
        height: 2px;
        background-color: #94a3b8;
        z-index: 1;
    }

    .org-child-slot:first-child::after {
        left: 50%;
        width: 50%;
        border-top-left-radius: 4px;
    }

    .org-child-slot:last-child::after {
        right: 50%;
        left: auto;
        width: 50%;
        border-top-right-radius: 4px;
    }

    .org-child-slot:only-child::after {
        display: none;
    }

    /* ========================================================
       DENSITY MODES: DETAILED, COMPACT & CIRCLE / BUBBLE NODES
       ======================================================== */

    /* 1. Base / Detailed Mode */
    .org-node-card {
        width: 235px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease;
        position: relative;
        text-align: left;
        cursor: pointer;
    }

    .org-node-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(234, 88, 12, 0.15), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
        border-color: #cbd5e1;
    }

    .org-node-card.tree-highlight {
        outline: 3px solid #ea580c;
        border-color: #ea580c;
        box-shadow: 0 0 0 6px rgba(234, 88, 12, 0.25), 0 10px 20px rgba(234, 88, 12, 0.15);
        animation: pulseHighlight 2s infinite;
    }

    @keyframes pulseHighlight {
        0%, 100% { box-shadow: 0 0 0 6px rgba(234, 88, 12, 0.25); }
        50% { box-shadow: 0 0 0 12px rgba(234, 88, 12, 0.05); }
    }

    /* Position Top Border Accents */
    .org-node-card.pos-root { border-top: 4px solid #ea580c; }
    .org-node-card.pos-left { border-top: 4px solid #0284c7; }
    .org-node-card.pos-middle { border-top: 4px solid #d97706; }
    .org-node-card.pos-right { border-top: 4px solid #059669; }
    .org-node-card.pos-company-root { border-top: 4px solid #f59e0b; background: linear-gradient(180deg, #fffbeb 0%, #ffffff 50px); }
    .org-node-card.pos-company-node { border-top: 4px solid #6366f1; background: linear-gradient(180deg, #f5f3ff 0%, #ffffff 50px); }
    .org-node-card.pos-promoted-node {
        border-top: 4px solid #10b981;
        border-color: #10b981;
        background: linear-gradient(180deg, #ecfdf5 0%, #ffffff 70px);
        box-shadow: 0 4px 14px -2px rgba(16, 185, 129, 0.25);
    }
    .org-node-card.pos-idle-slot {
        border-top: 4px solid #f59e0b;
        background: linear-gradient(180deg, #fffbeb 0%, #ffffff 60px);
        border: 1.5px dashed #f59e0b;
    }
    .org-node-card.pos-expired-node {
        border-top: 4px solid #f43f5e;
        border-color: #fca5a5;
        background: linear-gradient(180deg, #fff1f2 0%, #ffffff 60px);
    }

    /* Expand / Collapse Button */
    .org-expand-btn {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #ffffff;
        border: 2px solid #ea580c;
        color: #ea580c;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s ease;
        position: absolute;
        bottom: -11px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.12);
    }
    .org-expand-btn:hover {
        background: #ea580c;
        color: #ffffff;
        transform: translateX(-50%) scale(1.15);
    }
    .org-expand-btn.is-expanded {
        background: #f1f5f9;
        border-color: #64748b;
        color: #475569;
    }
    .org-expand-btn.is-expanded:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    /* Empty Slot Card */
    .org-slot-empty {
        width: 190px;
        min-height: 90px;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        background: #f8fafc;
        color: #64748b;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 0.5rem;
        transition: all 0.2s ease;
        position: relative;
        text-align: center;
    }
    .org-slot-empty:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }
    .org-slot-empty.slot-left { border-color: #bae6fd; background: #f0f9ff; }
    .org-slot-empty.slot-middle { border-color: #fde68a; background: #fffbeb; }
    .org-slot-empty.slot-right { border-color: #a7f3d0; background: #ecfdf5; }

    .circle-node-meta { display: none; }
    .circle-slot-icon { display: none; }

    /* 2. Compact Mode */
    .density-compact .org-node-card {
        width: 145px;
        padding: 0.6rem 0.5rem !important;
    }
    .density-compact .org-child-slot {
        padding: 0 8px;
    }
    .density-compact .card-full-meta,
    .density-compact .card-full-actions {
        display: none !important;
    }
    .density-compact .card-compact-meta {
        display: block !important;
    }
    .density-compact .card-avatar {
        width: 24px !important;
        height: 24px !important;
        font-size: 10px !important;
    }
    .density-compact .card-name {
        font-size: 11px !important;
    }
    .density-compact .org-slot-empty {
        width: 130px;
        min-height: 65px;
        padding: 0.4rem;
    }
    .density-compact .org-slot-empty span:last-child {
        display: none;
    }

    /* 3. Circle / Bubble Node Mode (Exact diagram style) */
    .density-circle .org-node-card {
        width: 56px;
        height: 56px;
        border-radius: 50% !important;
        padding: 0 !important;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid #cbd5e1;
        box-shadow: 0 4px 8px rgba(0,0,0,0.08);
    }
    .density-circle .org-child-slot {
        padding: 0 10px;
    }
    .density-circle .org-node-card.pos-root { border-color: #ea580c; background: #fff7ed; }
    .density-circle .org-node-card.pos-left { border-color: #0284c7; background: #f0f9ff; }
    .density-circle .org-node-card.pos-middle { border-color: #d97706; background: #fffbeb; }
    .density-circle .org-node-card.pos-right { border-color: #059669; background: #ecfdf5; }

    .density-circle .card-box-content {
        display: none !important;
    }
    .density-circle .circle-inner-avatar {
        display: flex !important;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 12px;
        color: #0f172a;
    }
    .density-circle .circle-node-meta {
        display: block !important;
        margin-top: 4px;
        text-align: center;
        max-width: 90px;
        line-height: 1.15;
    }
    .density-circle .org-slot-empty {
        width: 50px;
        height: 50px;
        min-height: unset;
        border-radius: 50%;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .density-circle .org-slot-empty .empty-box-content {
        display: none !important;
    }
    .density-circle .org-slot-empty .circle-slot-icon {
        display: block !important;
        font-size: 14px;
    }
</style>
@endpush

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-sitemap text-blue-600"></i> Placement Tree (1:3 Physical Matrix)
            </h1>
            <p class="text-sm text-slate-500 mt-1">Explore top-down physical 1:3 placement hierarchy across Levels 0 to 19.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('mlm.sponsor-network') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-user-friends text-blue-500"></i> Sponsor Network
            </a>
            <a href="{{ admin_route('mlm.genealogy') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-project-diagram text-indigo-500"></i> Genealogy (0–19)
            </a>
        </div>
    </div>

    <!-- Tree Controls & Search Toolbar -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-4 sm:p-5 shadow-xs">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">
            <div class="lg:col-span-5">
                <label for="rootMember" class="block text-xs font-semibold text-slate-700 mb-1.5 flex items-center justify-between">
                    <span>Root Member / Branch</span>
                    <span class="text-[10px] text-slate-400 font-normal">Select company node or distributor</span>
                </label>
                <select id="rootMember" class="w-full px-3 py-2 text-xs font-medium bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition">
                    @php
                        $activeId = $initialRoot['member']['id'] ?? $initialRoot['id'] ?? ($root->id ?? null);
                    @endphp
                    @if(!empty($leaderOptions['company']))
                        <optgroup label="Company Master Positions (Aaradhya Gruh Udyog)">
                            @foreach($leaderOptions['company'] as $comp)
                                <option value="{{ $comp['id'] }}" @selected($activeId == $comp['id'])>
                                    {{ $comp['label'] }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                    @if(!empty($leaderOptions['leaders']))
                        <optgroup label="Real Distributor Leaders & Downline Networks">
                            @foreach($leaderOptions['leaders'] as $ldr)
                                <option value="{{ $ldr['id'] }}" @selected($activeId == $ldr['id'])>
                                    {{ $ldr['label'] }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                    @if(empty($leaderOptions['company']) && empty($leaderOptions['leaders']))
                        @forelse($roots as $rootItem)
                            <option value="{{ $rootItem->id }}" @selected($activeId == $rootItem->id)>
                                {{ $rootItem->customer_id }} &mdash; {{ $rootItem->user?->name ?? 'Member' }}
                            </option>
                        @empty
                            <option value="">No root members available</option>
                        @endforelse
                    @endif
                </select>
            </div>

            <div class="lg:col-span-4 relative">
                <label for="treeMemberSearch" class="block text-xs font-semibold text-slate-700 mb-1.5">Search & Jump to Member</label>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                        <input id="treeMemberSearch" type="search" class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" placeholder="Customer ID, name, phone or email">
                    </div>
                    <button id="treeSearchButton" type="button" class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                        Find
                    </button>
                </div>
                <div id="treeSearchResults" class="tree-search-results hidden bg-white rounded-xl shadow-xl border border-slate-200 mt-1 max-h-60 overflow-y-auto z-40 text-xs absolute w-full left-0"></div>
            </div>

            <div class="lg:col-span-3 text-xs bg-blue-50/60 p-3 rounded-lg border border-blue-100">
                <div class="font-bold text-blue-950 flex items-center gap-1.5">
                    <i class="fas fa-info-circle text-blue-600"></i> Matrix Hierarchy
                </div>
                <div class="text-[11px] text-blue-800 mt-0.5">Physical 1:3 placement matrix down to 19 levels.</div>
            </div>
        </div>

        <!-- Quick Switch to Real Distributor Leaders & Key Branches -->
        @if(!empty($leaderOptions['quick_pills']))
        <div class="mt-4 pt-3.5 border-t border-slate-100 flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-700 flex items-center gap-1 mr-1">
                <i class="fas fa-bolt text-amber-500"></i> Quick Leader Network:
            </span>
            @php
                $topRoot = $roots->first() ?? $root;
                $topRootId = $topRoot?->id ?? 10;
                $topRootName = $topRoot?->user?->name ?? 'Nimesh Jagirdar';
                $topRootCid = $topRoot?->customer_id ?? 'C22020008';
            @endphp
            <button type="button" class="quick-leader-pill px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ ($activeId == $topRootId) ? 'bg-orange-600 text-white border-orange-600 shadow-xs' : 'bg-orange-50/80 text-orange-950 border-orange-200 hover:bg-orange-100' }} inline-flex items-center gap-1.5" data-member-id="{{ $topRootId }}">
                <svg class="w-3.5 h-3.5 fill-current text-amber-500" viewBox="0 0 24 24"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/></svg>
                <span>Top ID: {{ $topRootName }} ({{ $topRootCid }})</span>
            </button>
            @foreach($leaderOptions['quick_pills'] as $pill)
                <button type="button" class="quick-leader-pill px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ ($activeId == $pill['id']) ? 'bg-orange-600 text-white border-orange-600 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-orange-50 hover:border-orange-200 hover:text-orange-900' }}" data-member-id="{{ $pill['id'] }}" title="Phone: {{ $pill['phone'] }}">
                    <span class="font-bold">{{ $pill['name'] }}</span>
                    <span class="text-[10px] opacity-80 font-mono ml-0.5">({{ $pill['downlines'] }} DL)</span>
                </button>
            @endforeach
        </div>
        @endif

        <div class="mt-4 p-3 rounded-lg bg-blue-50/70 border border-blue-100 text-xs text-blue-900 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div><strong>Sponsor Tree and Placement Tree are separate.</strong> Sponsor information is shown on each card; branches below are placement relationships only.</div>
            @php
                $currentRootId = $initialRoot['member']['id'] ?? $initialRoot['id'] ?? null;
            @endphp
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ admin_route('mlm.manual') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold text-rose-800 bg-white border border-rose-200 rounded-md hover:bg-rose-50 transition flex-shrink-0 shadow-2xs" title="Open complete step-by-step PDF manual in browser">
                    <i class="fas fa-file-pdf text-rose-600 text-[11px]"></i> Admin Manual (PDF)
                </a>
                @if($currentRootId && Route::has('admin.mlm.tree.move.form'))
                    <a href="{{ admin_route('mlm.tree.move.form', $currentRootId) }}" class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-md hover:bg-amber-100 transition flex-shrink-0">
                        <i class="fas fa-arrows-up-down-left-right text-[10px]"></i> Move Member
                    </a>
                @else
                    <span class="text-slate-400 text-xs font-medium">Move Member</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Tree Notification Message -->
    <div id="treeMessage" class="hidden p-4 rounded-xl text-xs font-semibold"></div>

    <!-- Tree Canvas & Toolbar Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <!-- Breadcrumb & Subtree Focus Bar (Shown when drilled down into a downline) -->
        <div id="focusBreadcrumbsBar" class="hidden px-5 py-2.5 bg-blue-50 border-b border-blue-100 text-xs flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="font-bold text-blue-950 flex items-center gap-1"><i class="fas fa-crosshairs text-blue-600"></i> Focused Path:</span>
                <div id="focusBreadcrumbs" class="inline-flex items-center gap-1 flex-wrap font-medium text-slate-700"></div>
            </div>
            <button type="button" id="btnResetFocus" class="px-2.5 py-1 text-xs font-semibold bg-white text-blue-700 hover:bg-blue-100 border border-blue-200 rounded shadow-xs transition">
                <i class="fas fa-rotate-left text-[10px]"></i> Back to Top Root
            </button>
        </div>

        <!-- Interactive Toolbar: Density Modes, Zoom, Depth & Legend -->
        <div class="px-5 py-3 border-b border-slate-100 bg-slate-50/70 flex flex-wrap items-center justify-between gap-3">
            <!-- Left: View Density Switcher (Cards / Compact / Circles) -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-600 uppercase tracking-wider hidden sm:inline">View Mode:</span>
                <div class="inline-flex rounded-lg bg-slate-200/70 p-0.5" id="densityGroup">
                    <button type="button" class="px-2.5 py-1 text-xs font-bold rounded-md bg-white text-blue-600 shadow-xs transition density-btn" data-density="detailed" title="Detailed Cards with complete info">
                        <i class="fas fa-id-card text-[11px] mr-1"></i> Cards
                    </button>
                    <button type="button" class="px-2.5 py-1 text-xs font-medium rounded-md text-slate-600 hover:text-slate-900 transition density-btn" data-density="compact" title="Compact Cards for wider views">
                        <i class="fas fa-table-cells text-[11px] mr-1"></i> Compact
                    </button>
                    <button type="button" class="px-2.5 py-1 text-xs font-medium rounded-md text-slate-600 hover:text-slate-900 transition density-btn" data-density="circle" title="Circle Nodes for dense tree overview">
                        <i class="fas fa-circle-nodes text-[11px] mr-1"></i> Circles
                    </button>
                </div>

                <span class="h-4 w-px bg-slate-300 mx-1"></span>

                <!-- Quick Depth Expanders -->
                <button type="button" id="btnExpandLevel1" class="px-2.5 py-1 text-[11px] font-semibold text-slate-700 bg-white hover:bg-slate-100 rounded border border-slate-200 transition" title="Show Level 1">
                    L1
                </button>
                <button type="button" id="btnExpandLevel2" class="px-2.5 py-1 text-[11px] font-semibold text-slate-700 bg-white hover:bg-slate-100 rounded border border-slate-200 transition" title="Show Level 1 & 2">
                    L2
                </button>
                <button type="button" id="btnExpandLevel3" class="px-2.5 py-1 text-[11px] font-bold text-orange-700 bg-orange-50 hover:bg-orange-100 rounded border border-orange-300 transition" title="Show Levels 1 to 3">
                    <i class="fas fa-users text-[10px] mr-0.5"></i> L3
                </button>
                <button type="button" id="btnExpandAll" class="px-2.5 py-1 text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded border border-blue-200 transition">
                    <i class="fas fa-folder-open text-[10px]"></i> Expand All
                </button>
                <button type="button" id="btnCollapseAll" class="px-2.5 py-1 text-[11px] font-semibold text-slate-700 bg-white hover:bg-slate-100 rounded border border-slate-200 transition">
                    <i class="fas fa-folder text-[10px]"></i> Collapse
                </button>
            </div>

            <!-- Center: Legend -->
            <div class="hidden xl:flex items-center gap-2 text-[11px]">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 font-semibold"><span class="w-2 h-2 rounded-full bg-blue-600"></span> Root</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-sky-50 text-sky-700 border border-sky-200 font-semibold"><span class="w-2 h-2 rounded-full bg-sky-500"></span> Left</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 font-semibold"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Middle</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold"><span class="w-2 h-2 rounded-full bg-emerald-600"></span> Right</span>
            </div>

            <!-- Right: Zoom & Navigation Controls -->
            <div class="flex items-center gap-1.5">
                <button type="button" id="btnZoomOut" class="w-7 h-7 rounded bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center text-xs transition" title="Zoom Out">
                    <i class="fas fa-minus"></i>
                </button>
                <span id="zoomLevel" class="text-[11px] font-mono font-bold text-slate-600 px-1.5 min-w-[42px] text-center">100%</span>
                <button type="button" id="btnZoomIn" class="w-7 h-7 rounded bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center text-xs transition" title="Zoom In">
                    <i class="fas fa-plus"></i>
                </button>
                <button type="button" id="btnZoomFit" class="px-2 py-1 text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded border border-blue-200 transition" title="Auto Fit Entire Tree on Screen">
                    <i class="fas fa-compress text-[10px]"></i> Fit View
                </button>
                <button type="button" id="btnZoomReset" class="px-2 py-1 text-[11px] font-semibold text-slate-700 bg-white hover:bg-slate-100 rounded border border-slate-200 transition" title="Reset Zoom">
                    100%
                </button>
                <button type="button" id="btnCenterTree" class="w-7 h-7 rounded bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center text-xs transition" title="Center Tree">
                    <i class="fas fa-crosshairs"></i>
                </button>
            </div>
        </div>

        <!-- Scrollable & Grabbable Viewport -->
        <div id="treeViewport" class="tree-viewport density-detailed" data-children-url="{{ admin_route('mlm.tree.children', ['member' => '__MEMBER__']) }}" data-search-url="{{ admin_route('mlm.tree.search') }}">
            <div id="treePanContainer" class="tree-pan-container">
                @if(!$initialRoot)
                    <div class="text-center text-slate-400 py-16">
                        <i class="fas fa-sitemap text-4xl mb-3 text-slate-300 block"></i>
                        No root member is available for the placement tree.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Quick Slide-Over Member Detail Drawer -->
<div id="memberDrawerBackdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-[999] hidden transition-opacity"></div>
<div id="memberDrawer" class="fixed inset-y-0 right-0 h-screen max-h-screen w-full max-w-md bg-white shadow-2xl z-[1000] transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col border-l border-slate-200 overflow-hidden" style="height: 100vh; max-height: 100vh;">
    <!-- Drawer Header (Fixed at Top) -->
    <div class="flex-shrink-0 p-5 border-b border-orange-100 flex items-center justify-between bg-gradient-to-r from-orange-50 via-amber-50/40 to-white">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-orange-600 to-amber-500 text-white flex items-center justify-center shadow-xs text-sm flex-shrink-0">
                <i class="fas fa-user-circle"></i>
            </div>
            <div>
                <h3 class="font-black text-slate-900 text-sm tracking-tight leading-tight">Member Network Profile</h3>
                <p class="text-[10px] text-orange-700 font-semibold mt-0.5">1:3 Placement Matrix Hierarchy</p>
            </div>
        </div>
        <button type="button" id="btnCloseDrawer" class="w-8 h-8 rounded-xl bg-white hover:bg-orange-50 border border-slate-200 hover:border-orange-200 text-slate-400 hover:text-orange-600 flex items-center justify-center transition shadow-2xs flex-shrink-0" title="Close Profile">
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>

    <!-- Drawer Body (Scrollable with min-h-0 so footer NEVER gets pushed off) -->
    <div id="drawerBody" class="p-5 space-y-4 flex-1 min-h-0 overflow-y-auto text-xs">
        <!-- Dynamic Content injected here -->
    </div>

    <!-- Drawer Footer Actions (Fixed at Bottom with flex-shrink-0) -->
    <div id="drawerFooter" class="flex-shrink-0 p-4 border-t border-slate-100 bg-white flex items-center justify-between gap-3 shadow-[0_-4px_15px_-3px_rgba(0,0,0,0.06)]">
        <button type="button" id="btnDrawerFocusSubtree" class="flex-1 py-3 px-4 bg-gradient-to-r from-orange-600 via-orange-500 to-amber-500 hover:from-orange-700 hover:to-amber-600 text-white font-extrabold rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-md shadow-orange-500/20 active:scale-98">
            <i class="fas fa-crosshairs text-xs"></i>
            <span>Focus Subtree</span>
        </button>
        <a id="btnDrawerFullProfile" href="#" class="py-3 px-4 bg-white hover:bg-orange-50 text-slate-700 hover:text-orange-600 font-bold border border-slate-200 hover:border-orange-200 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-2xs">
            <i class="fas fa-external-link-alt text-[10px]"></i>
            <span>Full Profile</span>
        </a>
    </div>
</div>

<!-- Modal: Promote Member to Idle Position (Temporary / Flexible) -->
<div id="modalPromoteMember" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-[1050] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-lg w-full overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200">
        <!-- Header -->
        <div class="p-4 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-white flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-white/20 backdrop-blur-xs flex items-center justify-center text-amber-100 font-black text-base shadow-xs">
                    <i class="fas fa-crown"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-sm tracking-tight leading-tight">Promote Member to Idle Position</h3>
                    <p class="text-[11px] text-amber-100 font-medium">Temporary Dual-Placement & Double Income</p>
                </div>
            </div>
            <button type="button" id="btnClosePromoteModal" class="w-7 h-7 rounded-lg bg-black/10 hover:bg-black/20 text-white flex items-center justify-center text-xs transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Scrollable Form Body -->
        <div class="p-5 space-y-4 overflow-y-auto text-xs flex-1">
            <!-- Target Idle Slot Box / Dropdown -->
            <div id="promoteSlotSummary" class="p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-amber-950 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-200 text-amber-900 flex items-center justify-center font-bold text-xs">
                        <i class="fas fa-star text-amber-700"></i>
                    </div>
                    <div>
                        <div class="font-bold text-xs" id="promoteSlotLabel">Idle Slot</div>
                        <div class="text-[10px] text-amber-800 font-mono" id="promoteSlotCode">ID: C25070004</div>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-amber-200/80 text-amber-900 uppercase">Target Slot</span>
            </div>

            <!-- Alternative: Slot Picker (when opening from a regular member) -->
            <div id="promoteSlotPickerRow" class="hidden">
                <label class="block font-bold text-slate-800 mb-1" for="promoteSlotSelect">
                    Select Target Idle Position:
                </label>
                <select id="promoteSlotSelect" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 outline-none">
                    <!-- Populated dynamically -->
                </select>
            </div>

            <!-- Member Search / Selector -->
            <div id="promoteMemberSearchSection">
                <label class="block font-bold text-slate-800 mb-1 flex items-center justify-between">
                    <span>1. Select Existing Member to Promote:</span>
                    <span class="text-[10px] text-slate-400 font-normal">Search by Name, Code, Phone</span>
                </label>
                <div class="relative">
                    <div class="relative flex items-center">
                        <i class="fas fa-search absolute left-3 text-slate-400 text-xs pointer-events-none"></i>
                        <input type="text" id="promoteMemberSearchInput" class="w-full pl-9 pr-8 py-2 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 outline-none transition" placeholder="Type name, code (e.g. C22010003), or mobile...">
                        <button type="button" id="btnClearPromoteSearch" class="hidden absolute right-2.5 text-slate-400 hover:text-slate-600">
                            <i class="fas fa-times-circle text-xs"></i>
                        </button>
                    </div>

                    <!-- Autocomplete Results Dropdown -->
                    <div id="promoteSearchResultsDropdown" class="hidden absolute top-full left-0 right-0 mt-1 max-h-48 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-xl z-20 divide-y divide-slate-100">
                    </div>
                </div>

                <!-- Selected Member Card -->
                <div id="selectedPromoteMemberCard" class="hidden mt-2 p-3 rounded-xl bg-emerald-50/80 border border-emerald-300 text-emerald-950 flex items-center justify-between gap-2 shadow-2xs">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-full bg-emerald-600 text-white font-extrabold flex items-center justify-center text-xs flex-shrink-0" id="selectedMemberAvatar">
                            M
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-xs truncate text-slate-900" id="selectedMemberName">Member Name</div>
                            <div class="text-[10px] text-slate-600 font-mono flex items-center gap-1.5 flex-wrap">
                                <span id="selectedMemberCode" class="text-blue-700 font-bold">C25010004</span>
                                <span class="text-slate-300">&bull;</span>
                                <span id="selectedMemberPhone">9876543210</span>
                                <span class="text-slate-300">&bull;</span>
                                <span class="text-emerald-700 font-bold" id="selectedMemberLevel">Level 3</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="btnChangeSelectedMember" class="text-[10px] font-bold text-emerald-800 hover:text-emerald-950 underline px-1">
                        Change
                    </button>
                </div>
                <input type="hidden" id="promoteBeneficiaryId" value="">
                <input type="hidden" id="promoteTargetSlotId" value="">
            </div>

            <!-- Duration Selector -->
            <div>
                <label class="block font-bold text-slate-800 mb-1">
                    2. Promotion Duration ("Temporary time / As much time as you want"):
                </label>
                <div class="grid grid-cols-3 gap-1.5 text-center" id="durationChipsContainer">
                    <button type="button" class="duration-chip active px-2.5 py-1.5 rounded-lg border text-[11px] font-bold transition border-amber-500 bg-amber-50 text-amber-900" data-type="days" data-days="7">7 Days</button>
                    <button type="button" class="duration-chip px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-[11px] font-bold transition" data-type="days" data-days="15">15 Days</button>
                    <button type="button" class="duration-chip px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-[11px] font-bold transition" data-type="days" data-days="30">30 Days</button>
                    <button type="button" class="duration-chip px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-[11px] font-bold transition" data-type="days" data-days="90">90 Days</button>
                    <button type="button" class="duration-chip px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-[11px] font-bold transition" data-type="date">Custom Date</button>
                    <button type="button" class="duration-chip px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-[11px] font-bold transition" data-type="indefinite">Flexible / Indefinite</button>
                </div>

                <!-- Custom Date Input -->
                <div id="customDateRow" class="hidden mt-2">
                    <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Select Expiry Date & Time:</label>
                    <input type="datetime-local" id="customExpiryInput" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 outline-none">
                </div>
            </div>

            <!-- Promotion Note / Reason -->
            <div>
                <label class="block font-bold text-slate-800 mb-1" for="promoteNoteInput">
                    3. Promotion Reason / Note (Optional):
                </label>
                <input type="text" id="promoteNoteInput" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 outline-none" placeholder="e.g. Festival Performance Reward, Acting Team Leader..." value="Temporary Performance Promotion">
            </div>

            <!-- Guarantee Notice Banner -->
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 text-[10px] space-y-1">
                <div class="font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fas fa-check-circle text-emerald-600"></i>
                    <span>Dual-Placement & Double Income Rules:</span>
                </div>
                <p>&bull; <strong>Both Places Displayed:</strong> The member will appear at this promoted idle slot AND their original permanent tree position.</p>
                <p>&bull; <strong>Commission Routing:</strong> Level PV income calculated under this idle slot routes directly to this member during the active promotion.</p>
                <p>&bull; <strong>Admin Control:</strong> Super Admin can extend time or revert back to Idle at any moment.</p>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="p-4 border-t border-slate-100 bg-slate-50/70 flex items-center justify-end gap-2.5">
            <button type="button" id="btnCancelPromoteModal" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-200/70 transition">
                Cancel
            </button>
            <button type="button" id="btnSubmitPromotion" class="px-5 py-2.5 rounded-xl text-xs font-extrabold text-white bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:to-orange-600 transition shadow-md shadow-orange-500/20 active:scale-98 flex items-center gap-1.5">
                <i class="fas fa-crown text-xs"></i>
                <span>Confirm & Promote Member</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal: Manage / Extend Temporary Promotion -->
<div id="modalManagePromotion" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-[1050] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-md w-full overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-200">
        <!-- Header -->
        <div class="p-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center text-white text-sm">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-sm" id="manageModalTitle">Manage Promotion</h3>
                    <p class="text-[10px] text-emerald-100 font-medium" id="manageModalSubtitle">Active Dual-Placement Slot</p>
                </div>
            </div>
            <button type="button" id="btnCloseManageModal" class="w-7 h-7 rounded-lg bg-black/10 hover:bg-black/20 text-white flex items-center justify-center text-xs transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-5 space-y-4 overflow-y-auto text-xs flex-1">
            <div class="p-3 rounded-xl bg-emerald-50/80 border border-emerald-300 text-emerald-950">
                <div class="font-bold text-xs flex items-center gap-1.5" id="manageMemberName">Promoted Member</div>
                <div class="text-[10px] text-slate-600 font-mono mt-0.5" id="manageMemberCode">ID: C25010004</div>
                <div class="mt-2 text-[10px] font-semibold text-emerald-800" id="manageCurrentExpiry">Remaining: 22 Days</div>
            </div>

            <div>
                <label class="block font-bold text-slate-800 mb-1">Extend / Change Duration:</label>
                <div class="grid grid-cols-3 gap-1.5 text-center" id="manageDurationChipsContainer">
                    <button type="button" class="manage-chip active px-2.5 py-1.5 rounded-lg border text-[11px] font-bold border-emerald-500 bg-emerald-50 text-emerald-900" data-type="days" data-days="7">+7 Days</button>
                    <button type="button" class="manage-chip px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-[11px] font-bold" data-type="days" data-days="15">+15 Days</button>
                    <button type="button" class="manage-chip px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-[11px] font-bold" data-type="days" data-days="30">+30 Days</button>
                    <button type="button" class="manage-chip px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-[11px] font-bold" data-type="days" data-days="90">+90 Days</button>
                    <button type="button" class="manage-chip px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-[11px] font-bold" data-type="date">New Date</button>
                    <button type="button" class="manage-chip px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-[11px] font-bold" data-type="indefinite">Make Indefinite</button>
                </div>

                <div id="manageCustomDateRow" class="hidden mt-2">
                    <label class="block text-[10px] font-bold text-slate-600 mb-0.5">New Expiry Date:</label>
                    <input type="datetime-local" id="manageCustomExpiryInput" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-800 mb-1" for="manageNoteInput">Promotion Note:</label>
                <input type="text" id="manageNoteInput" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none">
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                <span class="text-[10px] text-slate-500">Want to end this promotion immediately?</span>
                <button type="button" id="btnEndPromotionNow" class="text-[10px] font-bold text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-2.5 py-1 rounded-lg border border-rose-200 transition flex items-center gap-1">
                    <i class="fas fa-undo text-[9px]"></i> End Promotion & Revert
                </button>
            </div>
        </div>

        <div class="p-4 border-t border-slate-100 bg-slate-50/70 flex items-center justify-end gap-2.5">
            <button type="button" id="btnCancelManageModal" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-200/70 transition">
                Close
            </button>
            <button type="button" id="btnSaveManagePromotion" class="px-5 py-2.5 rounded-xl text-xs font-extrabold text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-md shadow-emerald-600/20 active:scale-98">
                Update Promotion
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const viewport = document.getElementById('treeViewport');
    const panContainer = document.getElementById('treePanContainer');
    const rootSelect = document.getElementById('rootMember');
    const searchInput = document.getElementById('treeMemberSearch');
    const searchButton = document.getElementById('treeSearchButton');
    const searchResults = document.getElementById('treeSearchResults');
    const message = document.getElementById('treeMessage');
    const zoomLevelText = document.getElementById('zoomLevel');
    const focusBar = document.getElementById('focusBreadcrumbsBar');
    const focusBreadcrumbs = document.getElementById('focusBreadcrumbs');
    const btnResetFocus = document.getElementById('btnResetFocus');
    
    // Drawer elements
    const drawer = document.getElementById('memberDrawer');
    const drawerBackdrop = document.getElementById('memberDrawerBackdrop');
    const btnCloseDrawer = document.getElementById('btnCloseDrawer');
    const drawerBody = document.getElementById('drawerBody');
    const btnDrawerFocusSubtree = document.getElementById('btnDrawerFocusSubtree');
    const btnDrawerFullProfile = document.getElementById('btnDrawerFullProfile');

    const rootPayloads = @json($rootPayloads);
    const initialRoot = @json($initialRoot);
    const canManage = @json(auth()->user()?->hasPermission('mlm.manage') ?? false);
    
    let activeTopRoot = initialRoot;
    let focusHistory = []; // Stack of { id, name, customer_id }
    let searchCache = [];
    let zoomScale = 1.0;
    let currentDrawerMember = null;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    })[character]);

    const statusBadge = (status) => {
        if (status === 'active') return '<span class="inline-flex px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>';
        if (status === 'blocked') return '<span class="inline-flex px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Blocked</span>';
        if (status === 'pending') return '<span class="inline-flex px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Pending</span>';
        return '<span class="inline-flex px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-slate-100 text-slate-600">Inactive</span>';
    };

    const posBadge = (pos) => {
        if (pos === 'left') return '<span class="inline-flex px-1.5 py-0.2 rounded text-[9px] font-bold uppercase bg-sky-50 text-sky-700 border border-sky-200">Left Slot</span>';
        if (pos === 'middle') return '<span class="inline-flex px-1.5 py-0.2 rounded text-[9px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200">Middle Slot</span>';
        if (pos === 'right') return '<span class="inline-flex px-1.5 py-0.2 rounded text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">Right Slot</span>';
        return '<span class="inline-flex px-1.5 py-0.2 rounded text-[9px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200">Root</span>';
    };

    const posCardClass = (pos) => {
        if (pos === 'left') return 'pos-left';
        if (pos === 'middle') return 'pos-middle';
        if (pos === 'right') return 'pos-right';
        return 'pos-root';
    };

    const showMessage = (text, type = 'error') => {
        message.className = type === 'error'
            ? 'p-4 rounded-xl text-xs font-semibold bg-rose-50 border border-rose-200 text-rose-800'
            : 'p-4 rounded-xl text-xs font-semibold bg-blue-50 border border-blue-200 text-blue-800';
        message.textContent = text;
        message.classList.remove('hidden');
    };

    const clearMessage = () => {
        message.className = 'hidden';
        message.textContent = '';
    };

    // Render an interactive Org Chart Node Card
    const renderNodeCard = (member, position = 'root') => {
        const card = document.createElement('div');
        const isTopRoot = member.is_top_root || member.customer_id === 'C22020008' || position === 'root';
        const isIdle = Boolean(member.is_idle_slot);
        const isPillarLeader = ['C22010003', 'C22020001', 'C22030018'].includes(member.customer_id);
        const isPromoted = Boolean(isIdle && (member.is_promoted_acting || (member.promoted_info && !member.promoted_info.is_expired)));
        const isExpired = Boolean(isIdle && (member.is_promoted_expired || (member.promoted_info && member.promoted_info.is_expired)));
        const isDualPlaced = Boolean(member.is_dual_placed || (member.promotions_held && member.promotions_held.length > 0));

        const cardClass = isTopRoot 
            ? 'pos-company-root ring-2 ring-amber-400/50' 
            : (isPromoted
                ? 'pos-promoted-node ring-2 ring-emerald-500/80 bg-gradient-to-b from-amber-50/70 via-emerald-50/30 to-white shadow-md shadow-emerald-500/10'
                : (isExpired
                    ? 'pos-expired-node ring-2 ring-rose-400/80 bg-rose-50/40'
                    : (isIdle
                        ? 'pos-idle-slot ring-2 ring-amber-400/60 bg-amber-50/40'
                        : (isPillarLeader 
                            ? 'pos-pillar-node ring-1 ring-orange-300' 
                            : (posCardClass(position) + (isDualPlaced ? ' ring-2 ring-amber-400/80 shadow-xs' : ''))))));

        card.className = `org-node-card ${cardClass} p-3.5`;
        card.dataset.nodeCard = 'true';
        card.dataset.nodeId = member.id;
        card._memberData = member;

        let avatarSvg = '';
        if (isTopRoot) {
            avatarSvg = `<svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/></svg>`;
        } else if (isPromoted) {
            const pMon = ((member.name || 'P').split(' ').filter(Boolean).map(n => n[0]).slice(0, 2).join('').toUpperCase()) || 'P';
            avatarSvg = `<div class="relative flex items-center justify-center">
                <span class="font-extrabold text-[10px] text-white tracking-tight">${escapeHtml(pMon)}</span>
                <span class="absolute -top-3.5 -right-3 text-[10px] text-amber-300 drop-shadow">👑</span>
            </div>`;
        } else if (isIdle) {
            avatarSvg = `<svg class="w-4 h-4 text-amber-950" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>`;
        } else if (isPillarLeader) {
            avatarSvg = `<svg class="w-4 h-4 text-orange-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>`;
        } else {
            const mon = ((member.name || 'M').split(' ').filter(Boolean).map(n => n[0]).slice(0, 2).join('').toUpperCase()) || 'M';
            avatarSvg = `<span class="font-extrabold text-[11px] tracking-tight">${escapeHtml(mon)}</span>`;
        }

        const incomeFormatted = member.total_income_formatted || '₹0';
        const dlCount = Number(member.downline_count ?? 0);
        const dlBadge = `<span class="inline-flex items-center gap-1 font-bold text-[9px] text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-200" title="Total Network Downlines under this member">
            <svg class="w-2.5 h-2.5 text-indigo-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span>${dlCount} DL</span>
        </span>`;

        const avatarClass = isTopRoot
            ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-white shadow-xs border-amber-300'
            : (isPromoted
                ? 'bg-gradient-to-tr from-amber-400 via-orange-500 to-emerald-600 text-white shadow-xs border-amber-300 ring-2 ring-amber-300 font-extrabold'
                : (isIdle
                    ? 'bg-gradient-to-tr from-amber-400 to-amber-500 text-amber-950 border-amber-300 font-extrabold shadow-xs'
                    : (isPillarLeader 
                        ? 'bg-gradient-to-tr from-orange-100 to-amber-200 text-orange-800 border-orange-300 font-extrabold'
                        : (isDualPlaced
                            ? 'bg-gradient-to-tr from-amber-50 to-orange-100 text-amber-800 border-amber-300 font-extrabold ring-1 ring-amber-300'
                            : 'bg-gradient-to-tr from-blue-50 to-indigo-100 text-blue-700 border-indigo-200 font-extrabold'))));

        // Status badge in top right
        let cardStatusBadge = statusBadge(member.status);
        if (isPromoted) {
            cardStatusBadge = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-black bg-gradient-to-r from-amber-500 via-orange-500 to-emerald-600 text-white shadow-2xs uppercase tracking-wider animate-pulse">★ PROMOTED</span>`;
        } else if (isExpired) {
            cardStatusBadge = `<span class="inline-flex px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-rose-100 text-rose-800 border border-rose-300">Expired</span>`;
        }

        // Duration & Expiration Badge (for Promoted Idle Position - Place A)
        let promotionDurationBadge = '';
        if (isIdle && member.promoted_info) {
            if (member.promoted_info.is_indefinite) {
                promotionDurationBadge = `
                    <div class="mt-1 px-2 py-1 rounded-lg bg-emerald-50 border border-emerald-300 text-emerald-950 text-[9px] font-bold flex items-center justify-between gap-1 shadow-2xs">
                        <span class="flex items-center gap-1.5 truncate"><i class="fas fa-infinity text-emerald-600 flex-shrink-0"></i> Flexible Duration (Until Reverted)</span>
                        <span class="text-[8px] font-extrabold text-emerald-800 bg-emerald-200/80 px-1 py-0.2 rounded">Active</span>
                    </div>`;
            } else if (!member.promoted_info.is_expired) {
                const days = member.promoted_info.days_remaining;
                const isUrgent = days <= 3;
                const bgClass = isUrgent ? 'bg-amber-50 border-amber-300 text-amber-950' : 'bg-emerald-50 border-emerald-300 text-emerald-950';
                const iconClass = isUrgent ? 'text-amber-600' : 'text-emerald-600';
                promotionDurationBadge = `
                    <div class="mt-1 px-2 py-1 rounded-lg ${bgClass} border text-[9px] font-bold flex items-center justify-between gap-1 shadow-2xs">
                        <span class="flex items-center gap-1.5 truncate"><i class="fas fa-hourglass-half ${iconClass} flex-shrink-0"></i> ⏳ ${days} ${days === 1 ? 'Day' : 'Days'} Left <span class="font-normal text-slate-500 text-[8px]">(until ${escapeHtml(member.promoted_info.expires_at)})</span></span>
                        <span class="text-[8px] font-extrabold bg-white/90 text-slate-700 px-1 py-0.2 rounded border border-slate-200 shadow-2xs">Active</span>
                    </div>`;
            } else {
                promotionDurationBadge = `
                    <div class="mt-1 px-2 py-1 rounded-lg bg-rose-50 border border-rose-300 text-rose-950 text-[9px] font-bold flex items-center justify-between gap-1 shadow-2xs">
                        <span class="flex items-center gap-1.5 truncate"><i class="fas fa-exclamation-triangle text-rose-600 flex-shrink-0"></i> Temporary Time Expired</span>
                        <button type="button" class="btn-manage-promotion px-1.5 py-0.5 rounded text-[8px] font-black bg-rose-600 hover:bg-rose-700 text-white transition" data-id="${member.id}">Extend</button>
                    </div>`;
            }
        }

        // Dual-Placement Link to Original Position (on Promoted Idle Card - Place A)
        let originalPositionLink = '';
        if (isIdle && member.promoted_info && member.promoted_info.original_path) {
            originalPositionLink = `
                <div class="mt-1 px-2 py-1 rounded-lg bg-blue-50/80 border border-blue-200 text-blue-950 text-[9px] flex items-center justify-between gap-1 shadow-2xs">
                    <span class="truncate flex items-center gap-1"><i class="fas fa-map-marker-alt text-blue-600 text-[9px]"></i> Permanent: <strong>Level ${member.promoted_info.original_level_label || '0'}</strong></span>
                    <button type="button" class="btn-jump-node px-1.5 py-0.5 rounded text-[8px] font-black text-blue-800 bg-white hover:bg-blue-100 border border-blue-300 transition flex items-center gap-0.5" data-path='${JSON.stringify(member.promoted_info.original_path)}' data-target-id="${member.promoted_info.id}" title="Focus on member's permanent original card in the tree">
                        <span>View Original Card</span> <i class="fas fa-arrow-right text-[7px]"></i>
                    </button>
                </div>`;
        }

        // Dual-Placement Badge on Member's Existing Position Card (Place B)
        let dualPlacedBadge = '';
        if (member.promotions_held && member.promotions_held.length > 0) {
            dualPlacedBadge = member.promotions_held.map(promo => {
                const timeText = promo.is_indefinite ? 'Flexible' : `${promo.days_remaining}d left`;
                return `
                    <div class="mt-1.5 p-2 rounded-lg bg-gradient-to-r from-amber-50 via-orange-50 to-amber-50 border border-amber-300 text-amber-950 text-[9px] shadow-2xs">
                        <div class="flex items-center justify-between gap-1 font-bold">
                            <span class="flex items-center gap-1 truncate"><i class="fas fa-crown text-amber-600 text-[9px]"></i> Promoted at: <strong class="text-amber-900">${escapeHtml(promo.slot_label)}</strong></span>
                            <span class="text-[8px] font-extrabold bg-amber-200/90 text-amber-900 px-1.5 py-0.2 rounded font-mono">${timeText}</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between gap-1 pt-1 border-t border-amber-200/60">
                            <span class="text-[8px] text-emerald-800 font-bold flex items-center gap-1"><i class="fas fa-bolt text-emerald-600"></i> Double Income Active</span>
                            <button type="button" class="btn-jump-node px-1.5 py-0.5 rounded text-[8px] font-black text-amber-900 bg-white hover:bg-amber-100 border border-amber-300 transition flex items-center gap-0.5" data-path='${JSON.stringify(promo.slot_path || [])}' data-target-id="${promo.slot_id}" title="Jump to promoted idle position in tree">
                                <span>View Promoted Slot</span> <i class="fas fa-external-link-alt text-[7px]"></i>
                            </button>
                        </div>
                    </div>`;
            }).join('');
        }

        // Vacant Idle Slot Notice (when unoccupied)
        let vacantIdleNotice = '';
        if (isIdle && !member.promoted_info) {
            vacantIdleNotice = `
                <div class="mt-1 px-2 py-1 rounded-lg bg-amber-50/80 border border-amber-300 text-amber-900 text-[9px] font-semibold flex items-center gap-1.5">
                    <i class="fas fa-star text-amber-500"></i>
                    <span>Unoccupied Idle Slot &bull; Ready for Promotion</span>
                </div>`;
        }

        const rawCompanyLabel = (member.company_label || '').replace(/[\u{1F300}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}]/gu, '').trim();
        const companyBadgeIcon = isTopRoot
            ? `<svg class="w-3 h-3 text-amber-600 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/></svg>`
            : `<svg class="w-3 h-3 text-amber-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>`;

        const companyBadge = (rawCompanyLabel && !isPromoted) 
            ? `<div class="mt-1 mb-1 px-1.5 py-0.5 rounded bg-amber-50 border border-amber-200/80 text-amber-800 text-[9px] font-bold truncate flex items-center gap-1.5">
                 ${companyBadgeIcon} <span>${escapeHtml(rawCompanyLabel)}</span>
               </div>`
            : '';

        const phoneHtml = member.phone 
            ? `<div class="flex items-center gap-1 text-[10px] text-slate-600 truncate mt-0.5">
                 <svg class="w-2.5 h-2.5 text-slate-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                 <span class="font-mono text-[10px] font-medium text-slate-700">${escapeHtml(member.phone)}</span>
               </div>`
            : '';

        const sponsorHtml = member.sponsor 
            ? `<div class="flex justify-between items-center text-[10px] truncate" title="${escapeHtml(member.sponsor.name)} (${escapeHtml(member.sponsor.customer_id)})">
                 <span class="font-medium text-slate-400">Sponsor:</span>
                 <span class="font-bold text-slate-800 truncate max-w-[125px]">${escapeHtml(member.sponsor.name)}</span>
               </div>
               <div class="flex justify-between items-center text-[9px] text-slate-400 -mt-0.5 font-mono">
                 <span>ID:</span>
                 <span class="font-semibold text-slate-600">${escapeHtml(member.sponsor.customer_id)}</span>
               </div>`
            : `<div class="flex justify-between items-center text-[10px]"><span class="font-medium text-slate-400">Sponsor:</span> <span class="font-bold text-amber-700">Master Root</span></div>`;

        // Promotion Action Buttons
        let promotionActionBtn = '';
        if (isIdle) {
            if (member.promoted_info) {
                promotionActionBtn = `
                    <div class="flex items-center gap-1">
                        <button type="button" class="btn-manage-promotion px-2 py-0.8 font-bold text-amber-900 bg-amber-100 hover:bg-amber-200 rounded text-[9px] border border-amber-300 transition flex items-center gap-0.5" data-id="${member.id}" title="Manage Duration or Extend Promotion">
                            <i class="fas fa-sliders-h text-[8px]"></i> <span>Manage</span>
                        </button>
                        <button type="button" class="btn-clear-appreciation px-2 py-0.8 font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 rounded text-[9px] border border-rose-200 transition flex items-center gap-0.5" data-id="${member.id}" title="End temporary promotion and revert back to vacant idle slot">
                            <i class="fas fa-undo text-[8px]"></i> <span>Revert</span>
                        </button>
                    </div>`;
            } else {
                promotionActionBtn = `
                    <button type="button" class="btn-open-promote-modal px-2.5 py-0.8 font-extrabold text-amber-950 bg-gradient-to-r from-amber-300 via-amber-400 to-amber-300 hover:from-amber-400 hover:to-amber-500 rounded text-[9px] border border-amber-400 shadow-2xs transition flex items-center gap-1 active:scale-95" data-id="${member.id}" data-label="${escapeHtml(member.idle_slot_label || 'Idle Slot')}" data-code="${escapeHtml(member.slot_customer_id || member.customer_id)}" title="Promote an existing member to this idle slot">
                        <i class="fas fa-crown text-[8px] text-amber-900"></i> <span>+ Promote</span>
                    </button>`;
            }
        }

        const baseSlotTag = isPromoted && member.promoted_info
            ? `<span class="text-slate-400 font-normal text-[9px] truncate">(${escapeHtml(member.idle_slot_label || 'Idle Slot')})</span>`
            : '';

        card.innerHTML = `
            <!-- Box Content (for Detailed & Compact modes) -->
            <div class="card-box-content">
                <div class="flex items-center justify-between gap-1 mb-2">
                    <div class="flex items-center gap-1">
                        ${posBadge(position || member.placement_position)}
                        ${dlBadge}
                    </div>
                    ${cardStatusBadge}
                </div>

                <div class="flex items-center gap-2 mb-1.5">
                    <div class="card-avatar w-8 h-8 rounded-full ${avatarClass} text-xs flex items-center justify-center border border-slate-200 flex-shrink-0 relative">
                        ${avatarSvg}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="card-name font-bold text-slate-900 text-xs truncate leading-snug flex items-center gap-1" title="${escapeHtml(member.name)}">
                            <span class="truncate">${escapeHtml(member.name)}</span>
                            ${isPromoted ? '<span class="bg-amber-100 text-amber-800 text-[8px] font-black px-1.5 py-0.2 rounded-full uppercase tracking-wider shrink-0">★ PROMOTED</span>' : ''}
                        </div>
                        <div class="font-mono text-[10px] font-bold text-blue-600 truncate flex items-center gap-1">
                            <span>${escapeHtml(member.customer_id)}</span>
                            ${baseSlotTag}
                        </div>
                        ${phoneHtml}
                    </div>
                </div>

                ${companyBadge}
                ${promotionDurationBadge}
                ${originalPositionLink}
                ${dualPlacedBadge}
                ${vacantIdleNotice}

                <!-- Detailed Card Meta -->
                <div class="card-full-meta text-[10px] text-slate-500 pt-1.5 border-t border-slate-100 space-y-1 mb-2">
                    <div class="flex justify-between items-center"><span class="font-medium text-slate-400">Level:</span> <span class="font-semibold text-slate-700">${escapeHtml(member.level_label)}</span></div>
                    ${sponsorHtml}
                    <div class="flex justify-between items-center pt-1 border-t border-slate-100/80">
                        <span class="font-medium text-slate-400">Income:</span>
                        <span class="font-bold text-emerald-700 font-mono text-[10px] bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200/80">${escapeHtml(incomeFormatted)}</span>
                    </div>
                </div>

                <!-- Compact Mode Income Tag -->
                <div class="card-compact-meta hidden">
                    <div class="text-[10px] font-bold text-emerald-700 font-mono text-center bg-emerald-50 rounded py-0.5 mt-1 border border-emerald-200/80 truncate">${escapeHtml(incomeFormatted)}</div>
                </div>

                <div class="card-full-actions flex items-center justify-between gap-1 pt-2 border-t border-slate-100 text-[10px]">
                    <div class="flex items-center gap-1">
                        <button type="button" class="btn-node-drawer px-2.5 py-1 font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-md transition flex items-center gap-1">
                            <i class="fas fa-eye text-[9px]"></i> Info
                        </button>
                        <button type="button" class="btn-node-focus px-2.5 py-1 font-semibold text-orange-700 bg-orange-50 hover:bg-orange-100 rounded-md transition flex items-center gap-1" title="Focus subtree under this member">
                            <i class="fas fa-crosshairs text-[9px]"></i> Focus
                        </button>
                    </div>
                    ${promotionActionBtn}
                </div>
            </div>

            <!-- Circle Inner Content (for Circle mode) -->
            <div class="circle-inner-avatar hidden">
                ${avatarSvg}
            </div>

            <button type="button" class="org-expand-btn" data-expand="true" aria-expanded="false" title="Expand placement slots">
                <i class="fas fa-plus"></i>
            </button>
        `;

        return card;
    };

    // Render an Empty/Available Slot Card
    const renderEmptySlot = (position, label) => {
        const slotEl = document.createElement('div');
        const posClass = position === 'left' ? 'slot-left' : (position === 'middle' ? 'slot-middle' : 'slot-right');
        const iconColor = position === 'left' ? 'text-sky-500' : (position === 'middle' ? 'text-amber-500' : 'text-emerald-500');

        slotEl.className = `org-slot-empty ${posClass}`;
        slotEl.innerHTML = `
            <div class="empty-box-content">
                <i class="fas fa-plus-circle ${iconColor} text-base mb-1"></i>
                <span class="font-bold text-[11px] text-slate-700">${escapeHtml(label || position)} Slot</span>
                <span class="text-[10px] text-slate-400">Available Slot</span>
            </div>
            <div class="circle-slot-icon ${iconColor}">
                <i class="fas fa-plus"></i>
            </div>
        `;
        return slotEl;
    };

    // Render a complete tree branch (Parent Node + Children Container)
    const renderBranch = (member, position = 'root', initialSlots = null) => {
        const branch = document.createElement('div');
        branch.className = 'org-tree-branch';
        branch.dataset.nodeId = member.id;

        const nodeWrapper = document.createElement('div');
        nodeWrapper.className = 'org-node-wrapper';

        const card = renderNodeCard(member, position);
        nodeWrapper.append(card);

        // Sub-label for Circle Mode
        const circleLabel = document.createElement('div');
        circleLabel.className = 'circle-node-meta';
        circleLabel.innerHTML = `
            <div class="font-bold text-slate-900 text-[10px] truncate" title="${escapeHtml(member.name)}">${escapeHtml(member.name)}</div>
            <div class="font-mono text-[9px] text-blue-600 truncate">${escapeHtml(member.customer_id)}</div>
            <div class="font-mono text-[9px] font-bold text-emerald-700 bg-emerald-50 rounded px-1 border border-emerald-200/60 truncate mt-0.5">${escapeHtml(member.total_income_formatted || '₹0')}</div>
        `;
        nodeWrapper.append(circleLabel);

        const childrenContainer = document.createElement('div');
        childrenContainer.className = 'org-children-wrapper hidden';
        childrenContainer.dataset.loaded = 'false';

        branch.append(nodeWrapper, childrenContainer);

        branch._card = card;
        branch._nodeWrapper = nodeWrapper;
        branch._childrenContainer = childrenContainer;
        branch._expandButton = card.querySelector('[data-expand]');

        if (initialSlots && initialSlots.length > 0) {
            renderSlots(branch, initialSlots);
        }

        return branch;
    };

    // Render slots into branch
    const renderSlots = (branch, slots) => {
        branch._childrenContainer.replaceChildren();

        (slots || []).forEach((slot) => {
            const slotCol = document.createElement('div');
            slotCol.className = 'org-child-slot';

            if (slot.member) {
                const childBranch = renderBranch(slot.member, slot.position);
                slotCol.append(childBranch);
            } else {
                const emptySlot = renderEmptySlot(slot.position, slot.position_label);
                slotCol.append(emptySlot);
            }

            branch._childrenContainer.append(slotCol);
        });

        branch._childrenContainer.dataset.loaded = 'true';
        branch._childrenContainer.classList.remove('hidden');
        branch.classList.add('has-expanded-children');
        branch._expandButton.classList.add('is-expanded');
        branch._expandButton.setAttribute('aria-expanded', 'true');
        branch._expandButton.innerHTML = '<i class="fas fa-minus"></i>';
    };

    // Load children via AJAX
    const loadChildren = async (branch, toggleIfLoaded = true) => {
        if (branch._childrenContainer.dataset.loaded === 'true') {
            const isHidden = toggleIfLoaded
                ? branch._childrenContainer.classList.toggle('hidden')
                : false;
            branch._childrenContainer.classList.toggle('hidden', isHidden);
            branch.classList.toggle('has-expanded-children', !isHidden);
            branch._expandButton.classList.toggle('is-expanded', !isHidden);
            branch._expandButton.setAttribute('aria-expanded', String(!isHidden));
            branch._expandButton.innerHTML = isHidden ? '<i class="fas fa-plus"></i>' : '<i class="fas fa-minus"></i>';
            return;
        }

        branch._expandButton.disabled = true;
        branch._expandButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            const url = viewport.dataset.childrenUrl.replace('__MEMBER__', branch.dataset.nodeId);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Unable to load placement slots.');
            const payload = await response.json();
            renderSlots(branch, payload.slots);
        } catch (error) {
            showMessage(error.message || 'Unable to load placement slots.');
            branch._expandButton.innerHTML = '<i class="fas fa-plus"></i>';
        } finally {
            branch._expandButton.disabled = false;
        }
    };

    // Mount Root Tree
    const mountTree = (rootPayload, isSubtreeFocus = false) => {
        clearMessage();
        panContainer.replaceChildren();

        if (!rootPayload) {
            panContainer.innerHTML = '<div class="text-center text-slate-400 py-16"><i class="fas fa-sitemap text-4xl mb-3 text-slate-300 block"></i>No root member available.</div>';
            return;
        }

        const member = rootPayload.member || rootPayload;
        const slots = rootPayload.slots || [];

        if (!member || !member.id) {
            panContainer.innerHTML = '<div class="text-center text-slate-400 py-16"><i class="fas fa-sitemap text-4xl mb-3 text-slate-300 block"></i>No root member available.</div>';
            return;
        }

        const rootBranch = renderBranch(member, isSubtreeFocus ? 'focused' : 'root', slots);
        panContainer.append(rootBranch);

        renderBreadcrumbs();
        centerTree();
    };

    const findBranchInDom = (memberId) => panContainer.querySelector(`.org-tree-branch[data-node-id="${memberId}"]`);

    const openPath = async (pathIds) => {
        for (const memberId of pathIds) {
            const branch = findBranchInDom(memberId);
            if (!branch) continue;
            if (branch._childrenContainer.dataset.loaded !== 'true') {
                await loadChildren(branch, false);
            } else {
                branch._childrenContainer.classList.remove('hidden');
                branch.classList.add('has-expanded-children');
                branch._expandButton.classList.add('is-expanded');
                branch._expandButton.setAttribute('aria-expanded', 'true');
                branch._expandButton.innerHTML = '<i class="fas fa-minus"></i>';
            }
        }
    };

    // Subtree Focus Drill-Down
    const focusSubtree = async (member) => {
        if (!member) return;
        
        // Add to history if not already top
        if (!focusHistory.length) {
            const topRootMember = activeTopRoot.member || activeTopRoot;
            focusHistory.push({ id: topRootMember.id, name: topRootMember.name, customer_id: topRootMember.customer_id });
        }
        
        if (focusHistory[focusHistory.length - 1].id !== member.id) {
            focusHistory.push({ id: member.id, name: member.name, customer_id: member.customer_id });
        }

        try {
            const url = viewport.dataset.childrenUrl.replace('__MEMBER__', member.id);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Unable to focus member subtree.');
            const payload = await response.json();
            mountTree({ member: payload.parent || member, slots: payload.slots }, true);
            closeDrawer();
        } catch (e) {
            showMessage(e.message || 'Failed to focus subtree.');
        }
    };

    const renderBreadcrumbs = () => {
        if (!focusHistory.length || focusHistory.length === 1) {
            focusBar.classList.add('hidden');
            focusBreadcrumbs.innerHTML = '';
            return;
        }

        focusBar.classList.remove('hidden');
        focusBreadcrumbs.innerHTML = focusHistory.map((item, index) => {
            const isLast = index === focusHistory.length - 1;
            if (isLast) {
                return `<span class="font-bold text-blue-900 bg-blue-100 px-2 py-0.5 rounded">${escapeHtml(item.name)} (${escapeHtml(item.customer_id)})</span>`;
            }
            return `<button type="button" class="text-blue-700 hover:underline cursor-pointer" data-crumb-idx="${index}">${escapeHtml(item.name)}</button> <i class="fas fa-chevron-right text-[9px] text-slate-400"></i>`;
        }).join(' ');
    };

    focusBreadcrumbs?.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-crumb-idx]');
        if (!btn) return;
        const idx = Number(btn.dataset.crumbIdx);
        const target = focusHistory[idx];
        focusHistory = focusHistory.slice(0, idx + 1);
        
        if (idx === 0) {
            focusHistory = [];
            mountTree(activeTopRoot, false);
        } else {
            await focusSubtree(target);
        }
    });

    btnResetFocus?.addEventListener('click', () => {
        focusHistory = [];
        mountTree(activeTopRoot, false);
    });

    // Drawer System
    const openDrawer = (member) => {
        currentDrawerMember = member;
        const initials = ((member.name || 'M').split(' ').filter(Boolean).map(n => n[0]).slice(0, 2).join('').toUpperCase()) || 'M';
        const isIdle = Boolean(member.is_idle_slot);
        const isPromoted = Boolean(isIdle && (member.is_promoted_acting || (member.promoted_info && !member.promoted_info.is_expired)));
        const isExpired = Boolean(isIdle && (member.is_promoted_expired || (member.promoted_info && member.promoted_info.is_expired)));
        const isDual = Boolean(member.is_dual_placed || (member.promotions_held && member.promotions_held.length > 0));

        let promoDrawerSection = '';
        if (isIdle && member.promoted_info) {
            const pInfo = member.promoted_info;
            const timeDesc = pInfo.is_indefinite
                ? '<span class="text-emerald-700 font-bold"><i class="fas fa-infinity"></i> Flexible Duration (Until Reverted)</span>'
                : (pInfo.is_expired
                    ? '<span class="text-rose-600 font-bold"><i class="fas fa-exclamation-triangle"></i> Expired on ' + escapeHtml(pInfo.expires_at) + '</span>'
                    : '<span class="text-emerald-700 font-bold"><i class="fas fa-clock"></i> ' + pInfo.days_remaining + ' Days Remaining (Expires ' + escapeHtml(pInfo.expires_at) + ')</span>');

            const origPathJson = JSON.stringify(pInfo.original_path || []);
            promoDrawerSection = `
                <div class="p-3.5 rounded-2xl bg-gradient-to-br from-amber-50 via-emerald-50/40 to-white border-2 border-emerald-400 shadow-sm space-y-2">
                    <div class="flex items-center justify-between gap-1">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 flex items-center gap-1.5">
                            <i class="fas fa-crown text-amber-500"></i> Active Temporary Promotion
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-100 text-emerald-800 uppercase">Dual Placement</span>
                    </div>
                    <div class="pt-1 text-slate-800">
                        <div class="font-bold text-sm text-slate-900">${escapeHtml(member.name)}</div>
                        <div class="font-mono text-[11px] text-blue-700 font-semibold">${escapeHtml(member.customer_id)} &bull; ${escapeHtml(member.phone || 'No phone')}</div>
                    </div>
                    <div class="text-[11px] pt-1 border-t border-emerald-200/60 flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Duration Status:</span>
                        <span>${timeDesc}</span>
                    </div>
                    ${pInfo.note ? `<div class="text-[10px] text-slate-600 bg-white/70 p-1.5 rounded border border-emerald-200/50"><strong>Reason:</strong> ${escapeHtml(pInfo.note)}</div>` : ''}
                    <div class="pt-2 flex items-center gap-2">
                        <button type="button" class="btn-jump-node flex-1 py-1.5 px-2 bg-blue-50 hover:bg-blue-100 text-blue-800 rounded-lg font-bold text-[10px] border border-blue-200 transition flex items-center justify-center gap-1" data-path='${origPathJson}' data-target-id="${pInfo.id}">
                            <i class="fas fa-map-marker-alt text-blue-600"></i> <span>Jump to Original Permanent Card</span>
                        </button>
                        <button type="button" class="btn-manage-promotion px-2.5 py-1.5 bg-amber-100 hover:bg-amber-200 text-amber-900 rounded-lg font-bold text-[10px] border border-amber-300 transition" data-id="${member.id}">
                            <i class="fas fa-sliders-h"></i> Manage / Extend
                        </button>
                    </div>
                </div>
            `;
        } else if (isIdle && !member.promoted_info) {
            promoDrawerSection = `
                <div class="p-3.5 rounded-2xl bg-amber-50/70 border-2 border-dashed border-amber-300 text-center space-y-2">
                    <div class="text-xs font-bold text-amber-900 flex items-center justify-center gap-1.5">
                        <i class="fas fa-star text-amber-500"></i> Unoccupied Idle Slot
                    </div>
                    <p class="text-[10px] text-slate-500">This slot is reserved for leaders and can receive a temporary member promotion anytime.</p>
                    <button type="button" class="btn-open-promote-modal w-full py-2 px-3 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-500 hover:to-amber-600 text-amber-950 font-extrabold text-xs shadow-sm transition flex items-center justify-center gap-1.5" data-id="${member.id}" data-label="${escapeHtml(member.idle_slot_label || 'Idle Slot')}" data-code="${escapeHtml(member.slot_customer_id || member.customer_id)}">
                        <i class="fas fa-crown text-amber-900"></i> <span>Promote Member to this Slot</span>
                    </button>
                </div>
            `;
        } else if (isDual) {
            const promoList = (member.promotions_held || []).map(p => {
                const pathJson = JSON.stringify(p.slot_path || []);
                const timeStr = p.is_indefinite ? 'Flexible' : `${p.days_remaining}d remaining`;
                return `
                    <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-300 flex items-center justify-between gap-2">
                        <div>
                            <div class="font-bold text-slate-900 text-xs flex items-center gap-1"><i class="fas fa-crown text-amber-500 text-[10px]"></i> ${escapeHtml(p.slot_label)}</div>
                            <div class="text-[10px] text-amber-800 font-medium">⏳ ${timeStr}</div>
                        </div>
                        <button type="button" class="btn-jump-node px-2 py-1 rounded bg-white hover:bg-amber-100 text-amber-900 border border-amber-300 font-bold text-[9px] transition" data-path='${pathJson}' data-target-id="${p.slot_id}">
                            View Promoted Slot &rarr;
                        </button>
                    </div>
                `;
            }).join('');

            promoDrawerSection = `
                <div class="p-3 rounded-2xl bg-gradient-to-r from-amber-50/80 via-orange-50/50 to-white border border-amber-200 space-y-2">
                    <div class="text-[10px] font-extrabold uppercase tracking-wider text-amber-900 flex items-center gap-1">
                        <i class="fas fa-bolt text-amber-600"></i> Dual-Placement Active Promotions
                    </div>
                    <div class="space-y-1.5">${promoList}</div>
                </div>
            `;
        } else if (!isTopRoot && !isIdle) {
            promoDrawerSection = `
                <button type="button" class="btn-drawer-promote-member w-full py-2.5 px-3 rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 hover:from-amber-100 hover:to-orange-100 border border-amber-300 text-amber-950 font-bold text-xs shadow-2xs transition flex items-center justify-center gap-1.5" data-member-id="${member.id}" data-member-name="${escapeHtml(member.name)}" data-member-code="${escapeHtml(member.customer_id)}" data-member-phone="${escapeHtml(member.phone || '')}" data-member-level="${escapeHtml(member.level_label || '0')}">
                    <i class="fas fa-crown text-amber-600"></i>
                    <span>Promote this Member to an Idle Position</span>
                </button>
            `;
        }
        
        drawerBody.innerHTML = `
            <div class="text-center pb-5 border-b border-slate-100">
                <div class="w-16 h-16 mx-auto rounded-2xl ${isPromoted ? 'bg-gradient-to-tr from-amber-400 via-orange-500 to-emerald-600 ring-4 ring-emerald-300/40' : 'bg-gradient-to-tr from-orange-500 via-orange-600 to-amber-500 ring-4 ring-orange-400/20'} text-white text-xl font-black flex items-center justify-center shadow-md shadow-orange-500/25 mb-3 relative">
                    ${escapeHtml(initials)}
                    ${isPromoted ? '<span class="absolute -top-1.5 -right-1.5 text-base drop-shadow">👑</span>' : ''}
                </div>
                <div class="font-black text-slate-900 text-base tracking-tight flex items-center justify-center gap-1.5">
                    <span>${escapeHtml(member.name)}</span>
                    ${isPromoted ? '<span class="bg-amber-100 text-amber-800 text-[9px] font-black px-1.5 py-0.2 rounded-full uppercase">★ Promoted</span>' : ''}
                </div>
                <div class="inline-flex items-center gap-1.5 mt-1 px-2.5 py-0.5 rounded-full bg-orange-50 border border-orange-200/80 font-mono text-xs font-bold text-orange-700 shadow-2xs">
                    <i class="fas fa-id-card text-[10px] text-orange-500"></i>
                    <span>${escapeHtml(member.customer_id)}</span>
                    ${isIdle && member.idle_slot_label ? `<span class="text-slate-400 font-normal">(${escapeHtml(member.idle_slot_label)})</span>` : ''}
                </div>
                <div class="flex items-center justify-center gap-1.5 mt-3 flex-wrap">
                    ${posBadge(member.position || member.placement_position)}
                    ${isPromoted ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-600 text-white">Active Promotion</span>' : statusBadge(member.status)}
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        <i class="fas fa-shield-check text-emerald-600 text-[10px]"></i> KYC: ${escapeHtml(member.kyc_status || 'Approved')}
                    </span>
                </div>
            </div>

            ${promoDrawerSection}

            <div class="p-4 bg-gradient-to-br from-orange-50 via-amber-50/50 to-orange-50/30 rounded-2xl border border-orange-200/80 text-center shadow-2xs">
                <div class="text-[10px] text-orange-800 font-extrabold uppercase tracking-wider flex items-center justify-center gap-1.5">
                    <i class="fas fa-wallet text-orange-600"></i> Total Commission Income
                </div>
                <div class="text-2xl font-black text-orange-600 font-mono mt-1 tracking-tight">${escapeHtml(member.total_income_formatted || '₹0')}</div>
            </div>

            <div class="grid grid-cols-2 gap-3 text-center">
                <div class="p-3 bg-slate-50/80 rounded-xl border border-slate-200/70">
                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Matrix Level</div>
                    <div class="text-lg font-black text-orange-600 mt-0.5">${escapeHtml(member.level_label)}</div>
                </div>
                <div class="p-3 bg-slate-50/80 rounded-xl border border-slate-200/70">
                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Downline Team</div>
                    <div class="text-lg font-black text-indigo-600 mt-0.5">${escapeHtml(member.downline_count || 0)} Members</div>
                </div>
            </div>

            <div class="space-y-2.5 pt-2 border-t border-slate-100">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div class="text-[10px] font-extrabold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fas fa-phone text-blue-600"></i> Contact & Location
                    </div>
                    <div class="font-semibold text-slate-800 mt-1">${escapeHtml(member.phone || 'No phone')} &bull; ${escapeHtml(member.email || 'No email')}</div>
                    <div class="text-[11px] text-slate-500 mt-0.5">${escapeHtml(member.full_address || member.city || 'Gujarat')}</div>
                </div>

                <div class="p-3 rounded-xl bg-orange-50/60 border border-orange-200/70">
                    <div class="text-[10px] font-extrabold text-orange-900 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fas fa-user-tag text-orange-600"></i> Direct Sponsor
                    </div>
                    <div class="font-bold text-slate-900 mt-1">${escapeHtml(member.sponsor ? member.sponsor.name : 'Root Leader / None')}</div>
                    <div class="font-mono text-orange-700 text-[11px] font-semibold mt-0.5">${escapeHtml(member.sponsor ? `${member.sponsor.customer_id} ${member.sponsor.phone ? '• ' + member.sponsor.phone : ''}` : '')}</div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div class="text-[10px] font-extrabold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fas fa-sitemap text-slate-500"></i> Placement Parent
                    </div>
                    <div class="font-bold text-slate-900 mt-1">${escapeHtml(member.placement_parent ? member.placement_parent.name : 'Matrix Root')}</div>
                    <div class="font-mono text-slate-600 text-[11px] font-semibold mt-0.5">${escapeHtml(member.placement_parent ? `${member.placement_parent.customer_id} ${member.placement_parent.phone ? '• ' + member.placement_parent.phone : ''}` : '')}</div>
                </div>
            </div>
        `;

        btnDrawerFullProfile.href = member.details_url || '#';
        drawerBackdrop.classList.remove('hidden');
        drawer.classList.remove('translate-x-full');
    };

    const closeDrawer = () => {
        drawer.classList.add('translate-x-full');
        drawerBackdrop.classList.add('hidden');
    };

    btnCloseDrawer?.addEventListener('click', closeDrawer);
    drawerBackdrop?.addEventListener('click', closeDrawer);
    btnDrawerFocusSubtree?.addEventListener('click', () => {
        if (currentDrawerMember) focusSubtree(currentDrawerMember);
    });

    // Jump to node in tree by path with smooth pan & highlight animation
    const jumpToNode = async (pathIds, targetId) => {
        if (!pathIds || !pathIds.length) return;
        showMessage('Navigating to position in 1:3 placement tree...', 'info');

        if (focusHistory.length > 0) {
            focusHistory = [];
            mountTree(activeTopRoot, false);
        }

        closeDrawer();
        closePromoteModal();
        closeManageModal();

        await openPath(pathIds);
        clearMessage();

        const branch = findBranchInDom(targetId);
        if (branch && branch._card) {
            branch._card.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
            branch._card.classList.add('tree-highlight');
            setTimeout(() => {
                branch._card.classList.remove('tree-highlight');
            }, 3000);
        }
    };

    // Promotion Modal Elements & State
    const modalPromote = document.getElementById('modalPromoteMember');
    const btnClosePromoteModal = document.getElementById('btnClosePromoteModal');
    const btnCancelPromoteModal = document.getElementById('btnCancelPromoteModal');
    const btnSubmitPromotion = document.getElementById('btnSubmitPromotion');
    const promoteSlotLabel = document.getElementById('promoteSlotLabel');
    const promoteSlotCode = document.getElementById('promoteSlotCode');
    const promoteSlotPickerRow = document.getElementById('promoteSlotPickerRow');
    const promoteSlotSelect = document.getElementById('promoteSlotSelect');
    const promoteSlotSummary = document.getElementById('promoteSlotSummary');
    const promoteMemberSearchInput = document.getElementById('promoteMemberSearchInput');
    const btnClearPromoteSearch = document.getElementById('btnClearPromoteSearch');
    const promoteSearchResultsDropdown = document.getElementById('promoteSearchResultsDropdown');
    const selectedPromoteMemberCard = document.getElementById('selectedPromoteMemberCard');
    const selectedMemberName = document.getElementById('selectedMemberName');
    const selectedMemberCode = document.getElementById('selectedMemberCode');
    const selectedMemberPhone = document.getElementById('selectedMemberPhone');
    const selectedMemberLevel = document.getElementById('selectedMemberLevel');
    const selectedMemberAvatar = document.getElementById('selectedMemberAvatar');
    const btnChangeSelectedMember = document.getElementById('btnChangeSelectedMember');
    const promoteBeneficiaryId = document.getElementById('promoteBeneficiaryId');
    const promoteTargetSlotId = document.getElementById('promoteTargetSlotId');
    const promoteNoteInput = document.getElementById('promoteNoteInput');
    const customDateRow = document.getElementById('customDateRow');
    const customExpiryInput = document.getElementById('customExpiryInput');

    let promoteDurationType = 'days';
    let promoteDurationDays = 7;
    let searchDebounceTimer = null;

    // Manage Modal Elements & State
    const modalManage = document.getElementById('modalManagePromotion');
    const btnCloseManageModal = document.getElementById('btnCloseManageModal');
    const btnCancelManageModal = document.getElementById('btnCancelManageModal');
    const btnSaveManagePromotion = document.getElementById('btnSaveManagePromotion');
    const btnEndPromotionNow = document.getElementById('btnEndPromotionNow');
    const manageModalTitle = document.getElementById('manageModalTitle');
    const manageModalSubtitle = document.getElementById('manageModalSubtitle');
    const manageMemberName = document.getElementById('manageMemberName');
    const manageMemberCode = document.getElementById('manageMemberCode');
    const manageCurrentExpiry = document.getElementById('manageCurrentExpiry');
    const manageNoteInput = document.getElementById('manageNoteInput');
    const manageCustomDateRow = document.getElementById('manageCustomDateRow');
    const manageCustomExpiryInput = document.getElementById('manageCustomExpiryInput');

    let manageSlotId = null;
    let manageDurationType = 'days';
    let manageDurationDays = 7;

    // Open Promote Modal
    const openPromoteModal = async (slotId, slotLabel, slotCode, preselected = null) => {
        closeDrawer();
        promoteTargetSlotId.value = slotId || '';
        promoteSlotLabel.textContent = slotLabel || 'Idle Slot';
        promoteSlotCode.textContent = 'Code: ' + (slotCode || 'Reserved');

        if (!slotId) {
            // Need to pick from available idle slots
            promoteSlotSummary.classList.add('hidden');
            promoteSlotPickerRow.classList.remove('hidden');
            promoteSlotSelect.innerHTML = '<option value="">Loading available idle slots...</option>';
            try {
                const res = await fetch(`{{ admin_route('mlm.tree.idle-slots') }}`, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (data.slots && data.slots.length) {
                    promoteSlotSelect.innerHTML = data.slots.map(s => `
                        <option value="${s.id}">${escapeHtml(s.label)} (${escapeHtml(s.customer_id)}) — Level ${s.level} under ${escapeHtml(s.parent_name)}${s.is_occupied ? ' [Occupied]' : ' [Available]'}</option>
                    `).join('');
                    promoteTargetSlotId.value = promoteSlotSelect.value;
                }
            } catch (e) {
                promoteSlotSelect.innerHTML = '<option value="">Error loading slots</option>';
            }
        } else {
            promoteSlotSummary.classList.remove('hidden');
            promoteSlotPickerRow.classList.add('hidden');
        }

        if (preselected) {
            selectPromoteCandidate(preselected);
        } else {
            resetSelectedCandidate();
        }

        // Reset duration chips to default (7 days)
        document.querySelectorAll('#durationChipsContainer .duration-chip').forEach(c => {
            c.classList.remove('active', 'border-amber-500', 'bg-amber-50', 'text-amber-900');
            c.classList.add('border-slate-200', 'bg-white', 'text-slate-700');
        });
        const defaultChip = document.querySelector('#durationChipsContainer .duration-chip[data-days="7"]');
        if (defaultChip) {
            defaultChip.classList.add('active', 'border-amber-500', 'bg-amber-50', 'text-amber-900');
            defaultChip.classList.remove('border-slate-200', 'bg-white', 'text-slate-700');
        }
        promoteDurationType = 'days';
        promoteDurationDays = 7;
        customDateRow.classList.add('hidden');

        promoteNoteInput.value = 'Special performance appreciation promotion';
        modalPromote.classList.remove('hidden');
    };

    const closePromoteModal = () => {
        modalPromote.classList.add('hidden');
        promoteSearchResultsDropdown.classList.add('hidden');
    };

    const selectPromoteCandidate = (candidate) => {
        promoteBeneficiaryId.value = candidate.id || candidate.customer_id;
        selectedMemberName.textContent = candidate.name || 'Member';
        selectedMemberCode.textContent = candidate.customer_id || '';
        selectedMemberPhone.textContent = candidate.phone || 'No phone';
        selectedMemberLevel.textContent = 'Level ' + (candidate.level_label || candidate.level || '0');
        selectedMemberAvatar.textContent = ((candidate.name || 'M').split(' ').filter(Boolean).map(n => n[0]).slice(0, 2).join('').toUpperCase()) || 'M';

        selectedPromoteMemberCard.classList.remove('hidden');
        promoteMemberSearchInput.classList.add('hidden');
        btnClearPromoteSearch.classList.add('hidden');
        promoteSearchResultsDropdown.classList.add('hidden');
    };

    const resetSelectedCandidate = () => {
        promoteBeneficiaryId.value = '';
        selectedPromoteMemberCard.classList.add('hidden');
        promoteMemberSearchInput.classList.remove('hidden');
        promoteMemberSearchInput.value = '';
        btnClearPromoteSearch.classList.add('hidden');
        promoteSearchResultsDropdown.classList.add('hidden');
    };

    btnChangeSelectedMember?.addEventListener('click', resetSelectedCandidate);
    btnClosePromoteModal?.addEventListener('click', closePromoteModal);
    btnCancelPromoteModal?.addEventListener('click', closePromoteModal);

    // Duration Chips in Promote Modal
    document.querySelectorAll('#durationChipsContainer .duration-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('#durationChipsContainer .duration-chip').forEach(c => {
                c.classList.remove('active', 'border-amber-500', 'bg-amber-50', 'text-amber-900');
                c.classList.add('border-slate-200', 'bg-white', 'text-slate-700');
            });
            chip.classList.add('active', 'border-amber-500', 'bg-amber-50', 'text-amber-900');
            chip.classList.remove('border-slate-200', 'bg-white', 'text-slate-700');

            promoteDurationType = chip.dataset.type;
            if (promoteDurationType === 'days') {
                promoteDurationDays = Number(chip.dataset.days);
                customDateRow.classList.add('hidden');
            } else if (promoteDurationType === 'date') {
                customDateRow.classList.remove('hidden');
                // Set default to 30 days from now
                const d = new Date();
                d.setDate(d.getDate() + 30);
                customExpiryInput.value = d.toISOString().slice(0, 16);
            } else if (promoteDurationType === 'indefinite') {
                customDateRow.classList.add('hidden');
            }
        });
    });

    // Autocomplete Search in Promote Modal
    promoteMemberSearchInput?.addEventListener('input', (e) => {
        const val = e.target.value.trim();
        btnClearPromoteSearch.classList.toggle('hidden', !val);
        clearTimeout(searchDebounceTimer);

        if (val.length < 2) {
            promoteSearchResultsDropdown.classList.add('hidden');
            promoteSearchResultsDropdown.innerHTML = '';
            return;
        }

        searchDebounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(`{{ admin_route('mlm.tree.search-promotable') }}?q=` + encodeURIComponent(val), {
                    headers: { Accept: 'application/json' }
                });
                const data = await res.json();
                const members = data.data || [];
                if (!members.length) {
                    promoteSearchResultsDropdown.innerHTML = '<div class="p-3 text-slate-400 text-center">No matching members found</div>';
                } else {
                    promoteSearchResultsDropdown.innerHTML = members.map(m => `
                        <div class="p-2.5 hover:bg-amber-50 cursor-pointer flex items-center justify-between gap-2 candidate-item transition" data-json='${escapeHtml(JSON.stringify(m))}'>
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-7 h-7 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-[10px] flex-shrink-0">
                                    ${((m.name || 'M').split(' ').filter(Boolean).map(n => n[0]).slice(0, 2).join('').toUpperCase())}
                                </div>
                                <div class="min-w-0 truncate">
                                    <div class="font-bold text-slate-900 truncate">${escapeHtml(m.name)}</div>
                                    <div class="font-mono text-[9px] text-blue-600">${escapeHtml(m.customer_id)} &bull; ${escapeHtml(m.phone || 'No phone')}</div>
                                </div>
                            </div>
                            <span class="text-[9px] font-bold text-emerald-800 bg-emerald-100/70 px-1.5 py-0.5 rounded flex-shrink-0">Level ${m.level_label}</span>
                        </div>
                    `).join('');
                }
                promoteSearchResultsDropdown.classList.remove('hidden');
            } catch (err) {
                promoteSearchResultsDropdown.innerHTML = '<div class="p-3 text-rose-500 text-center">Search error</div>';
                promoteSearchResultsDropdown.classList.remove('hidden');
            }
        }, 250);
    });

    btnClearPromoteSearch?.addEventListener('click', () => {
        promoteMemberSearchInput.value = '';
        btnClearPromoteSearch.classList.add('hidden');
        promoteSearchResultsDropdown.classList.add('hidden');
    });

    promoteSearchResultsDropdown?.addEventListener('click', (e) => {
        const item = e.target.closest('.candidate-item');
        if (!item) return;
        const candidate = JSON.parse(item.dataset.json);
        selectPromoteCandidate(candidate);
    });

    // Submit Promotion
    btnSubmitPromotion?.addEventListener('click', async () => {
        const slotId = promoteSlotPickerRow.classList.contains('hidden') ? promoteTargetSlotId.value : promoteSlotSelect.value;
        const beneficiary = promoteBeneficiaryId.value || promoteMemberSearchInput.value.trim();

        if (!slotId) {
            alert('Please select a target Idle Slot.');
            return;
        }
        if (!beneficiary) {
            alert('Please search and select a member to promote.');
            return;
        }

        btnSubmitPromotion.disabled = true;
        btnSubmitPromotion.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Promoting...';

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const assignUrlTemplate = @json(admin_route('mlm.tree.assign-appreciation', ['member' => '__MEMBER__']));
            const url = assignUrlTemplate.replace('__MEMBER__', slotId);
            const payload = {
                beneficiary,
                duration_type: promoteDurationType,
                duration_days: promoteDurationDays,
                expires_at: promoteDurationType === 'date' ? customExpiryInput.value : null,
                note: promoteNoteInput.value.trim(),
            };

            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to promote member.');

            closePromoteModal();
            showMessage(data.message, 'success');

            // Reload active tree to display card at both positions!
            await reloadActiveTree();
        } catch (e) {
            alert(e.message || 'Error processing promotion.');
        } finally {
            btnSubmitPromotion.disabled = false;
            btnSubmitPromotion.innerHTML = '<i class="fas fa-crown text-xs"></i> <span>Confirm & Promote Member</span>';
        }
    });

    // Open Manage Modal
    const openManageModal = (memberData) => {
        closeDrawer();
        manageSlotId = memberData.id;
        manageModalTitle.textContent = 'Manage ' + (memberData.idle_slot_label || 'Idle Slot');
        manageMemberName.textContent = memberData.name || 'Promoted Member';
        manageMemberCode.textContent = 'Customer Code: ' + (memberData.customer_id || '');

        const pInfo = memberData.promoted_info || {};
        if (pInfo.is_indefinite) {
            manageCurrentExpiry.innerHTML = '<span class="text-emerald-700 font-bold"><i class="fas fa-infinity"></i> Flexible Duration (Until Reverted)</span>';
        } else if (pInfo.is_expired) {
            manageCurrentExpiry.innerHTML = '<span class="text-rose-600 font-bold"><i class="fas fa-exclamation-triangle"></i> Expired on ' + escapeHtml(pInfo.expires_at || '') + '</span>';
        } else {
            manageCurrentExpiry.innerHTML = '<span class="text-emerald-700 font-bold"><i class="fas fa-hourglass-half"></i> ' + (pInfo.days_remaining || 0) + ' Days Left (until ' + escapeHtml(pInfo.expires_at || '') + ')</span>';
        }

        manageNoteInput.value = pInfo.note || memberData.appreciation_note || 'Temporary Promotion';

        // Reset manage chips
        document.querySelectorAll('#manageDurationChipsContainer .manage-chip').forEach(c => {
            c.classList.remove('active', 'border-emerald-500', 'bg-emerald-50', 'text-emerald-900');
            c.classList.add('border-slate-200', 'bg-white', 'text-slate-700');
        });
        const defaultChip = document.querySelector('#manageDurationChipsContainer .manage-chip[data-days="7"]');
        if (defaultChip) {
            defaultChip.classList.add('active', 'border-emerald-500', 'bg-emerald-50', 'text-emerald-900');
            defaultChip.classList.remove('border-slate-200', 'bg-white', 'text-slate-700');
        }
        manageDurationType = 'days';
        manageDurationDays = 7;
        manageCustomDateRow.classList.add('hidden');

        modalManage.classList.remove('hidden');
    };

    const closeManageModal = () => {
        modalManage.classList.add('hidden');
    };

    btnCloseManageModal?.addEventListener('click', closeManageModal);
    btnCancelManageModal?.addEventListener('click', closeManageModal);

    // Duration Chips in Manage Modal
    document.querySelectorAll('#manageDurationChipsContainer .manage-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('#manageDurationChipsContainer .manage-chip').forEach(c => {
                c.classList.remove('active', 'border-emerald-500', 'bg-emerald-50', 'text-emerald-900');
                c.classList.add('border-slate-200', 'bg-white', 'text-slate-700');
            });
            chip.classList.add('active', 'border-emerald-500', 'bg-emerald-50', 'text-emerald-900');
            chip.classList.remove('border-slate-200', 'bg-white', 'text-slate-700');

            manageDurationType = chip.dataset.type;
            if (manageDurationType === 'days') {
                manageDurationDays = Number(chip.dataset.days);
                manageCustomDateRow.classList.add('hidden');
            } else if (manageDurationType === 'date') {
                manageCustomDateRow.classList.remove('hidden');
                const d = new Date();
                d.setDate(d.getDate() + 30);
                manageCustomExpiryInput.value = d.toISOString().slice(0, 16);
            } else if (manageDurationType === 'indefinite') {
                manageCustomDateRow.classList.add('hidden');
            }
        });
    });

    // Save Manage Promotion (Update duration / note)
    btnSaveManagePromotion?.addEventListener('click', async () => {
        if (!manageSlotId) return;
        btnSaveManagePromotion.disabled = true;
        btnSaveManagePromotion.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const assignUrlTemplate = @json(admin_route('mlm.tree.assign-appreciation', ['member' => '__MEMBER__']));
            const url = assignUrlTemplate.replace('__MEMBER__', manageSlotId);
            const payload = {
                duration_type: manageDurationType,
                duration_days: manageDurationDays,
                expires_at: manageDurationType === 'date' ? manageCustomExpiryInput.value : null,
                note: manageNoteInput.value.trim(),
            };

            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to update promotion.');

            closeManageModal();
            showMessage(data.message, 'success');
            await reloadActiveTree();
        } catch (e) {
            alert(e.message || 'Error updating promotion.');
        } finally {
            btnSaveManagePromotion.disabled = false;
            btnSaveManagePromotion.innerHTML = 'Update Promotion';
        }
    });

    // End Promotion from Manage Modal
    btnEndPromotionNow?.addEventListener('click', async () => {
        if (!manageSlotId) return;
        if (!confirm('Are you sure you want to end this promotion immediately? The slot will revert back to vacant Idle status.')) return;
        closeManageModal();
        await unassignAppreciation(manageSlotId);
    });

    // Reload active tree
    const reloadActiveTree = async () => {
        const rootId = (activeTopRoot && activeTopRoot.member) ? activeTopRoot.member.id : (activeTopRoot?.id || 1);
        try {
            const url = viewport.dataset.childrenUrl.replace('__MEMBER__', rootId);
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (res.ok) {
                const payload = await res.json();
                activeTopRoot = { member: payload.parent, slots: payload.slots };
                mountTree(activeTopRoot, focusHistory.length > 0);
            }
        } catch (err) {
            console.error('Error reloading tree:', err);
        }
    };

    // Event Delegation: Node Click, Expand Click & Dual-Placement Actions
    viewport.addEventListener('click', (event) => {
        const expandBtn = event.target.closest('[data-expand]');
        if (expandBtn) {
            const branch = expandBtn.closest('.org-tree-branch');
            if (branch) loadChildren(branch, true);
            return;
        }

        const focusBtn = event.target.closest('.btn-node-focus');
        if (focusBtn) {
            const card = focusBtn.closest('.org-node-card');
            if (card?._memberData) focusSubtree(card._memberData);
            return;
        }

        const jumpBtn = event.target.closest('.btn-jump-node');
        if (jumpBtn) {
            event.stopPropagation();
            const path = JSON.parse(jumpBtn.dataset.path || '[]');
            const targetId = Number(jumpBtn.dataset.targetId);
            jumpToNode(path, targetId);
            return;
        }

        const openPromoteBtn = event.target.closest('.btn-open-promote-modal');
        if (openPromoteBtn) {
            event.stopPropagation();
            openPromoteModal(openPromoteBtn.dataset.id, openPromoteBtn.dataset.label, openPromoteBtn.dataset.code);
            return;
        }

        const manageBtn = event.target.closest('.btn-manage-promotion');
        if (manageBtn) {
            event.stopPropagation();
            const card = manageBtn.closest('.org-node-card');
            openManageModal(card?._memberData || { id: manageBtn.dataset.id });
            return;
        }

        const clearBtn = event.target.closest('.btn-clear-appreciation');
        if (clearBtn) {
            event.stopPropagation();
            if (!confirm('Are you sure you want to end this temporary promotion and restore the idle slot back to unoccupied status?')) return;
            unassignAppreciation(clearBtn.dataset.id);
            return;
        }

        const card = event.target.closest('.org-node-card');
        if (card && card._memberData) {
            openDrawer(card._memberData);
        }
    });

    // Drawer click delegation (for drawer jump button & promote button)
    drawerBody?.addEventListener('click', (event) => {
        const jumpBtn = event.target.closest('.btn-jump-node');
        if (jumpBtn) {
            event.stopPropagation();
            const path = JSON.parse(jumpBtn.dataset.path || '[]');
            const targetId = Number(jumpBtn.dataset.targetId);
            jumpToNode(path, targetId);
            return;
        }

        const promoteMemberBtn = event.target.closest('.btn-drawer-promote-member');
        if (promoteMemberBtn) {
            event.stopPropagation();
            const candidate = {
                id: promoteMemberBtn.dataset.memberId,
                name: promoteMemberBtn.dataset.memberName,
                customer_id: promoteMemberBtn.dataset.memberCode,
                phone: promoteMemberBtn.dataset.memberPhone,
                level_label: promoteMemberBtn.dataset.memberLevel,
            };
            openPromoteModal(null, null, null, candidate);
            return;
        }

        const openPromoteBtn = event.target.closest('.btn-open-promote-modal');
        if (openPromoteBtn) {
            event.stopPropagation();
            openPromoteModal(openPromoteBtn.dataset.id, openPromoteBtn.dataset.label, openPromoteBtn.dataset.code);
            return;
        }

        const manageBtn = event.target.closest('.btn-manage-promotion');
        if (manageBtn) {
            event.stopPropagation();
            openManageModal(currentDrawerMember || { id: manageBtn.dataset.id });
            return;
        }
    });

    const unassignAppreciation = async (memberId) => {
        try {
            clearMessage();
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const unassignUrlTemplate = @json(admin_route('mlm.tree.unassign-appreciation', ['member' => '__MEMBER__']));
            const url = unassignUrlTemplate.replace('__MEMBER__', memberId);
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to revert promotion.');
            showMessage(data.message, 'success');
            await reloadActiveTree();
        } catch (e) {
            showMessage(e.message || 'Error reverting promotion.');
        }
    };

    // Density Mode Toggle
    document.querySelectorAll('.density-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const density = btn.dataset.density;
            document.querySelectorAll('.density-btn').forEach(b => {
                b.className = 'px-2.5 py-1 text-xs font-medium rounded-md text-slate-600 hover:text-slate-900 transition density-btn';
            });
            btn.className = 'px-2.5 py-1 text-xs font-bold rounded-md bg-white text-blue-600 shadow-xs transition density-btn';

            viewport.classList.remove('density-detailed', 'density-compact', 'density-circle');
            viewport.classList.add(`density-${density}`);

            // Re-center after density change
            setTimeout(centerTree, 50);
        });
    });

    // Load member tree by ID
    const loadMemberTree = async (memberId, isSubtreeFocus = false) => {
        try {
            clearMessage();
            const url = viewport.dataset.childrenUrl.replace('__MEMBER__', memberId);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Failed to load member hierarchy.');
            const payload = await response.json();
            
            if (!isSubtreeFocus) {
                focusHistory = [];
                activeTopRoot = { member: payload.parent, slots: payload.slots };
            }
            mountTree({ member: payload.parent, slots: payload.slots }, isSubtreeFocus);
            
            if (rootSelect) {
                rootSelect.value = String(memberId);
            }

            // Sync quick leader pills
            document.querySelectorAll('.quick-leader-pill').forEach((btn) => {
                if (btn.dataset.memberId === String(memberId)) {
                    btn.className = 'quick-leader-pill px-3 py-1.5 rounded-lg text-xs font-semibold transition border bg-orange-600 text-white border-orange-600 shadow-xs';
                } else {
                    btn.className = 'quick-leader-pill px-3 py-1.5 rounded-lg text-xs font-semibold transition border bg-slate-50 text-slate-700 border-slate-200 hover:bg-orange-50 hover:border-orange-200 hover:text-orange-900';
                }
            });

            // Update URL without reload
            const urlObj = new URL(window.location.href);
            urlObj.searchParams.set('root', memberId);
            window.history.replaceState({}, '', urlObj.toString());
        } catch (err) {
            showMessage(err.message || 'Error loading member tree.');
        }
    };

    // Quick Leader Pills Click
    document.querySelectorAll('.quick-leader-pill').forEach((btn) => {
        btn.addEventListener('click', () => {
            const memberId = Number(btn.dataset.memberId);
            if (memberId) loadMemberTree(memberId, false);
        });
    });

    // Root Selector Change
    rootSelect?.addEventListener('change', () => {
        const selectedId = Number(rootSelect.value);
        if (selectedId) {
            loadMemberTree(selectedId, false);
        }
    });

    // Search Member
    const executeSearch = async () => {
        const term = searchInput.value.trim();
        if (term.length < 2) {
            searchResults.classList.add('hidden');
            return;
        }

        try {
            const response = await fetch(`${viewport.dataset.searchUrl}?q=${encodeURIComponent(term)}`, {
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) throw new Error('Search failed.');
            const data = await response.json();
            const results = data.data || data;

            searchCache = Array.isArray(results) ? results : [];

            if (!searchCache.length) {
                searchResults.innerHTML = '<div class="p-3 text-slate-400 text-center">No member matches found</div>';
                searchResults.classList.remove('hidden');
                return;
            }

            searchResults.innerHTML = searchCache.map((item) => {
                const member = item.member || item;
                const dl = member.downline_count !== undefined ? `${member.downline_count} DL` : '';
                return `
                <div class="p-3 hover:bg-orange-50/70 cursor-pointer border-b border-slate-100 last:border-0 transition flex items-center justify-between gap-2" data-result-id="${member.id}">
                    <div class="min-w-0">
                        <div class="font-bold text-slate-900 text-xs truncate">${escapeHtml(member.name)}</div>
                        <div class="font-mono text-blue-600 text-[11px] truncate">${escapeHtml(member.customer_id)} &bull; ${escapeHtml(member.phone || '')} &bull; ${escapeHtml(member.city || 'Gujarat')}</div>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-800">
                            <i class="fas fa-sitemap text-[9px]"></i> ${escapeHtml(dl || 'View Tree')}
                        </span>
                    </div>
                </div>`;
            }).join('');
            searchResults.classList.remove('hidden');
        } catch (error) {
            showMessage(error.message || 'Search failed.');
        }
    };

    searchButton?.addEventListener('click', executeSearch);
    searchInput?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); executeSearch(); } });

    searchResults?.addEventListener('click', async (event) => {
        const item = event.target.closest('[data-result-id]');
        if (!item) return;

        searchResults.classList.add('hidden');
        const memberId = Number(item.dataset.resultId);
        if (memberId) {
            await loadMemberTree(memberId, false);
        }
    });

    document.addEventListener('click', (e) => {
        if (!searchResults.contains(e.target) && e.target !== searchInput && e.target !== searchButton) {
            searchResults.classList.add('hidden');
        }
    });

    // Zoom & Pan System
    const setZoom = (scale) => {
        zoomScale = Math.min(Math.max(scale, 0.25), 1.6);
        panContainer.style.transform = `scale(${zoomScale})`;
        zoomLevelText.textContent = `${Math.round(zoomScale * 100)}%`;
    };

    document.getElementById('btnZoomIn')?.addEventListener('click', () => setZoom(zoomScale + 0.1));
    document.getElementById('btnZoomOut')?.addEventListener('click', () => setZoom(zoomScale - 0.1));
    document.getElementById('btnZoomReset')?.addEventListener('click', () => setZoom(1.0));

    // Auto-Fit to Screen
    document.getElementById('btnZoomFit')?.addEventListener('click', () => {
        const rootBranch = panContainer.firstElementChild;
        if (!rootBranch) return;

        const availableWidth = viewport.clientWidth - 60;
        const naturalWidth = rootBranch.scrollWidth;

        if (naturalWidth > 0 && availableWidth > 0) {
            const fitScale = Math.min(1.0, Math.max(0.3, availableWidth / naturalWidth));
            setZoom(fitScale);
            centerTree();
        }
    });

    const centerTree = () => {
        setTimeout(() => {
            const scrollX = (panContainer.scrollWidth - viewport.clientWidth) / 2;
            viewport.scrollTo({ left: Math.max(0, scrollX), top: 0, behavior: 'smooth' });
        }, 50);
    };

    document.getElementById('btnCenterTree')?.addEventListener('click', centerTree);

    // Expand / Collapse Controls
    document.getElementById('btnExpandLevel1')?.addEventListener('click', () => {
        const rootBranch = panContainer.firstElementChild;
        if (rootBranch && rootBranch._childrenContainer) {
            rootBranch._childrenContainer.classList.remove('hidden');
            rootBranch.classList.add('has-expanded-children');
            rootBranch._expandButton.classList.add('is-expanded');
            rootBranch._expandButton.setAttribute('aria-expanded', 'true');
            rootBranch._expandButton.innerHTML = '<i class="fas fa-minus"></i>';
        }
    });

    document.getElementById('btnExpandLevel2')?.addEventListener('click', async () => {
        const rootBranch = panContainer.firstElementChild;
        if (!rootBranch) return;
        
        // Open L1
        rootBranch._childrenContainer.classList.remove('hidden');
        rootBranch.classList.add('has-expanded-children');

        // Open L2
        const l1Branches = Array.from(rootBranch._childrenContainer.querySelectorAll(':scope > .org-child-slot > .org-tree-branch'));
        for (const branch of l1Branches) {
            if (branch._childrenContainer.dataset.loaded !== 'true') {
                await loadChildren(branch, false);
            } else {
                branch._childrenContainer.classList.remove('hidden');
                branch.classList.add('has-expanded-children');
                branch._expandButton.classList.add('is-expanded');
                branch._expandButton.setAttribute('aria-expanded', 'true');
                branch._expandButton.innerHTML = '<i class="fas fa-minus"></i>';
            }
        }
    });

    document.getElementById('btnExpandLevel3')?.addEventListener('click', async () => {
        const rootBranch = panContainer.firstElementChild;
        if (!rootBranch) return;
        
        // Open L1
        if (rootBranch._childrenContainer.dataset.loaded !== 'true') {
            await loadChildren(rootBranch, false);
        } else {
            rootBranch._childrenContainer.classList.remove('hidden');
            rootBranch.classList.add('has-expanded-children');
            rootBranch._expandButton.classList.add('is-expanded');
            rootBranch._expandButton.setAttribute('aria-expanded', 'true');
            rootBranch._expandButton.innerHTML = '<i class="fas fa-minus"></i>';
        }

        // Open L2
        const l1Branches = Array.from(rootBranch._childrenContainer.querySelectorAll(':scope > .org-child-slot > .org-tree-branch'));
        for (const branch of l1Branches) {
            if (branch._childrenContainer.dataset.loaded !== 'true') {
                await loadChildren(branch, false);
            } else {
                branch._childrenContainer.classList.remove('hidden');
                branch.classList.add('has-expanded-children');
                branch._expandButton.classList.add('is-expanded');
                branch._expandButton.setAttribute('aria-expanded', 'true');
                branch._expandButton.innerHTML = '<i class="fas fa-minus"></i>';
            }
        }

        // Open L3 (Real Distributors under M5..M13)
        const l2Branches = Array.from(rootBranch._childrenContainer.querySelectorAll('.org-children-wrapper > .org-child-slot > .org-tree-branch'));
        for (const branch of l2Branches) {
            if (branch._childrenContainer.dataset.loaded !== 'true') {
                await loadChildren(branch, false);
            } else {
                branch._childrenContainer.classList.remove('hidden');
                branch.classList.add('has-expanded-children');
                branch._expandButton.classList.add('is-expanded');
                branch._expandButton.setAttribute('aria-expanded', 'true');
                branch._expandButton.innerHTML = '<i class="fas fa-minus"></i>';
            }
        }
        centerTree();
    });

    document.getElementById('btnExpandAll')?.addEventListener('click', async () => {
        const branches = Array.from(panContainer.querySelectorAll('.org-tree-branch'));
        for (const branch of branches) {
            if (branch._childrenContainer.dataset.loaded !== 'true') {
                await loadChildren(branch, false);
            } else {
                branch._childrenContainer.classList.remove('hidden');
                branch.classList.add('has-expanded-children');
                branch._expandButton.classList.add('is-expanded');
                branch._expandButton.setAttribute('aria-expanded', 'true');
                branch._expandButton.innerHTML = '<i class="fas fa-minus"></i>';
            }
        }
    });

    document.getElementById('btnCollapseAll')?.addEventListener('click', () => {
        const rootBranch = panContainer.firstElementChild;
        if (!rootBranch) return;

        const subBranches = Array.from(rootBranch.querySelectorAll('.org-children-wrapper .org-tree-branch'));
        subBranches.forEach((branch) => {
            branch._childrenContainer.classList.add('hidden');
            branch.classList.remove('has-expanded-children');
            branch._expandButton.classList.remove('is-expanded');
            branch._expandButton.setAttribute('aria-expanded', 'false');
            branch._expandButton.innerHTML = '<i class="fas fa-plus"></i>';
        });
    });

    // Drag-to-Pan Mouse Dragging
    let isMouseDown = false;
    let startX, startY, scrollLeft, scrollTop;

    viewport.addEventListener('mousedown', (e) => {
        if (e.target.closest('.org-node-card') || e.target.closest('button') || e.target.closest('a')) return;
        isMouseDown = true;
        startX = e.pageX - viewport.offsetLeft;
        startY = e.pageY - viewport.offsetTop;
        scrollLeft = viewport.scrollLeft;
        scrollTop = viewport.scrollTop;
    });

    viewport.addEventListener('mouseleave', () => { isMouseDown = false; });
    viewport.addEventListener('mouseup', () => { isMouseDown = false; });

    viewport.addEventListener('mousemove', (e) => {
        if (!isMouseDown) return;
        e.preventDefault();
        const x = e.pageX - viewport.offsetLeft;
        const y = e.pageY - viewport.offsetTop;
        const walkX = (x - startX) * 1.2;
        const walkY = (y - startY) * 1.2;
        viewport.scrollLeft = scrollLeft - walkX;
        viewport.scrollTop = scrollTop - walkY;
    });

    // Mousewheel Zoom (Ctrl + Wheel)
    viewport.addEventListener('wheel', (e) => {
        if (e.ctrlKey) {
            e.preventDefault();
            const delta = e.deltaY < 0 ? 0.08 : -0.08;
            setZoom(zoomScale + delta);
        }
    }, { passive: false });

    // Initial Mount
    if (initialRoot) {
        mountTree(initialRoot);
        // Automatically expand Level 1 and Level 2 so real distributor names are immediately visible!
        setTimeout(async () => {
            const rootBranch = panContainer.firstElementChild;
            if (rootBranch && rootBranch._childrenContainer) {
                if (rootBranch._childrenContainer.dataset.loaded !== 'true') {
                    await loadChildren(rootBranch, false);
                }
                const l1Branches = Array.from(rootBranch._childrenContainer.querySelectorAll(':scope > .org-child-slot > .org-tree-branch'));
                for (const branch of l1Branches) {
                    if (branch._childrenContainer && branch._childrenContainer.dataset.loaded !== 'true') {
                        await loadChildren(branch, false);
                    }
                }
                centerTree();
            }
        }, 80);
    }
})();
</script>
@endpush
