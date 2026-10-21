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
                        <optgroup label="👑 Company Master Positions (Aaradhya Gruh Udyog)">
                            @foreach($leaderOptions['company'] as $comp)
                                <option value="{{ $comp['id'] }}" @selected($activeId == $comp['id'])>
                                    {{ $comp['label'] }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                    @if(!empty($leaderOptions['leaders']))
                        <optgroup label="⭐ Real Distributor Leaders & Downline Networks">
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
                $topRootId = $roots->first()?->id ?? 747;
            @endphp
            <button type="button" class="quick-leader-pill px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ ($activeId == $topRootId) ? 'bg-orange-600 text-white border-orange-600 shadow-xs' : 'bg-orange-50/80 text-orange-950 border-orange-200 hover:bg-orange-100' }}" data-member-id="{{ $topRootId }}">
                👑 M1 Master Root (765 DL)
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
            @if($currentRootId && Route::has('admin.mlm.tree.move.form'))
                <a href="{{ admin_route('mlm.tree.move.form', $currentRootId) }}" class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-md hover:bg-amber-100 transition flex-shrink-0">
                    <i class="fas fa-arrows-up-down-left-right text-[10px]"></i> Move Member
                </a>
            @else
                <span class="text-slate-400 text-xs font-medium">Move Member</span>
            @endif
        </div>
    </div>

    <!-- Tree Notification Message -->
    <div id="treeMessage" class="hidden p-4 rounded-xl text-xs font-semibold"></div>

    <!-- Tree Canvas & Dual-View Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <!-- Top Tab Bar: Simple 1:3 Matrix View vs Full Multi-Tier Org Tree -->
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50/90 px-4 pt-3 pb-0">
            <div class="flex items-center gap-2">
                <button type="button" id="tabSimpleMatrix" class="px-4 py-2.5 text-xs font-bold border-b-2 border-orange-600 text-orange-700 bg-white rounded-t-lg transition flex items-center gap-2 shadow-2xs">
                    <i class="fas fa-cubes text-orange-600"></i>
                    <span>Simple 1:3 Matrix View (1 Parent &rarr; 3 Cards Only)</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[9px] font-extrabold bg-orange-100 text-orange-800">Default &bull; Easy</span>
                </button>
                <button type="button" id="tabOrgTree" class="px-4 py-2.5 text-xs font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 rounded-t-lg transition flex items-center gap-2">
                    <i class="fas fa-sitemap text-slate-400"></i>
                    <span>Full Multi-Tier Tree (Pan & Zoom)</span>
                </button>
            </div>
            <div class="hidden md:flex items-center gap-2 text-[11px] text-slate-500 pb-2">
                <span class="inline-flex items-center gap-1 font-bold text-slate-700">
                    <i class="fas fa-layer-group text-orange-500"></i> Strict 1:3 Matrix:
                </span>
                <span class="text-slate-500">Every card has at most 3 direct child cards (Left, Middle, Right)</span>
            </div>
        </div>

        <!-- ========================================================
             VIEW 1: SIMPLE 1:3 MATRIX VIEW (1 CARD -> 3 CARDS ONLY)
             ======================================================== -->
        <div id="simpleMatrixView" class="block">
            <!-- Navigation & Breadcrumb Trail -->
            <div class="bg-gradient-to-r from-orange-50/80 via-amber-50/30 to-orange-50/20 border-b border-orange-100 px-5 py-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-wrap text-xs">
                    <button type="button" id="btnMatrixUpOneLevel" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-orange-200 font-bold text-orange-700 hover:bg-orange-100 shadow-2xs transition disabled:opacity-40 disabled:cursor-not-allowed" disabled title="Go up to upline parent">
                        <i class="fas fa-arrow-up text-xs"></i> Go Up 1 Level
                    </button>
                    <button type="button" id="btnMatrixResetTop" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-white border border-slate-200 font-semibold text-slate-700 hover:bg-slate-100 shadow-2xs transition" title="Reset view to top root">
                        <i class="fas fa-crown text-amber-500"></i> Top Root (M1)
                    </button>
                    <span class="h-4 w-px bg-orange-200 mx-1"></span>
                    <span class="text-slate-500 font-semibold text-[11px]">Drill Path:</span>
                    <div id="matrixBreadcrumbTrail" class="inline-flex items-center gap-1.5 flex-wrap font-medium text-xs">
                        <!-- Populated by JS -->
                    </div>
                </div>
                <div class="text-[11px] font-bold text-orange-800 bg-white/90 border border-orange-200/90 px-3 py-1 rounded-full shadow-2xs flex items-center gap-1.5">
                    <i class="fas fa-check-circle text-emerald-600"></i>
                    <span>Clean 1:3 View: Under this card there are 3 cards only</span>
                </div>
            </div>

            <!-- Matrix Canvas Board -->
            <div class="p-6 sm:p-8 bg-slate-50/60 min-h-[520px] flex flex-col items-center">
                <!-- 1. Top Single Parent Card Container -->
                <div class="w-full max-w-md flex flex-col items-center">
                    <div class="text-[11px] font-black uppercase tracking-wider text-slate-500 mb-2.5 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-orange-500 inline-block ring-2 ring-orange-200"></span>
                        <span>Active Parent Member (1 Card)</span>
                    </div>
                    <div id="matrixParentCardContainer" class="w-full">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                <!-- 2. Visual Branch Connector Line -->
                <div class="relative w-full max-w-4xl flex flex-col items-center my-4">
                    <!-- Vertical line down from parent -->
                    <div class="w-0.5 h-6 bg-slate-400"></div>
                    <!-- Badge in center -->
                    <div class="z-10 bg-white px-4 py-1 rounded-full border border-slate-300 shadow-xs text-[11px] font-black text-slate-800 flex items-center gap-2">
                        <i class="fas fa-arrow-down text-orange-500 text-xs"></i>
                        <span>EXACTLY 3 DIRECT DOWNLINES BELOW (1:3 MATRIX)</span>
                        <i class="fas fa-arrow-down text-orange-500 text-xs"></i>
                    </div>
                    <!-- Horizontal branch bar with 3 drop lines -->
                    <div class="w-full grid grid-cols-3 relative mt-2.5">
                        <!-- Left Drop -->
                        <div class="flex flex-col items-center relative">
                            <div class="w-1/2 ml-auto h-0.5 bg-slate-400 rounded-tl"></div>
                            <div class="w-0.5 h-6 bg-slate-400"></div>
                        </div>
                        <!-- Middle Drop -->
                        <div class="flex flex-col items-center relative">
                            <div class="w-full h-0.5 bg-slate-400"></div>
                            <div class="w-0.5 h-6 bg-slate-400"></div>
                        </div>
                        <!-- Right Drop -->
                        <div class="flex flex-col items-center relative">
                            <div class="w-1/2 mr-auto h-0.5 bg-slate-400 rounded-tr"></div>
                            <div class="w-0.5 h-6 bg-slate-400"></div>
                        </div>
                    </div>
                </div>

                <!-- 3. Exactly 3 Child Cards Grid -->
                <div id="matrixChildrenGrid" class="w-full max-w-5xl grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Rendered by JS: Slot 1 (Left), Slot 2 (Middle), Slot 3 (Right) -->
                </div>
            </div>
        </div>

        <!-- ========================================================
             VIEW 2: FULL MULTI-TIER ORG TREE (PAN & ZOOM) [SECONDARY]
             ======================================================== -->
        <div id="orgTreeView" class="hidden">
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
                    <button type="button" id="btnExpandLevel3" class="px-2.5 py-1 text-[11px] font-bold text-orange-700 bg-orange-50 hover:bg-orange-100 rounded border border-orange-300 transition" title="Show Real Distributors (Levels 1 to 3)">
                        <i class="fas fa-users text-[10px] mr-0.5"></i> L3 (Distributors)
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
    const canManage = @json(auth()->user()->hasPermission('mlm.manage'));
    
    // Tab and Simple Matrix DOM Elements
    const tabSimpleMatrix = document.getElementById('tabSimpleMatrix');
    const tabOrgTree = document.getElementById('tabOrgTree');
    const simpleMatrixView = document.getElementById('simpleMatrixView');
    const orgTreeView = document.getElementById('orgTreeView');
    const matrixParentCardContainer = document.getElementById('matrixParentCardContainer');
    const matrixChildrenGrid = document.getElementById('matrixChildrenGrid');
    const matrixBreadcrumbTrail = document.getElementById('matrixBreadcrumbTrail');
    const btnMatrixUpOneLevel = document.getElementById('btnMatrixUpOneLevel');
    const btnMatrixResetTop = document.getElementById('btnMatrixResetTop');

    let activeTopRoot = initialRoot;
    let focusHistory = []; // Stack of { id, name, customer_id }
    let matrixParentMember = null;
    let matrixSlots = [];
    let matrixHistory = []; // Stack of { member, slots }
    let currentTab = 'simpleMatrix';
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

    // View Switcher (Simple 1:3 Matrix View vs Full Multi-Tier Org Tree)
    const switchTab = (tab) => {
        currentTab = tab;
        if (tab === 'simpleMatrix') {
            tabSimpleMatrix.className = 'px-4 py-2.5 text-xs font-bold border-b-2 border-orange-600 text-orange-700 bg-white rounded-t-lg transition flex items-center gap-2 shadow-2xs';
            tabOrgTree.className = 'px-4 py-2.5 text-xs font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 rounded-t-lg transition flex items-center gap-2';
            simpleMatrixView.classList.remove('hidden');
            orgTreeView.classList.add('hidden');
        } else {
            tabOrgTree.className = 'px-4 py-2.5 text-xs font-bold border-b-2 border-blue-600 text-blue-700 bg-white rounded-t-lg transition flex items-center gap-2 shadow-2xs';
            tabSimpleMatrix.className = 'px-4 py-2.5 text-xs font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 rounded-t-lg transition flex items-center gap-2';
            orgTreeView.classList.remove('hidden');
            simpleMatrixView.classList.add('hidden');
            setTimeout(centerTree, 60);
        }
    };

    tabSimpleMatrix?.addEventListener('click', () => switchTab('simpleMatrix'));
    tabOrgTree?.addEventListener('click', () => switchTab('orgTree'));

    // ========================================================
    // SIMPLE 1:3 MATRIX VIEW: RENDERERS & EVENT HANDLERS
    // ========================================================

    // Render Parent Card for Simple 1:3 Matrix View
    const renderMatrixParentCardHtml = (member) => {
        if (!member) return '';
        const isCompanyRoot = member.customer_id === 'C25070003';
        const isCompanyNode = Boolean(member.is_company_node);
        const initials = isCompanyRoot 
            ? '👑' 
            : (isCompanyNode ? '🏢' : ((member.name || 'M').split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()));
        const incomeFormatted = member.total_income_formatted || '₹0';
        const dlCount = Number(member.downline_count ?? 0);

        const companyBadge = member.company_label 
            ? `<div class="mt-1 px-2.5 py-0.5 rounded-lg bg-amber-50 border border-amber-200/80 text-amber-800 text-[10px] font-bold inline-flex items-center gap-1.5">
                 <i class="fas fa-crown text-[9px] text-amber-600"></i> <span>${escapeHtml(member.company_label)}</span>
               </div>`
            : '';

        const phoneHtml = member.phone 
            ? `<div class="flex items-center gap-1.5 text-xs text-slate-700 mt-1">
                 <i class="fas fa-phone-alt text-[10px] text-slate-400"></i>
                 <span class="font-mono font-bold">${escapeHtml(member.phone)}</span>
               </div>`
            : '';

        const sponsorText = member.sponsor 
            ? `${escapeHtml(member.sponsor.name)} (${escapeHtml(member.sponsor.customer_id)})`
            : 'Company Master Root';

        return `
            <div class="bg-white rounded-2xl border-2 border-orange-500 shadow-xl p-5 relative transition">
                <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-orange-500 to-amber-500 text-white font-black text-base flex items-center justify-center shadow-md shadow-orange-500/25 ring-2 ring-orange-200 flex-shrink-0">
                            ${initials}
                        </div>
                        <div>
                            <div class="text-[10px] font-black uppercase tracking-wider text-orange-600 flex items-center gap-1">
                                <i class="fas fa-crown"></i> Active Parent Card
                            </div>
                            <h2 class="text-base font-black text-slate-900 leading-tight tracking-tight">${escapeHtml(member.name)}</h2>
                            <div class="font-mono text-xs font-bold text-blue-600">${escapeHtml(member.customer_id)}</div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        ${statusBadge(member.status)}
                        <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">Level ${escapeHtml(member.level_label)}</span>
                    </div>
                </div>

                ${companyBadge ? `<div class="pt-2">${companyBadge}</div>` : ''}

                <div class="grid grid-cols-2 gap-2 mt-3 text-xs">
                    <div class="p-2.5 rounded-xl bg-orange-50/90 border border-orange-200">
                        <div class="text-[10px] text-orange-800 font-extrabold uppercase tracking-wider">Total Downlines</div>
                        <div class="text-base font-black text-orange-700 font-mono mt-0.5 flex items-center gap-1">
                            <i class="fas fa-users text-xs"></i> ${dlCount} Members
                        </div>
                    </div>
                    <div class="p-2.5 rounded-xl bg-emerald-50/90 border border-emerald-200">
                        <div class="text-[10px] text-emerald-800 font-extrabold uppercase tracking-wider">Commission Income</div>
                        <div class="text-base font-black text-emerald-700 font-mono mt-0.5">${escapeHtml(incomeFormatted)}</div>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-600 space-y-1">
                    ${phoneHtml}
                    <div class="flex items-center gap-1.5 truncate">
                        <i class="fas fa-user-tag text-[10px] text-slate-400"></i>
                        <span class="text-slate-400">Direct Sponsor:</span>
                        <span class="font-bold text-slate-800 truncate">${sponsorText}</span>
                    </div>
                    ${member.city ? `
                    <div class="flex items-center gap-1.5 truncate text-slate-500">
                        <i class="fas fa-map-marker-alt text-[10px] text-slate-400"></i>
                        <span>${escapeHtml(member.city)}, ${escapeHtml(member.state || 'Gujarat')}</span>
                    </div>` : ''}
                </div>

                <div class="mt-3.5 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <button type="button" class="btn-matrix-parent-drawer flex-1 py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-2xs">
                        <i class="fas fa-id-card text-slate-500"></i> Quick Profile Drawer
                    </button>
                    <a href="${escapeHtml(member.details_url || '#')}" class="py-2 px-3 bg-white hover:bg-orange-50 text-orange-600 font-bold border border-orange-200 rounded-xl text-xs transition flex items-center gap-1 shadow-2xs">
                        <span>Full Page</span> <i class="fas fa-external-link-alt text-[10px]"></i>
                    </a>
                </div>
            </div>
        `;
    };

    // Render Exactly 3 Child Cards for Simple 1:3 Matrix View
    const renderChildrenMatrixGridHtml = (slots) => {
        const slotConfigs = [
            { key: 'left', label: 'SLOT 1 OF 3: LEFT', border: 'border-sky-400', headerBg: 'bg-gradient-to-r from-sky-600 to-sky-500', badgeBg: 'bg-sky-50 text-sky-800 border-sky-200' },
            { key: 'middle', label: 'SLOT 2 OF 3: MIDDLE', border: 'border-amber-400', headerBg: 'bg-gradient-to-r from-amber-600 to-orange-500', badgeBg: 'bg-amber-50 text-amber-800 border-amber-200' },
            { key: 'right', label: 'SLOT 3 OF 3: RIGHT', border: 'border-emerald-400', headerBg: 'bg-gradient-to-r from-emerald-600 to-teal-600', badgeBg: 'bg-emerald-50 text-emerald-800 border-emerald-200' },
        ];

        return slotConfigs.map((cfg, index) => {
            const slotData = (slots || []).find(s => (s.position || '').toLowerCase() === cfg.key) 
                || (slots && slots[index] ? slots[index] : null);

            const member = slotData ? slotData.member : null;

            if (!member) {
                // Empty Slot Card
                return `
                    <div class="flex flex-col bg-white rounded-2xl border-2 border-dashed border-slate-300 p-6 items-center justify-center text-center shadow-xs transition hover:border-slate-400 min-h-[360px]">
                        <div class="w-full pb-3 border-b border-slate-100 mb-6">
                            <span class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border ${cfg.badgeBg}">
                                ${cfg.label}
                            </span>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl mb-3 border border-slate-200">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="font-black text-slate-700 text-sm">Available Slot</div>
                        <div class="text-xs text-slate-400 mt-1 max-w-[200px]">No member is placed in this direct slot yet.</div>
                        <div class="mt-6 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-[11px] font-semibold text-slate-500">
                            1 of 3 Matrix Placement
                        </div>
                    </div>
                `;
            }

            // Occupied Slot Card
            const initials = (member.name || 'M').split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
            const incomeFormatted = member.total_income_formatted || '₹0';
            const dlCount = Number(member.downline_count ?? 0);
            const sponsorText = member.sponsor 
                ? `${escapeHtml(member.sponsor.name)}`
                : 'Master Root';

            return `
                <div class="flex flex-col bg-white rounded-2xl border-2 ${cfg.border} shadow-md hover:shadow-xl transition overflow-hidden min-h-[360px]">
                    <!-- Slot Header Bar -->
                    <div class="${cfg.headerBg} text-white px-4 py-2.5 flex items-center justify-between text-xs font-black shadow-xs">
                        <span class="flex items-center gap-1.5 tracking-wide">
                            <i class="fas fa-sitemap text-[11px]"></i> ${cfg.label}
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-white/20 backdrop-blur-xs text-white">
                            Level ${escapeHtml(member.level_label)}
                        </span>
                    </div>

                    <!-- Card Content -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-slate-100 to-slate-200 text-slate-800 font-extrabold text-sm flex items-center justify-center border border-slate-300 flex-shrink-0">
                                        ${initials}
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-black text-slate-900 text-sm truncate leading-tight" title="${escapeHtml(member.name)}">${escapeHtml(member.name)}</h3>
                                        <div class="font-mono text-xs font-bold text-blue-600 truncate">${escapeHtml(member.customer_id)}</div>
                                    </div>
                                </div>
                                ${statusBadge(member.status)}
                            </div>

                            ${member.company_label ? `
                            <div class="mb-2.5 px-2 py-0.5 rounded bg-amber-50 border border-amber-200/80 text-amber-800 text-[10px] font-bold truncate">
                                <i class="fas fa-crown text-[9px] text-amber-600"></i> ${escapeHtml(member.company_label)}
                            </div>` : ''}

                            <!-- Stats Box -->
                            <div class="grid grid-cols-2 gap-2 my-3 text-center">
                                <div class="p-2 rounded-xl bg-indigo-50/90 border border-indigo-100">
                                    <div class="text-[9px] text-indigo-700 font-extrabold uppercase tracking-wider">Team Size</div>
                                    <div class="text-sm font-black text-indigo-900 font-mono mt-0.5">${dlCount} DL</div>
                                </div>
                                <div class="p-2 rounded-xl bg-emerald-50/90 border border-emerald-100">
                                    <div class="text-[9px] text-emerald-700 font-extrabold uppercase tracking-wider">Income</div>
                                    <div class="text-sm font-black text-emerald-800 font-mono mt-0.5">${escapeHtml(incomeFormatted)}</div>
                                </div>
                            </div>

                            <!-- Details Strip -->
                            <div class="space-y-1 text-[11px] text-slate-600 pt-2 border-t border-slate-100">
                                ${member.phone ? `
                                <div class="flex items-center gap-1.5 truncate">
                                    <i class="fas fa-phone-alt text-[9px] text-slate-400"></i>
                                    <span class="font-mono font-medium text-slate-700">${escapeHtml(member.phone)}</span>
                                </div>` : ''}
                                <div class="flex items-center justify-between text-[11px] pt-0.5 truncate">
                                    <span class="text-slate-400">Sponsor:</span>
                                    <span class="font-bold text-slate-700 truncate max-w-[140px]">${sponsorText}</span>
                                </div>
                                ${member.city ? `
                                <div class="flex items-center gap-1.5 text-slate-400 text-[10px] truncate">
                                    <i class="fas fa-map-marker-alt text-[9px]"></i>
                                    <span>${escapeHtml(member.city)}</span>
                                </div>` : ''}
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4 pt-3 border-t border-slate-100 space-y-2">
                            <button type="button" class="btn-matrix-step-down w-full py-2.5 px-3 bg-gradient-to-r from-orange-600 via-orange-500 to-amber-500 hover:from-orange-700 hover:to-amber-600 text-white font-extrabold rounded-xl text-xs shadow-md shadow-orange-500/20 active:scale-98 transition flex items-center justify-center gap-2" data-child-id="${member.id}">
                                <span>View 3 Downlines</span>
                                <i class="fas fa-arrow-right text-[11px]"></i>
                            </button>
                            <button type="button" class="btn-matrix-child-drawer w-full py-1.5 px-3 bg-slate-50 hover:bg-slate-100 text-slate-600 hover:text-slate-900 font-bold rounded-lg text-[11px] border border-slate-200 transition flex items-center justify-center gap-1" data-child-id="${member.id}">
                                <i class="fas fa-eye text-[10px] text-slate-400"></i> Quick Info
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    };

    // Render Breadcrumbs for Simple Matrix View
    const renderMatrixBreadcrumbs = () => {
        const upBtn = document.getElementById('btnMatrixUpOneLevel');
        const trail = document.getElementById('matrixBreadcrumbTrail');
        if (!trail) return;

        if (upBtn) {
            upBtn.disabled = (matrixHistory.length === 0);
        }

        if (!matrixHistory.length) {
            trail.innerHTML = `<span class="font-bold text-orange-900 bg-orange-100/90 px-2.5 py-0.5 rounded-full border border-orange-200">${escapeHtml(matrixParentMember?.name || 'Root')} (${escapeHtml(matrixParentMember?.customer_id || '')})</span>`;
            return;
        }

        trail.innerHTML = matrixHistory.map((item, index) => {
            return `<button type="button" class="text-orange-700 hover:text-orange-900 font-semibold underline underline-offset-2 hover:bg-orange-100/50 px-1.5 py-0.5 rounded transition" data-matrix-crumb-idx="${index}">${escapeHtml(item.member.name)}</button> <i class="fas fa-chevron-right text-[9px] text-orange-300"></i>`;
        }).join(' ') + ` <span class="font-bold text-orange-950 bg-orange-100 px-2 py-0.5 rounded-full border border-orange-300">${escapeHtml(matrixParentMember?.name || '')}</span>`;
    };

    // Main Simple Matrix View Entrypoint
    const renderSimpleMatrixView = (payload) => {
        if (!payload) return;
        matrixParentMember = payload.member || payload;
        matrixSlots = payload.slots || [];

        if (matrixParentCardContainer) {
            matrixParentCardContainer.innerHTML = renderMatrixParentCardHtml(matrixParentMember);
        }

        if (matrixChildrenGrid) {
            matrixChildrenGrid.innerHTML = renderChildrenMatrixGridHtml(matrixSlots);
        }

        renderMatrixBreadcrumbs();
    };

    // Simple Matrix Click Events: Step Down (drill into child) and Child Drawer
    matrixChildrenGrid?.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-matrix-step-down');
        if (btn) {
            const childId = Number(btn.dataset.childId);
            if (!childId) return;

            // Push current parent state to history stack
            matrixHistory.push({ member: matrixParentMember, slots: matrixSlots });

            try {
                const url = viewport.dataset.childrenUrl.replace('__MEMBER__', childId);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Failed to load downlines for this member.');
                const payload = await response.json();

                renderSimpleMatrixView({ member: payload.parent, slots: payload.slots });
                mountTree({ member: payload.parent, slots: payload.slots }, true);

                // Smooth scroll up to top of matrix view
                simpleMatrixView?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } catch (err) {
                showMessage(err.message || 'Error loading member.');
            }
            return;
        }

        const drawerBtn = e.target.closest('.btn-matrix-child-drawer');
        if (drawerBtn) {
            const childId = Number(drawerBtn.dataset.childId);
            const slot = matrixSlots.find(s => s.member && s.member.id === childId);
            if (slot && slot.member) {
                openDrawer(slot.member);
            }
        }
    });

    // Parent Card Drawer Button
    matrixParentCardContainer?.addEventListener('click', (e) => {
        if (e.target.closest('.btn-matrix-parent-drawer')) {
            if (matrixParentMember) openDrawer(matrixParentMember);
        }
    });

    // Up 1 Level Button
    btnMatrixUpOneLevel?.addEventListener('click', () => {
        if (!matrixHistory.length) return;
        const previous = matrixHistory.pop();
        renderSimpleMatrixView(previous);
        mountTree(previous, matrixHistory.length > 0);
        simpleMatrixView?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // Reset to Top Root Button
    btnMatrixResetTop?.addEventListener('click', () => {
        matrixHistory = [];
        renderSimpleMatrixView(activeTopRoot);
        mountTree(activeTopRoot, false);
        simpleMatrixView?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // Breadcrumbs Navigation Click
    matrixBreadcrumbTrail?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-matrix-crumb-idx]');
        if (!btn) return;
        const idx = Number(btn.dataset.matrixCrumbIdx);
        const target = matrixHistory[idx];
        matrixHistory = matrixHistory.slice(0, idx);
        renderSimpleMatrixView(target);
        mountTree(target, matrixHistory.length > 0);
        simpleMatrixView?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // Render an interactive Org Chart Node Card
    const renderNodeCard = (member, position = 'root') => {
        const card = document.createElement('div');
        const isCompanyRoot = member.customer_id === 'C25070003';
        const isCompanyNode = Boolean(member.is_company_node);
        const cardClass = isCompanyRoot 
            ? 'pos-company-root ring-2 ring-amber-400/50' 
            : (isCompanyNode ? 'pos-company-node' : posCardClass(position));

        card.className = `org-node-card ${cardClass} p-3.5`;
        card.dataset.nodeCard = 'true';
        card.dataset.nodeId = member.id;
        card._memberData = member;

        const initials = isCompanyRoot 
            ? '👑' 
            : (isCompanyNode ? '🏢' : ((member.name || 'M').split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()));
        const incomeFormatted = member.total_income_formatted || '₹0';
        const dlCount = Number(member.downline_count ?? 0);
        const dlBadge = `<span class="inline-flex items-center gap-1 font-bold text-[9px] text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-200" title="Total Network Downlines under this member"><i class="fas fa-users text-[8px]"></i> ${dlCount} DL</span>`;

        const avatarClass = isCompanyRoot
            ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-white shadow-xs border-amber-300'
            : (isCompanyNode 
                ? 'bg-gradient-to-tr from-slate-700 to-slate-900 text-white border-slate-600'
                : 'bg-gradient-to-tr from-blue-50 to-indigo-100 text-blue-700 border-indigo-200 font-extrabold');

        const companyBadge = member.company_label 
            ? `<div class="mt-1 mb-1 px-1.5 py-0.5 rounded bg-amber-50 border border-amber-200/80 text-amber-800 text-[9px] font-bold truncate flex items-center gap-1">
                 <i class="fas fa-crown text-[8px] text-amber-600"></i> <span>${escapeHtml(member.company_label)}</span>
               </div>`
            : '';

        const phoneHtml = member.phone 
            ? `<div class="flex items-center gap-1 text-[10px] text-slate-600 truncate mt-0.5">
                 <i class="fas fa-phone-alt text-[8px] text-slate-400"></i>
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

        card.innerHTML = `
            <!-- Box Content (for Detailed & Compact modes) -->
            <div class="card-box-content">
                <div class="flex items-center justify-between gap-1 mb-2">
                    <div class="flex items-center gap-1">
                        ${posBadge(position || member.placement_position)}
                        ${dlBadge}
                    </div>
                    ${statusBadge(member.status)}
                </div>

                <div class="flex items-center gap-2 mb-1.5">
                    <div class="card-avatar w-8 h-8 rounded-full ${avatarClass} text-xs flex items-center justify-center border border-slate-200 flex-shrink-0">
                        ${initials}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="card-name font-bold text-slate-900 text-xs truncate leading-snug" title="${escapeHtml(member.name)}">${escapeHtml(member.name)}</div>
                        <div class="font-mono text-[10px] font-bold text-blue-600 truncate">${escapeHtml(member.customer_id)}</div>
                        ${phoneHtml}
                    </div>
                </div>

                ${companyBadge}

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
                    <button type="button" class="btn-node-drawer px-2.5 py-1 font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-md transition flex items-center gap-1">
                        <i class="fas fa-eye text-[9px]"></i> Info
                    </button>
                    <button type="button" class="btn-node-focus px-2.5 py-1 font-semibold text-orange-700 bg-orange-50 hover:bg-orange-100 rounded-md transition flex items-center gap-1" title="Focus subtree under this member">
                        <i class="fas fa-crosshairs text-[9px]"></i> Focus
                    </button>
                </div>
            </div>

            <!-- Circle Inner Content (for Circle mode) -->
            <div class="circle-inner-avatar hidden">
                ${initials}
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
        const initials = (member.name || 'M').split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
        
        drawerBody.innerHTML = `
            <div class="text-center pb-5 border-b border-slate-100">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-tr from-orange-500 via-orange-600 to-amber-500 text-white text-xl font-black flex items-center justify-center shadow-md shadow-orange-500/25 ring-4 ring-orange-400/20 mb-3">
                    ${escapeHtml(initials)}
                </div>
                <div class="font-black text-slate-900 text-base tracking-tight">${escapeHtml(member.name)}</div>
                <div class="inline-flex items-center gap-1.5 mt-1 px-2.5 py-0.5 rounded-full bg-orange-50 border border-orange-200/80 font-mono text-xs font-bold text-orange-700 shadow-2xs">
                    <i class="fas fa-id-card text-[10px] text-orange-500"></i>
                    <span>${escapeHtml(member.customer_id)}</span>
                </div>
                <div class="flex items-center justify-center gap-1.5 mt-3 flex-wrap">
                    ${posBadge(member.position || member.placement_position)}
                    ${statusBadge(member.status)}
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        <i class="fas fa-shield-check text-emerald-600 text-[10px]"></i> KYC: ${escapeHtml(member.kyc_status || 'Approved')}
                    </span>
                </div>
            </div>

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

    // Event Delegation: Node Click, Expand Click & Drawer Click
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

        const card = event.target.closest('.org-node-card');
        if (card && card._memberData) {
            openDrawer(card._memberData);
        }
    });

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
                matrixHistory = [];
                activeTopRoot = { member: payload.parent, slots: payload.slots };
            }
            mountTree({ member: payload.parent, slots: payload.slots }, isSubtreeFocus);
            renderSimpleMatrixView({ member: payload.parent, slots: payload.slots });
            
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
        renderSimpleMatrixView(initialRoot);
        mountTree(initialRoot);
    }
})();
</script>
@endpush
