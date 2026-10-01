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
        width: 215px;
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
            <div class="lg:col-span-4">
                <label for="rootMember" class="block text-xs font-semibold text-slate-600 mb-1.5">Root Member</label>
                <select id="rootMember" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition">
                    @forelse($roots as $root)
                        <option value="{{ $root->id }}" @selected($initialRoot && (($initialRoot['member']['id'] ?? $initialRoot['id'] ?? null) === $root->id))>
                            {{ $root->customer_id }} &mdash; {{ $root->user?->name ?? 'Member' }}
                        </option>
                    @empty
                        <option value="">No root members available</option>
                    @endforelse
                </select>
            </div>

            <div class="lg:col-span-5 relative">
                <label for="treeMemberSearch" class="block text-xs font-semibold text-slate-600 mb-1.5">Search & Jump to Member</label>
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
                <div class="text-[11px] text-blue-800 mt-0.5">Supports 1:3 placement width up to Level 19 depth.</div>
            </div>
        </div>

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
        card.className = `org-node-card ${posCardClass(position)} p-3.5`;
        card.dataset.nodeCard = 'true';
        card.dataset.nodeId = member.id;
        card._memberData = member;

        const initials = (member.name || 'M').split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
        const incomeFormatted = member.total_income_formatted || '₹0';

        card.innerHTML = `
            <!-- Box Content (for Detailed & Compact modes) -->
            <div class="card-box-content">
                <div class="flex items-center justify-between gap-1.5 mb-2">
                    ${posBadge(position || member.placement_position)}
                    ${statusBadge(member.status)}
                </div>

                <div class="flex items-center gap-2 mb-2">
                    <div class="card-avatar w-8 h-8 rounded-full bg-slate-100 text-blue-700 font-extrabold text-xs flex items-center justify-center border border-slate-200 flex-shrink-0">
                        ${escapeHtml(initials)}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="card-name font-bold text-slate-900 text-xs truncate" title="${escapeHtml(member.name)}">${escapeHtml(member.name)}</div>
                        <div class="font-mono text-[10px] font-semibold text-blue-600 truncate">${escapeHtml(member.customer_id)}</div>
                    </div>
                </div>

                <!-- Detailed Card Meta -->
                <div class="card-full-meta text-[10px] text-slate-500 pt-2 border-t border-slate-100 space-y-1 mb-2.5">
                    <div class="flex justify-between items-center"><span class="font-medium text-slate-400">Level:</span> <span class="font-semibold text-slate-700">${escapeHtml(member.level_label)}</span></div>
                    <div class="flex justify-between items-center truncate" title="${escapeHtml(member.sponsor ? `${member.sponsor.customer_id} (${member.sponsor.name})` : 'None')}"><span class="font-medium text-slate-400">Sponsor:</span> <span class="font-semibold text-slate-700 truncate max-w-[110px]">${escapeHtml(member.sponsor ? member.sponsor.customer_id : 'Root')}</span></div>
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
                    <button type="button" class="btn-node-drawer px-2 py-0.5 font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded transition flex items-center gap-1">
                        <i class="fas fa-eye text-[9px]"></i> Info
                    </button>
                    <button type="button" class="btn-node-focus px-2 py-0.5 font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded transition flex items-center gap-1" title="Focus subtree under this member">
                        <i class="fas fa-crosshairs text-[9px]"></i> Focus
                    </button>
                </div>
            </div>

            <!-- Circle Inner Content (for Circle mode) -->
            <div class="circle-inner-avatar hidden">
                ${escapeHtml(initials)}
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
                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Matrix Children</div>
                    <div class="text-lg font-black text-emerald-600 mt-0.5">${member.has_children ? 'Active 1:3' : 'Available'}</div>
                </div>
            </div>

            <div class="space-y-2.5 pt-2 border-t border-slate-100">
                <div class="p-3 rounded-xl bg-orange-50/60 border border-orange-200/70">
                    <div class="text-[10px] font-extrabold text-orange-900 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fas fa-user-tag text-orange-600"></i> Direct Sponsor
                    </div>
                    <div class="font-bold text-slate-900 mt-1">${escapeHtml(member.sponsor ? member.sponsor.name : 'Root Leader / None')}</div>
                    <div class="font-mono text-orange-700 text-[11px] font-semibold mt-0.5">${escapeHtml(member.sponsor ? member.sponsor.customer_id : '')}</div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div class="text-[10px] font-extrabold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fas fa-sitemap text-slate-500"></i> Placement Parent
                    </div>
                    <div class="font-bold text-slate-900 mt-1">${escapeHtml(member.placement_parent ? member.placement_parent.name : 'Matrix Root')}</div>
                    <div class="font-mono text-slate-600 text-[11px] font-semibold mt-0.5">${escapeHtml(member.placement_parent ? member.placement_parent.customer_id : '')}</div>
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

    // Root Selector Change
    rootSelect?.addEventListener('change', () => {
        const selectedId = Number(rootSelect.value);
        const rootPayload = rootPayloads.find((item) => {
            const mId = item.member ? item.member.id : item.id;
            return mId === selectedId;
        });
        activeTopRoot = rootPayload || null;
        focusHistory = [];
        mountTree(activeTopRoot);
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
                return `
                <div class="p-3 hover:bg-blue-50/60 cursor-pointer border-b border-slate-100 last:border-0 transition" data-result-id="${member.id}">
                    <div class="font-bold text-slate-900">${escapeHtml(member.name)}</div>
                    <div class="font-mono text-blue-600 text-[11px]">${escapeHtml(member.customer_id)} &bull; ${escapeHtml(member.position_label || 'Member')}</div>
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
        const matched = searchCache.find((entry) => {
            const m = entry.member || entry;
            return m.id === memberId;
        });

        if (!matched) return;

        try {
            const rootId = matched.root_id || (matched.path && matched.path[0]) || memberId;
            const path = matched.path || [memberId];

            if (rootSelect && String(rootSelect.value) !== String(rootId)) {
                rootSelect.value = String(rootId);
                const rootPayload = rootPayloads.find((p) => {
                    const mId = p.member ? p.member.id : p.id;
                    return mId === rootId;
                });
                activeTopRoot = rootPayload || null;
                focusHistory = [];
                mountTree(activeTopRoot);
            }

            if (path.length) {
                await openPath(path);
            }

            const targetBranch = findBranchInDom(memberId);
            if (targetBranch) {
                document.querySelectorAll('.tree-highlight').forEach((el) => el.classList.remove('tree-highlight'));
                targetBranch._card.classList.add('tree-highlight');
                targetBranch._card.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
            }
        } catch (error) {
            showMessage(error.message || 'Error locating member in hierarchy.');
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
    }
})();
</script>
@endpush
