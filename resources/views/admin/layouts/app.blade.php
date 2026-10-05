@php
    $pendingKycCount = \App\Models\Member::whereIn('kyc_status', ['pending', 'under_review'])->count();
    $pendingPayoutsCount = \App\Models\MlmPayoutCycle::whereIn('status', ['pending_calculation', 'pending_approval'])->count();
    $totalNotifications = $pendingKycCount + $pendingPayoutsCount;
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Direct Selling Admin') - BachatGanga.Org</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                            950: '#431407',
                        },
                        blue: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                            950: '#431407',
                        },
                        navy: {
                            800: '#292524',
                            850: '#1c1917',
                            900: '#0c0a09',
                            950: '#060504',
                        }
                    },
                    boxShadow: {
                        'xs': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                        'card': '0 1px 3px 0 rgba(15, 23, 42, 0.08), 0 1px 2px -1px rgba(15, 23, 42, 0.04)',
                        'subtle': '0 4px 20px -2px rgba(15, 23, 42, 0.06)',
                    }
                }
            }
        }
    </script>
    
    <style>
        * {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: #0c0a09;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: #292524;
            border-radius: 4px;
        }

        /* Bootstrap Form / Utility Compatibility */
        .form-control, .form-select {
            display: block;
            width: 100%;
            padding: 0.5rem 0.75rem;
            font-size: 0.8125rem;
            font-weight: 400;
            line-height: 1.5;
            color: #1e293b;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-control:focus, .form-select:focus {
            border-color: #ea580c;
            outline: 0;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.18);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8125rem;
            font-weight: 600;
            padding: 0.5rem 0.875rem;
            border-radius: 0.5rem;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            gap: 0.375rem;
        }
        .btn-primary {
            background-color: #ea580c;
            color: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .btn-primary:hover {
            background-color: #c2410c;
            color: #ffffff;
        }
        .btn-outline-primary {
            background-color: #ffffff;
            color: #ea580c;
            border-color: #fed7aa;
        }
        .btn-outline-primary:hover {
            background-color: #fff7ed;
            border-color: #f97316;
            color: #c2410c;
        }
        .btn-outline-secondary {
            background-color: #ffffff;
            color: #475569;
            border-color: #e2e8f0;
        }
        .btn-outline-secondary:hover {
            background-color: #f8fafc;
            color: #0f172a;
            border-color: #cbd5e1;
        }
        .btn-outline-warning {
            background-color: #ffffff;
            color: #d97706;
            border-color: #fde68a;
        }
        .btn-outline-warning:hover {
            background-color: #fffbeb;
            color: #b45309;
        }
        .btn-success {
            background-color: #10b981;
            color: #ffffff;
        }
        .btn-success:hover {
            background-color: #059669;
        }
        .btn-sm {
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            border-radius: 0.375rem;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            line-height: 1;
        }
        .badge.bg-success { background-color: #dcfce7 !important; color: #15803d !important; border: 1px solid #bbf7d0; }
        .badge.bg-primary { background-color: #ffedd5 !important; color: #c2410c !important; border: 1px solid #fed7aa; }
        .badge.bg-warning { background-color: #fef3c7 !important; color: #b45309 !important; border: 1px solid #fde68a; }
        .badge.bg-danger { background-color: #fee2e2 !important; color: #b91c1c !important; border: 1px solid #fecaca; }
        .badge.bg-info { background-color: #e0f2fe !important; color: #0369a1 !important; border: 1px solid #bae6fd; }
        .badge.bg-secondary { background-color: #f1f5f9 !important; color: #475569 !important; border: 1px solid #e2e8f0; }
        .badge.bg-light { background-color: #f8fafc !important; color: #334155 !important; border: 1px solid #e2e8f0; }

        /* Robust Grid & Flex Compatibility */
        .row {
            display: flex !important;
            flex-wrap: wrap !important;
            margin-left: -0.5rem !important;
            margin-right: -0.5rem !important;
        }
        .row > * {
            box-sizing: border-box !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        .g-2 { margin-left: -0.25rem !important; margin-right: -0.25rem !important; }
        .g-2 > * { padding-left: 0.25rem !important; padding-right: 0.25rem !important; }
        .g-3 { margin-left: -0.5rem !important; margin-right: -0.5rem !important; }
        .g-3 > * { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        .g-4 { margin-left: -0.75rem !important; margin-right: -0.75rem !important; }
        .g-4 > * { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }

        .col-12 { flex: 0 0 100% !important; max-width: 100% !important; }
        .col-auto { flex: 0 0 auto !important; width: auto !important; max-width: 100% !important; }
        .col { flex: 1 0 0% !important; width: 100% !important; }

        @media (min-width: 768px) {
            .col-md-2 { flex: 0 0 16.666667% !important; max-width: 16.666667% !important; }
            .col-md-3 { flex: 0 0 25% !important; max-width: 25% !important; }
            .col-md-4 { flex: 0 0 33.333333% !important; max-width: 33.333333% !important; }
            .col-md-5 { flex: 0 0 41.666667% !important; max-width: 41.666667% !important; }
            .col-md-6 { flex: 0 0 50% !important; max-width: 50% !important; }
            .col-md-7 { flex: 0 0 58.333333% !important; max-width: 58.333333% !important; }
            .col-md-8 { flex: 0 0 66.666667% !important; max-width: 66.666667% !important; }
            .col-md-9 { flex: 0 0 75% !important; max-width: 75% !important; }
            .col-md-10 { flex: 0 0 83.333333% !important; max-width: 83.333333% !important; }
            .col-md-12 { flex: 0 0 100% !important; max-width: 100% !important; }
        }

        @media (min-width: 1200px) {
            .col-xl-3 { flex: 0 0 25% !important; max-width: 25% !important; }
            .col-xl-4 { flex: 0 0 33.333333% !important; max-width: 33.333333% !important; }
            .col-xl-6 { flex: 0 0 50% !important; max-width: 50% !important; }
            .col-xl-8 { flex: 0 0 66.666667% !important; max-width: 66.666667% !important; }
        }

        .d-flex { display: flex !important; }
        .d-inline-flex { display: inline-flex !important; }
        .d-none { display: none !important; }
        .align-items-center { align-items: center !important; }
        .align-items-baseline { align-items: baseline !important; }
        .align-items-start { align-items: flex-start !important; }
        .align-items-end { align-items: flex-end !important; }
        .justify-content-between { justify-content: space-between !important; }
        .justify-content-center { justify-content: center !important; }
        .justify-content-end { justify-content: flex-end !important; }
        .flex-grow-1 { flex-grow: 1 !important; }
        .flex-shrink-0 { flex-shrink: 0 !important; }
        .flex-wrap { flex-wrap: wrap !important; }

        .text-end { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-start { text-align: left !important; }
        .text-muted { color: #64748b !important; }
        .text-dark { color: #0f172a !important; }
        .text-primary { color: #ea580c !important; }
        .text-success { color: #16a34a !important; }
        .text-warning { color: #d97706 !important; }
        .text-danger { color: #dc2626 !important; }
        .text-info { color: #0284c7 !important; }
        .fw-bold { font-weight: 700 !important; }
        .fw-semibold { font-weight: 600 !important; }
        .fw-normal { font-weight: 400 !important; }
        .font-monospace { font-family: 'JetBrains Mono', monospace !important; }
        .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important; }
        .rounded { border-radius: 0.5rem !important; }
        .rounded-circle { border-radius: 9999px !important; }
        .rounded-pill { border-radius: 9999px !important; }

        /* Tables */
        .table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
        .table th, .table td { padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .table-hover tbody tr:hover { background-color: #f8fafc; }
        .table-light { background-color: #f8fafc; color: #475569; font-weight: 700; font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        /* Universal Tabs */
        .nav-tabs {
            display: flex !important;
            flex-wrap: wrap !important;
            border-bottom: 1px solid #e2e8f0 !important;
            gap: 0.25rem !important;
            list-style: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .nav-tabs .nav-link {
            display: inline-flex !important;
            align-items: center !important;
            padding: 0.75rem 1rem !important;
            font-size: 0.8125rem !important;
            font-weight: 600 !important;
            color: #64748b !important;
            border: none !important;
            border-bottom: 2px solid transparent !important;
            background: transparent !important;
            cursor: pointer !important;
            transition: all 0.15s ease-in-out !important;
        }
        .nav-tabs .nav-link:hover {
            color: #ea580c !important;
            border-bottom-color: #fed7aa !important;
        }
        .nav-tabs .nav-link.active {
            color: #ea580c !important;
            border-bottom-color: #ea580c !important;
            background: transparent !important;
        }
        .tab-content > .tab-pane {
            display: none;
        }
        .tab-content > .tab-pane.active,
        .tab-content > .tab-pane.show {
            display: block !important;
        }

        /* Dropdowns */
        .dropdown { position: relative; display: inline-block; }
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 50;
            display: none;
            min-width: 10rem;
            padding: 0.5rem 0;
            margin: 0.25rem 0 0;
            font-size: 0.8125rem;
            color: #1e293b;
            text-align: left;
            list-style: none;
            background-color: #ffffff;
            background-clip: padding-box;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        }
        .dropdown-menu.show { display: block !important; }
        .dropdown-item {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 0.5rem 1rem;
            clear: both;
            font-weight: 500;
            color: #334155;
            text-align: inherit;
            text-decoration: none;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
            cursor: pointer;
            transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out;
        }
        .dropdown-item:hover { background-color: #f1f5f9; color: #0f172a; }
        .dropdown-divider { height: 0; margin: 0.5rem 0; overflow: hidden; border-top: 1px solid #f1f5f9; }

        /* Universal Pagination */
        .pagination, ul.pagination {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 0.25rem !important;
            list-style: none !important;
            padding-left: 0 !important;
            margin: 0 !important;
        }
        .page-item, li.page-item {
            display: inline-flex !important;
            margin: 0 !important;
            padding: 0 !important;
            list-style: none !important;
        }
        .page-link, a.page-link, span.page-link {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 2rem !important;
            height: 2rem !important;
            padding: 0 0.5rem !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            color: #475569 !important;
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.5rem !important;
            text-decoration: none !important;
            transition: all 0.15s ease-in-out !important;
        }
        .page-item.active .page-link, li.page-item.active span.page-link, li.page-item.active a.page-link {
            background-color: #ea580c !important;
            border-color: #ea580c !important;
            color: #ffffff !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }
        .page-item.disabled .page-link, li.page-item.disabled span.page-link {
            color: #94a3b8 !important;
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
            cursor: not-allowed !important;
            opacity: 0.6 !important;
        }
        .page-link:hover:not(.active):not(.disabled) {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }
        nav[role="navigation"] {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            width: 100% !important;
            flex-wrap: wrap !important;
            gap: 0.75rem !important;
        }
        nav[role="navigation"] p {
            font-size: 0.75rem !important;
            color: #64748b !important;
            margin: 0 !important;
        }
        nav[role="navigation"] svg {
            width: 1rem !important;
            height: 1rem !important;
            display: inline-block !important;
        }

        /* Card System */
        .card {
            background-color: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }
        .card-header {
            padding: 1rem 1.25rem;
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 700;
            color: #0f172a;
        }
        .card-body {
            padding: 1.25rem;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full antialiased text-slate-800 bg-slate-50 flex overflow-hidden">

    <!-- ========================================================================= -->
    <!-- DESKTOP SIDEBAR -->
    <!-- ========================================================================= -->
    <aside class="w-64 bg-navy-900 border-r border-stone-800 flex-shrink-0 flex flex-col h-screen select-none z-30 transition-all duration-300">
        <!-- Brand Header -->
        <div class="h-16 px-5 flex items-center justify-between border-b border-stone-800/80 bg-navy-950/80">
            <a href="{{ admin_route('dashboard') }}" class="flex items-center gap-3 no-underline group">
                <div class="w-9 h-9 rounded-xl {{ \App\Helpers\AdminHelper::isSuperAdminPortal() ? 'bg-gradient-to-tr from-amber-500 to-orange-600 shadow-amber-500/25' : 'bg-gradient-to-tr from-purple-600 to-indigo-600 shadow-purple-500/25' }} flex items-center justify-center text-white font-black shadow-md group-hover:scale-105 transition">
                    <i class="fas {{ \App\Helpers\AdminHelper::isSuperAdminPortal() ? 'fa-crown' : 'fa-user-gear' }} text-sm"></i>
                </div>
                <div>
                    <div class="font-bold text-white text-base tracking-tight leading-none group-hover:text-orange-300 transition">BachatGanga<span class="{{ \App\Helpers\AdminHelper::isSuperAdminPortal() ? 'text-amber-400' : 'text-purple-400' }}">.Org</span></div>
                    <div class="text-[10px] font-bold tracking-wider uppercase mt-1 {{ \App\Helpers\AdminHelper::isSuperAdminPortal() ? 'text-amber-400' : 'text-purple-300' }}">
                        {{ \App\Helpers\AdminHelper::isSuperAdminPortal() ? '👑 Super Admin Master' : '👤 Sub-Admin Staff' }}
                    </div>
                </div>
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto sidebar-scroll px-3 py-4 space-y-5 text-xs font-medium">
            
            <!-- 1. DASHBOARD -->
            @if(auth()->user()->hasPermission('dashboard.view') || auth()->user()->isSuperAdmin())
            <div>
                <div class="px-3 pb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-400/80">Dashboard</div>
                <div class="space-y-0.5">
                    <a href="{{ admin_route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.dashboard') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-chart-line w-4 text-center text-sm {{ request()->routeIs('*.dashboard') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Direct Selling Hub</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- 2. MLM / DIRECT SELLING -->
            @if(auth()->user()->hasPermission('mlm.view') || auth()->user()->isSuperAdmin())
            <div>
                <div class="px-3 pb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-400/80">Direct Selling Network</div>
                <div class="space-y-0.5">
                    <a href="{{ admin_route('mlm.members.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.mlm.members.*') && !request()->routeIs('*.mlm.sponsor-network') && !request()->routeIs('*.mlm.genealogy') && !request()->routeIs('*.mlm.levels') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-users w-4 text-center text-sm {{ request()->routeIs('*.mlm.members.*') && !request()->routeIs('*.mlm.sponsor-network') && !request()->routeIs('*.mlm.genealogy') && !request()->routeIs('*.mlm.levels') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Members Directory</span>
                    </a>
                    <a href="{{ admin_route('mlm.sponsor-network') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.mlm.sponsor-network') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-user-friends w-4 text-center text-sm {{ request()->routeIs('*.mlm.sponsor-network') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Sponsor Network</span>
                    </a>
                    <a href="{{ admin_route('mlm.tree.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.mlm.tree.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-sitemap w-4 text-center text-sm {{ request()->routeIs('*.mlm.tree.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Placement Tree (1:3)</span>
                    </a>
                    <a href="{{ admin_route('mlm.genealogy') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.mlm.genealogy') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-project-diagram w-4 text-center text-sm {{ request()->routeIs('*.mlm.genealogy') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Genealogy (Levels 0–19)</span>
                    </a>
                    <a href="{{ admin_route('mlm.levels') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.mlm.levels') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-layer-group w-4 text-center text-sm {{ request()->routeIs('*.mlm.levels') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Levels & Rules</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- 3. MLM CALCULATIONS & SETTLEMENTS -->
            @if(auth()->user()->hasPermission('mlm.manage') || auth()->user()->isSuperAdmin())
            <div>
                <div class="px-3 pb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-400/80">Calculations & Settlements</div>
                <div class="space-y-0.5">
                    <a href="{{ admin_route('mlm.calculations.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.mlm.calculations.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-calculator w-4 text-center text-sm {{ request()->routeIs('*.mlm.calculations.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Calculation Engine</span>
                    </a>
                    <a href="{{ admin_route('mlm.reconciliation.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.mlm.reconciliation.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-check-double w-4 text-center text-sm {{ request()->routeIs('*.mlm.reconciliation.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Reconciliation</span>
                    </a>
                    <a href="{{ admin_route('mlm.payouts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.mlm.payouts.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-wallet w-4 text-center text-sm {{ request()->routeIs('*.mlm.payouts.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Weekly Settlements</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- 4. 100% CASHBACK -->
            @if(auth()->user()->hasPermission('cashback.view') || auth()->user()->isSuperAdmin())
            <div>
                <div class="px-3 pb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-400/80">100% Cashback</div>
                <div class="space-y-0.5">
                    <a href="{{ admin_route('cashback.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.cashback.index') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-hand-holding-dollar w-4 text-center text-sm {{ request()->routeIs('*.cashback.index') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Cashback Program</span>
                    </a>
                    <a href="{{ admin_route('cashback.reconciliation') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.cashback.reconciliation*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-clipboard-check w-4 text-center text-sm {{ request()->routeIs('*.cashback.reconciliation*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Reconciliation</span>
                    </a>
                    <a href="{{ admin_route('reports.cashback') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.reports.cashback') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-piggy-bank w-4 text-center text-sm {{ request()->routeIs('*.reports.cashback') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Profit Pools</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- 5. E-COMMERCE & STORE -->
            @if(auth()->user()->hasPermission('products.view') || auth()->user()->hasPermission('categories.view') || auth()->user()->hasPermission('inventory.view') || auth()->user()->hasPermission('orders.view') || auth()->user()->isSuperAdmin())
            <div>
                <div class="px-3 pb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-400/80">E-Commerce & Store</div>
                <div class="space-y-0.5">
                    @if(auth()->user()->hasPermission('products.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('products.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.products.*') && !request()->routeIs('*.products.create') && !request()->routeIs('*.products.bulkCreate') && !request()->routeIs('*.products.import') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-boxes-stacked w-4 text-center text-sm {{ request()->routeIs('*.products.*') && !request()->routeIs('*.products.create') && !request()->routeIs('*.products.bulkCreate') && !request()->routeIs('*.products.import') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Products Catalog</span>
                    </a>
                    @endif
                    @if(auth()->user()->hasPermission('products.create') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('products.create') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.products.create') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-plus-circle w-4 text-center text-sm {{ request()->routeIs('*.products.create') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Add New Product</span>
                    </a>
                    @endif
                    @if(auth()->user()->hasPermission('categories.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('categories.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.categories.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-folder-tree w-4 text-center text-sm {{ request()->routeIs('*.categories.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Categories</span>
                    </a>
                    @endif
                    @if(auth()->user()->hasPermission('brands.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('brands.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.brands.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-tags w-4 text-center text-sm {{ request()->routeIs('*.brands.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Brands</span>
                    </a>
                    @endif
                    @if(auth()->user()->hasPermission('attributes.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('attributes.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.attributes.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-sliders-h w-4 text-center text-sm {{ request()->routeIs('*.attributes.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Attributes & Variations</span>
                    </a>
                    @endif
                    @if(auth()->user()->hasPermission('inventory.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('inventory.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.inventory.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-warehouse w-4 text-center text-sm {{ request()->routeIs('*.inventory.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Stock & Inventory</span>
                    </a>
                    @endif
                    @if(auth()->user()->hasPermission('coupons.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('coupons.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.coupons.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-ticket w-4 text-center text-sm {{ request()->routeIs('*.coupons.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Coupons & Offers</span>
                    </a>
                    @endif
                    @if(auth()->user()->hasPermission('shipping.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('shipping.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.shipping.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-truck-fast w-4 text-center text-sm {{ request()->routeIs('*.shipping.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Shipping Methods</span>
                    </a>
                    @endif
                    @if(auth()->user()->hasPermission('taxes.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('taxes.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.taxes.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-receipt w-4 text-center text-sm {{ request()->routeIs('*.taxes.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Tax Rates (GST)</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- 6. REPORTING SUITE -->
            @if(auth()->user()->hasPermission('reports.view') || auth()->user()->isSuperAdmin())
            <div>
                <div class="px-3 pb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-400/80">Reporting Suite</div>
                <div class="space-y-0.5">
                    <a href="{{ admin_route('reports.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.reports.index') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-chart-pie w-4 text-center text-sm {{ request()->routeIs('*.reports.index') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Overview & Revenue</span>
                    </a>
                    <a href="{{ admin_route('reports.network') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.reports.network') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-network-wired w-4 text-center text-sm {{ request()->routeIs('*.reports.network') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Direct Selling Reports</span>
                    </a>
                    <a href="{{ admin_route('reports.profit') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.reports.profit') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-arrow-trend-up w-4 text-center text-sm {{ request()->routeIs('*.reports.profit') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Profit & Margins</span>
                    </a>
                    <a href="{{ admin_route('reports.gst') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.reports.gst') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-receipt w-4 text-center text-sm {{ request()->routeIs('*.reports.gst') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>GST Compliance</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- 7. MANAGEMENT & OPERATIONS -->
            <div>
                <div class="px-3 pb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-400/80">Operations & Admin</div>
                <div class="space-y-0.5">
                    @if(auth()->user()->hasPermission('customers.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('customers.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.customers.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-address-book w-4 text-center text-sm {{ request()->routeIs('*.customers.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Customers</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('orders.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('orders.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.orders.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-bag-shopping w-4 text-center text-sm {{ request()->routeIs('*.orders.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Orders & Payments</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('inquiries.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('inquiries.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.inquiries.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-comments w-4 text-center text-sm {{ request()->routeIs('*.inquiries.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Inquiries & Leads</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('woocommerce.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('woocommerce.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.woocommerce.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-rotate w-4 text-center text-sm {{ request()->routeIs('*.woocommerce.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>WooCommerce Sync</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('cms.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('banners.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.banners.*') || request()->routeIs('*.pages.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-file-lines w-4 text-center text-sm {{ request()->routeIs('*.banners.*') || request()->routeIs('*.pages.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>CMS Banners & Pages</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('users.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.users.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-user-shield w-4 text-center text-sm {{ request()->routeIs('*.users.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Sub-Admins & Staff</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('activity_logs.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('activity-logs.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.activity-logs.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-clock-rotate-left w-4 text-center text-sm {{ request()->routeIs('*.activity-logs.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Audit Logs</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('settings.view') || auth()->user()->isSuperAdmin())
                    <a href="{{ admin_route('settings.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition {{ request()->routeIs('*.settings.*') ? 'bg-blue-600/90 text-white font-semibold shadow-xs' : '' }}">
                        <i class="fas fa-sliders w-4 text-center text-sm {{ request()->routeIs('*.settings.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Platform Settings</span>
                    </a>
                    @endif
                </div>
            </div>
        </nav>

        <!-- Sidebar User Footer -->
        <div class="p-3 border-t border-stone-800/80 bg-navy-950/80 flex items-center justify-between">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-lg {{ \App\Helpers\AdminHelper::isSuperAdminPortal() ? 'bg-gradient-to-tr from-amber-600 to-orange-600 ring-amber-500/20' : 'bg-gradient-to-tr from-purple-600 to-indigo-600 ring-purple-500/20' }} flex items-center justify-center text-white text-xs font-bold ring-2 shadow-xs">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-semibold text-white truncate">{{ auth()->user()->name }}</div>
                    <div class="text-[10px] {{ \App\Helpers\AdminHelper::isSuperAdminPortal() ? 'text-amber-400' : 'text-purple-300' }} truncate">{{ auth()->user()->role_name ?? 'Super Admin' }}</div>
                </div>
            </div>
            <form action="{{ admin_route('logout') }}" method="POST">
                @csrf
                <button type="submit" title="Logout" class="w-7 h-7 rounded-md text-stone-400 hover:text-rose-400 hover:bg-rose-500/10 flex items-center justify-center transition">
                    <i class="fas fa-arrow-right-from-bracket text-xs"></i>
                </button>
            </form>
        </div>
    </aside>

    <!-- ========================================================================= -->
    <!-- MAIN CONTENT WRAPPER -->
    <!-- ========================================================================= -->
    <div class="flex-1 flex-col min-w-0 h-screen overflow-hidden flex">
        
        <!-- TOP APP BAR -->
        <header class="h-16 bg-white/95 backdrop-blur-sm border-b border-slate-200/80 px-6 flex items-center justify-between z-20 flex-shrink-0">
            <!-- Left: Breadcrumb / Path & Portal Indicator -->
            <div class="flex items-center gap-4">
                @if(\App\Helpers\AdminHelper::isSuperAdminPortal())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-900 border border-amber-300 shadow-2xs">
                        <i class="fas fa-crown text-amber-600"></i> SUPER ADMIN MASTER
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-900 border border-purple-300 shadow-2xs">
                        <i class="fas fa-user-gear text-purple-600"></i> SUB-ADMIN STAFF
                    </span>
                @endif

                <nav class="hidden md:flex items-center text-xs font-medium text-slate-500 gap-1.5">
                    <a href="{{ admin_route('dashboard') }}" class="text-slate-400 hover:text-slate-700 transition">
                        <i class="fas fa-house text-slate-400"></i>
                    </a>
                    <span>/</span>
                    @php
                        $routeName = request()->route() ? request()->route()->getName() : '';
                        $clean = str_replace(['super-admin.', 'sub-admin.', 'admin.'], '', $routeName);
                        $segments = explode('.', $clean);
                    @endphp
                    @foreach($segments as $idx => $seg)
                        <span class="{{ $idx === count($segments) - 1 ? 'text-slate-900 font-semibold capitalize' : 'text-slate-500 capitalize' }}">
                            {{ str_replace(['-', 'mlm'], [' ', 'Direct Selling'], $seg) }}
                        </span>
                        @if($idx < count($segments) - 1) <span>/</span> @endif
                    @endforeach
                </nav>
            </div>

            <!-- Right Controls -->
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 bg-slate-100/80 hover:bg-slate-200/70 rounded-lg transition">
                    <i class="fas fa-arrow-up-right-from-square text-[11px] text-slate-500"></i> View Platform
                </a>

                <!-- Quick Action Dropdown -->
                <div class="relative inline-block text-left" id="quickActionContainer">
                    <button type="button" onclick="document.getElementById('quickActionMenu').classList.toggle('hidden')" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 rounded-lg shadow-sm transition">
                        <i class="fas fa-plus text-xs"></i> Quick Action
                    </button>
                    <div id="quickActionMenu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-200/80 py-1.5 z-50 text-xs">
                        <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Direct Selling Actions</div>
                        <a href="{{ admin_route('mlm.members.create') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-orange-600 transition">
                            <i class="fas fa-user-plus text-orange-500 w-4"></i> Add Direct Selling Member
                        </a>
                        <a href="{{ admin_route('mlm.tree.index') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-orange-600 transition">
                            <i class="fas fa-sitemap text-amber-500 w-4"></i> Placement Tree (1:3)
                        </a>
                        <a href="{{ admin_route('mlm.calculations.index') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-orange-600 transition">
                            <i class="fas fa-calculator text-emerald-500 w-4"></i> Run Calculation Engine
                        </a>
                        <a href="{{ admin_route('mlm.payouts.index') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-orange-600 transition">
                            <i class="fas fa-wallet text-amber-500 w-4"></i> Weekly Settlement
                        </a>

                        <div class="my-1 border-t border-slate-100"></div>
                        <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Store & E-Commerce</div>
                        <a href="{{ admin_route('products.create') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-orange-600 transition">
                            <i class="fas fa-plus-circle text-emerald-500 w-4"></i> Add New Product
                        </a>
                        <a href="{{ admin_route('products.index') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-orange-600 transition">
                            <i class="fas fa-boxes-stacked text-orange-500 w-4"></i> Products Catalog
                        </a>
                        <a href="{{ admin_route('categories.index') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-orange-600 transition">
                            <i class="fas fa-folder-tree text-amber-500 w-4"></i> Manage Categories
                        </a>
                        <a href="{{ admin_route('inventory.index') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-orange-600 transition">
                            <i class="fas fa-warehouse text-purple-500 w-4"></i> Stock & Inventory
                        </a>
                    </div>
                </div>

                <!-- Notifications Dropdown -->
                <div class="relative inline-block text-left" id="notificationContainer">
                    <button type="button" onclick="document.getElementById('notificationMenu').classList.toggle('hidden')" class="relative w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-50 flex items-center justify-center transition">
                        <i class="far fa-bell text-sm"></i>
                        @if($totalNotifications > 0)
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-rose-500 text-white rounded-full text-[9px] font-bold flex items-center justify-center ring-2 ring-white">
                                {{ $totalNotifications }}
                            </span>
                        @endif
                    </button>
                    <div id="notificationMenu" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-slate-200/80 overflow-hidden z-50">
                        <div class="p-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900">Platform Alerts</span>
                            <span class="text-[10px] font-semibold bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">{{ $totalNotifications }} pending</span>
                        </div>
                        <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
                            @if($pendingKycCount > 0)
                                <a href="{{ admin_route('mlm.members.index', ['kyc_status' => 'pending']) }}" class="flex items-start gap-3 p-3 hover:bg-slate-50 transition no-underline">
                                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                                        <i class="fas fa-id-card"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-semibold text-slate-900">Pending KYC Verification</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">{{ $pendingKycCount }} member(s) waiting for KYC approval</div>
                                    </div>
                                </a>
                            @endif
                            @if($pendingPayoutsCount > 0)
                                <a href="{{ admin_route('mlm.payouts.index') }}" class="flex items-start gap-3 p-3 hover:bg-slate-50 transition no-underline">
                                    <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-semibold text-slate-900">Weekly Payout Cycles</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">{{ $pendingPayoutsCount }} cycle(s) pending calculation or approval</div>
                                    </div>
                                </a>
                            @endif
                            @if($totalNotifications === 0)
                                <div class="p-6 text-center text-slate-400">
                                    <i class="far fa-circle-check text-2xl text-emerald-500 mb-1 block"></i>
                                    <span class="text-xs">All clear! No pending alerts</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="h-5 w-px bg-slate-200"></div>

                <!-- Admin Profile Pill with Dropdown -->
                <div class="relative inline-block text-left" id="userProfileContainer">
                    <button type="button" onclick="document.getElementById('userProfileMenu').classList.toggle('hidden')" class="flex items-center gap-2 pl-1 p-1 rounded-xl hover:bg-slate-100 transition focus:outline-none">
                        <div class="w-8 h-8 rounded-lg {{ \App\Helpers\AdminHelper::isSuperAdminPortal() ? 'bg-gradient-to-tr from-amber-600 to-orange-600' : 'bg-gradient-to-tr from-purple-600 to-indigo-600' }} text-white font-bold flex items-center justify-center text-xs shadow-xs">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="hidden md:block text-left">
                            <div class="text-xs font-semibold text-slate-900 leading-tight">{{ auth()->user()->name }}</div>
                            <div class="text-[10px] {{ \App\Helpers\AdminHelper::isSuperAdminPortal() ? 'text-amber-600 font-bold' : 'text-purple-600 font-bold' }}">
                                {{ auth()->user()->isSuperAdmin() ? '👑 Super Admin' : '👤 Sub-Admin' }}
                            </div>
                        </div>
                        <i class="fas fa-chevron-down text-[10px] text-slate-400 ml-1"></i>
                    </button>
                    <div id="userProfileMenu" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-slate-200/90 py-2 z-50 text-xs divide-y divide-slate-100">
                        <div class="px-4 py-3 bg-slate-50/70 rounded-t-xl">
                            <div class="font-bold text-slate-900 text-sm">{{ auth()->user()->name }}</div>
                            <div class="text-[11px] text-slate-500 font-mono">{{ auth()->user()->email }}</div>
                            <div class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold {{ \App\Helpers\AdminHelper::isSuperAdminPortal() ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-purple-100 text-purple-800 border border-purple-300' }}">
                                <i class="fas {{ \App\Helpers\AdminHelper::isSuperAdminPortal() ? 'fa-crown' : 'fa-shield-halved' }}"></i>
                                {{ auth()->user()->isSuperAdmin() ? 'Master Super Administrator' : 'Restricted Sub-Admin Staff' }}
                            </div>
                        </div>

                        <div class="py-1">
                            @if(auth()->user()->isSuperAdmin())
                                <a href="{{ url('/super-admin/users') }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-slate-50 transition">
                                    <i class="fas fa-key text-amber-600 w-4"></i> Sub-Admin Credentials & Passwords
                                </a>
                                <a href="{{ url('/sub-admin/login') }}" class="flex items-center gap-2.5 px-4 py-2 text-purple-700 hover:bg-purple-50 transition">
                                    <i class="fas fa-arrow-right-arrow-left text-purple-600 w-4"></i> Switch to Sub-Admin View
                                </a>
                            @else
                                <a href="{{ url('/super-admin/login') }}" class="flex items-center gap-2.5 px-4 py-2 text-amber-700 hover:bg-amber-50 transition">
                                    <i class="fas fa-crown text-amber-600 w-4"></i> Log in to Super Admin Master
                                </a>
                            @endif
                        </div>

                        <div class="p-2">
                            <form action="{{ admin_route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-rose-700 bg-rose-50 hover:bg-rose-100 font-bold transition text-xs">
                                    <i class="fas fa-arrow-right-from-bracket"></i> Sign Out / Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN SCROLLABLE BODY -->
        <main class="flex-1 overflow-y-auto bg-slate-50/70 p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                @include('admin.partials.alert')
                @yield('content')
            </div>

            <!-- Footer -->
            <footer class="max-w-7xl mx-auto mt-12 pt-6 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-600">BachatGanga.Org</span>
                    <span>&mdash;</span>
                    <span class="italic font-medium text-amber-600">"तेरा तुझको अर्पण"</span>
                    <span>&bull;</span>
                    <span>&copy; {{ date('Y') }} All rights reserved.</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/60 text-[11px] font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Engine Active
                    </span>
                    <span class="font-mono text-[11px]" id="serverClock">{{ date('h:i:s A | d-M-Y') }}</span>
                    <span class="px-2 py-0.5 rounded bg-slate-200/70 text-slate-600 font-semibold text-[10px]">v2.0 Direct Selling</span>
                </div>
            </footer>
        </main>
    </div>

    <!-- Click outside handler for dropdowns -->
    <script>
        document.addEventListener('click', (e) => {
            const qaBtn = document.getElementById('quickActionContainer');
            const qaMenu = document.getElementById('quickActionMenu');
            if (qaBtn && qaMenu && !qaBtn.contains(e.target)) {
                qaMenu.classList.add('hidden');
            }

            const notifBtn = document.getElementById('notificationContainer');
            const notifMenu = document.getElementById('notificationMenu');
            if (notifBtn && notifMenu && !notifBtn.contains(e.target)) {
                notifMenu.classList.add('hidden');
            }

            const profileBtn = document.getElementById('userProfileContainer');
            const profileMenu = document.getElementById('userProfileMenu');
            if (profileBtn && profileMenu && !profileBtn.contains(e.target)) {
                profileMenu.classList.add('hidden');
            }
        });

        // Real-time server clock
        const clockEl = document.getElementById('serverClock');
        if (clockEl) {
            setInterval(() => {
                const now = new Date();
                clockEl.innerText = now.toLocaleTimeString() + ' | ' + now.toLocaleDateString();
            }, 1000);
        }
    </script>
    
    @stack('scripts')
</body>
</html>
