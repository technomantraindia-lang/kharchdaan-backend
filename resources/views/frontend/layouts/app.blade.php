<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'KharchDaan.Com') - "तेरा तुझको अर्पण" | Direct Selling & 100% Cashback Platform</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
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
                        },
                        gold: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    },
                    boxShadow: {
                        'xs': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                        'card': '0 1px 3px 0 rgba(15, 23, 42, 0.08), 0 1px 2px -1px rgba(15, 23, 42, 0.04)',
                        'glow': '0 0 25px -5px rgba(234, 88, 12, 0.4)',
                        'gold-glow': '0 0 25px -5px rgba(245, 158, 11, 0.35)',
                    }
                }
            }
        }
    </script>

    <style>
        * {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }
        
        html {
            scroll-behavior: smooth;
        }

        /* Form & Component Compatibility Bridge */
        .form-control, .form-select {
            display: block;
            width: 100%;
            padding: 0.55rem 0.85rem;
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.5;
            color: #0f172a;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            transition: all 0.15s ease-in-out;
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
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.55rem 1.15rem;
            border-radius: 0.5rem;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            text-decoration: none;
            gap: 0.5rem;
        }
        .btn-primary {
            background-color: #ea580c;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #c2410c;
        }
        .btn-outline-primary {
            background-color: #ffffff;
            color: #ea580c;
            border: 1px solid #fed7aa;
        }
        .btn-outline-primary:hover {
            background-color: #fff7ed;
            border-color: #ea580c;
        }
        .btn-warning {
            background-color: #f59e0b;
            color: #0f172a;
        }
        .btn-warning:hover {
            background-color: #d97706;
            color: #ffffff;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            line-height: 1;
        }
        .badge.bg-success { background-color: #dcfce7 !important; color: #15803d !important; }
        .badge.bg-primary { background-color: #ffedd5 !important; color: #c2410c !important; }
        .badge.bg-warning { background-color: #fef3c7 !important; color: #b45309 !important; }
        .badge.bg-danger { background-color: #fee2e2 !important; color: #b91c1c !important; }
        .badge.bg-light { background-color: #f1f5f9 !important; color: #334155 !important; }
    </style>
    @stack('styles')
</head>
<body class="flex flex-col min-h-screen bg-slate-50/50 text-slate-800 antialiased selection:bg-orange-600 selection:text-white">

    <!-- Top Announcement Bar (Clean Orange & White Trust Ribbon) -->
    <div class="bg-gradient-to-r from-orange-50 via-amber-50/50 to-orange-50 text-slate-700 text-xs py-1.5 px-4 border-b border-orange-200/60 shadow-2xs">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
            <div class="flex items-center gap-2.5 flex-wrap justify-center sm:justify-start">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-gradient-to-r from-orange-600 via-orange-500 to-amber-500 text-white shadow-xs tracking-wide">
                    <i class="fas fa-sparkles text-[9px] text-amber-200"></i> "तेरा तुझको अर्पण"
                </span>
                <span class="text-slate-700 font-semibold text-[11px] sm:text-xs">
                    India's Leading Direct Selling & 100% Conditional Profit Cashback Engine
                </span>
            </div>
            <div class="flex items-center gap-3 text-[11px] text-slate-600 font-medium">
                <span class="flex items-center gap-1.5">
                    <i class="fas fa-shield-halved text-emerald-600 text-xs"></i> AES-256 KYC
                </span>
                <span class="text-orange-300">•</span>
                <span class="flex items-center gap-1.5">
                    <i class="fas fa-clock-rotate-left text-orange-600 text-xs"></i> Weekly Payouts
                </span>
                <span class="hidden md:inline text-orange-300">•</span>
                <span class="hidden md:flex items-center gap-1.5 text-orange-700 font-bold">
                    <i class="fas fa-certificate text-amber-500 text-xs"></i> 100% Transparent
                </span>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar (Ultra-Premium Orange & White Header) -->
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-xl border-b border-orange-100 shadow-[0_4px_25px_-5px_rgba(234,88,12,0.07)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-18 py-3">
                
                <!-- Logo & Brand Tagline -->
                <a href="{{ route('home') }}" class="flex items-center gap-3 group text-decoration-none flex-shrink-0">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-500 via-orange-600 to-amber-600 p-0.5 shadow-md shadow-orange-500/25 ring-2 ring-orange-400/20 group-hover:scale-105 group-hover:shadow-orange-500/40 transition-all duration-300 flex items-center justify-center text-white">
                        <div class="w-full h-full rounded-[14px] bg-gradient-to-tr from-orange-600 to-amber-500 flex items-center justify-center">
                            <i class="fas fa-layer-group text-lg text-white drop-shadow-sm"></i>
                        </div>
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none group-hover:text-orange-600 transition duration-200">
                            KharchDaan<span class="text-orange-600">.Com</span>
                        </div>
                        <div class="flex items-center gap-1.5 mt-1">
                            <span class="text-[10px] sm:text-[11px] font-black text-orange-600 tracking-wider">
                                "तेरा तुझको अर्पण"
                            </span>
                            <span class="w-1 h-1 rounded-full bg-amber-500 inline-block"></span>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider hidden xl:inline">Direct Selling</span>
                        </div>
                    </div>
                </a>

                <!-- Desktop Navigation Links (Spacious, Perfectly Aligned) -->
                <nav class="hidden lg:flex items-center gap-1 xl:gap-2 text-xs xl:text-sm font-semibold">
                    <a href="{{ route('home') }}" class="px-3 py-2 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap {{ request()->routeIs('home') ? 'text-orange-600 bg-orange-50 font-bold border border-orange-200 shadow-2xs' : 'text-slate-700 hover:text-orange-600 hover:bg-orange-50/60' }}">
                        <i class="fas fa-house-chimney text-xs {{ request()->routeIs('home') ? 'text-orange-600' : 'text-slate-400' }}"></i>
                        <span>Home</span>
                    </a>
                    
                    <a href="{{ route('products.index') }}" class="px-3 py-2 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap {{ request()->routeIs('products.*') ? 'text-orange-600 bg-orange-50 font-bold border border-orange-200 shadow-2xs' : 'text-slate-700 hover:text-orange-600 hover:bg-orange-50/60' }}">
                        <i class="fas fa-boxes-stacked text-xs {{ request()->routeIs('products.*') ? 'text-orange-600' : 'text-slate-400' }}"></i>
                        <span>Store Catalog</span>
                        <span class="px-1.5 py-0.2 rounded-full bg-orange-100 text-orange-700 text-[9px] font-black uppercase">HOT</span>
                    </a>
                    
                    <a href="{{ route('home') }}#cashback-section" class="px-3 py-2 rounded-xl text-slate-700 hover:text-orange-600 hover:bg-orange-50/60 transition inline-flex items-center gap-1.5 whitespace-nowrap">
                        <i class="fas fa-hand-holding-dollar text-xs text-amber-500"></i>
                        <span>100% Cashback</span>
                        <span class="px-1.5 py-0.2 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-extrabold">100%</span>
                    </a>
                    
                    <a href="{{ route('home') }}#matrix-section" class="px-3 py-2 rounded-xl text-slate-700 hover:text-orange-600 hover:bg-orange-50/60 transition inline-flex items-center gap-1.5 whitespace-nowrap">
                        <i class="fas fa-sitemap text-xs text-orange-500"></i>
                        <span>1:3 Matrix Plan</span>
                    </a>
                </nav>

                <!-- Auth & Action Controls -->
                <div class="flex items-center gap-2 sm:gap-2.5 flex-shrink-0">
                    
                    <!-- Shopping Cart Pill -->
                    <a href="{{ route('cart.index') }}" class="relative inline-flex items-center gap-1.5 px-3 py-2 rounded-xl transition text-xs font-bold whitespace-nowrap {{ request()->routeIs('cart.*') ? 'bg-orange-600 text-white shadow-md shadow-orange-500/25' : 'bg-orange-50 hover:bg-orange-100/80 text-orange-700 border border-orange-200/80' }}" title="Shopping Cart">
                        <i class="fas fa-bag-shopping text-sm"></i>
                        <span class="hidden sm:inline">Cart</span>
                        @php
                            $cartCount = count(session('cart', []));
                        @endphp
                        <span class="inline-flex items-center justify-center px-1.5 py-0.2 text-[10px] font-black rounded-full {{ request()->routeIs('cart.*') ? 'bg-white text-orange-600' : 'bg-orange-600 text-white' }}">
                            {{ $cartCount }}
                        </span>
                    </a>

                    @auth
                        <div class="hidden sm:flex items-center gap-2">
                            <a href="{{ route('account') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-800 bg-orange-50/70 hover:bg-orange-100 border border-orange-200 rounded-xl transition whitespace-nowrap" title="My Account">
                                <div class="w-5 h-5 rounded-lg bg-gradient-to-tr from-orange-600 to-amber-500 text-white flex items-center justify-center text-[10px] font-black shadow-2xs">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                                <span class="max-w-[100px] truncate">{{ auth()->user()->name }}</span>
                            </a>
                            <a href="{{ route('network.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-white bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 rounded-xl shadow-xs transition whitespace-nowrap">
                                <i class="fas fa-sitemap text-xs"></i>
                                <span>My Network</span>
                            </a>
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" title="Logout" class="w-8 h-8 rounded-xl bg-slate-50 hover:bg-rose-50 text-slate-400 hover:text-rose-600 border border-slate-200 flex items-center justify-center transition">
                                    <i class="fas fa-arrow-right-from-bracket text-xs"></i>
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="hidden sm:flex items-center gap-2">
                            <a href="{{ route('login') }}" class="px-3 py-2 text-xs font-bold text-slate-700 hover:text-orange-600 bg-slate-50 hover:bg-orange-50 border border-slate-200 hover:border-orange-200 rounded-xl transition shadow-2xs whitespace-nowrap">
                                <i class="fas fa-right-to-bracket mr-1 text-slate-400"></i> Member Login
                            </a>
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-extrabold text-white bg-gradient-to-r from-orange-600 via-orange-500 to-amber-500 hover:from-orange-700 hover:to-amber-600 rounded-xl shadow-md shadow-orange-500/25 hover:shadow-orange-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 whitespace-nowrap">
                                <i class="fas fa-user-plus text-xs"></i> Join Network
                            </a>
                        </div>
                    @endauth

                    <a href="{{ route('admin.login') }}" class="inline-flex items-center gap-1 px-2.5 py-2 text-xs font-semibold text-slate-500 hover:text-orange-600 bg-white hover:bg-orange-50/50 border border-slate-200 rounded-xl transition whitespace-nowrap" title="Staff & Admin Portal">
                        <i class="fas fa-lock text-slate-400 text-xs"></i>
                        <span class="hidden xl:inline">Admin</span>
                    </a>

                    <!-- Mobile Menu Toggle Button -->
                    <button type="button" onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="lg:hidden w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 flex items-center justify-center hover:text-orange-600 hover:bg-orange-50 transition">
                        <i class="fas fa-bars text-sm"></i>
                    </button>

                </div>

            </div>
        </div>

        <!-- Mobile Drawer Menu -->
        <div id="mobileMenu" class="hidden lg:hidden border-t border-orange-100 bg-white px-4 py-4 space-y-2.5 shadow-xl">
            <div class="px-2 py-1 mb-2">
                <span class="text-[11px] font-black text-orange-600">"तेरा तुझको अर्पण"</span>
                <span class="text-xs text-slate-500"> &mdash; Direct Selling Ecosystem</span>
            </div>
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-slate-700 hover:bg-orange-50 hover:text-orange-600 text-sm font-semibold {{ request()->routeIs('home') ? 'bg-orange-50 text-orange-600 font-bold border border-orange-200' : '' }}">
                <i class="fas fa-house-chimney text-xs text-orange-500"></i> Home
            </a>
            <a href="{{ route('products.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-700 hover:bg-orange-50 hover:text-orange-600 text-sm font-semibold {{ request()->routeIs('products.*') ? 'bg-orange-50 text-orange-600 font-bold border border-orange-200' : '' }}">
                <div class="flex items-center gap-2.5">
                    <i class="fas fa-boxes-stacked text-xs text-orange-500"></i> Store Products
                </div>
                <span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 text-[10px] font-black">HOT</span>
            </a>
            <a href="{{ route('home') }}#cashback-section" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-700 hover:bg-orange-50 hover:text-orange-600 text-sm font-semibold">
                <div class="flex items-center gap-2.5">
                    <i class="fas fa-hand-holding-dollar text-xs text-amber-500"></i> 100% Cashback Program
                </div>
                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-black">100%</span>
            </a>
            <a href="{{ route('home') }}#matrix-section" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-slate-700 hover:bg-orange-50 hover:text-orange-600 text-sm font-semibold">
                <i class="fas fa-sitemap text-xs text-orange-500"></i> 1:3 Placement Matrix
            </a>
            <a href="{{ route('cart.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-700 hover:bg-orange-50 hover:text-orange-600 text-sm font-semibold {{ request()->routeIs('cart.*') ? 'bg-orange-50 text-orange-600 font-bold border border-orange-200' : '' }}">
                <div class="flex items-center gap-2.5">
                    <i class="fas fa-bag-shopping text-xs text-orange-500"></i> Shopping Cart
                </div>
                <span class="px-2 py-0.5 rounded-full bg-orange-600 text-white text-[10px] font-black">{{ $cartCount }}</span>
            </a>
            
            <div class="pt-3 border-t border-slate-100 flex flex-col gap-2">
                @auth
                    <a href="{{ route('account') }}" class="block px-3 py-2.5 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 text-white text-center text-xs font-bold shadow-xs">My Account Dashboard</a>
                    <a href="{{ route('network.index') }}" class="block px-3 py-2.5 rounded-xl bg-amber-500 text-slate-900 text-center text-xs font-bold">My Direct Selling Network</a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-3 py-2 rounded-xl bg-slate-100 text-slate-700 text-center text-xs font-bold border border-slate-200">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block px-3 py-2.5 rounded-xl bg-slate-100 text-slate-800 text-center text-xs font-bold border border-slate-200">Member Login</a>
                    <a href="{{ route('register') }}" class="block px-3 py-2.5 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 text-white text-center text-xs font-bold shadow-xs">Join Direct Selling Network</a>
                @endauth
                <a href="{{ route('admin.login') }}" class="block px-3 py-2 rounded-xl bg-slate-50 text-slate-600 border border-slate-200 text-center text-xs font-semibold">Admin Panel Access</a>
            </div>
        </div>
    </header>

    <!-- Global Flash Notifications -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-3">
                    <i class="fas fa-circle-check text-emerald-600 text-lg"></i>
                    <span class="text-sm font-semibold">{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-3">
                    <i class="fas fa-circle-exclamation text-rose-600 text-lg"></i>
                    <span class="text-sm font-semibold">{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    <!-- Main Content Container -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-navy-950 text-slate-400 pt-16 pb-12 border-t border-stone-850 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 pb-12 border-b border-stone-800/80">
                
                <!-- Col 1: Brand Info (5 cols) -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-orange-600 to-amber-500 flex items-center justify-center text-white shadow-glow">
                            <i class="fas fa-layer-group text-lg"></i>
                        </div>
                        <div>
                            <div class="text-xl font-extrabold text-white tracking-tight">KharchDaan<span class="text-orange-500">.Com</span></div>
                            <div class="text-xs font-semibold text-amber-400">"तेरा तुझको अर्पण"</div>
                        </div>
                    </div>
                    <p class="text-sm text-slate-400 leading-relaxed max-w-sm">
                        KharchDaan.Com is India's next-generation Direct Selling & 100% Conditional Profit Cashback ecosystem. Built with absolute transparency, balanced 1:3 placement matrix hierarchy, and automated weekly direct settlements.
                    </p>
                    <div class="flex items-center gap-3 pt-2">
                        <span class="w-8 h-8 rounded-lg bg-stone-900 border border-stone-800 flex items-center justify-center text-slate-400 hover:text-orange-400 hover:border-orange-500/40 transition cursor-pointer"><i class="fab fa-facebook-f text-xs"></i></span>
                        <span class="w-8 h-8 rounded-lg bg-stone-900 border border-stone-800 flex items-center justify-center text-slate-400 hover:text-orange-400 hover:border-orange-500/40 transition cursor-pointer"><i class="fab fa-x-twitter text-xs"></i></span>
                        <span class="w-8 h-8 rounded-lg bg-stone-900 border border-stone-800 flex items-center justify-center text-slate-400 hover:text-orange-400 hover:border-orange-500/40 transition cursor-pointer"><i class="fab fa-instagram text-xs"></i></span>
                        <span class="w-8 h-8 rounded-lg bg-stone-900 border border-stone-800 flex items-center justify-center text-slate-400 hover:text-orange-400 hover:border-orange-500/40 transition cursor-pointer"><i class="fab fa-youtube text-xs"></i></span>
                    </div>
                </div>

                <!-- Col 2: Store & Catalog (2 cols) -->
                <div class="lg:col-span-2 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white">Store & Shop</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('products.index') }}" class="hover:text-orange-400 transition">All Products</a></li>
                        <li><a href="{{ route('products.index') }}?featured=1" class="hover:text-orange-400 transition">Featured Packs</a></li>
                        <li><a href="{{ route('products.index') }}" class="hover:text-orange-400 transition">Starter Kits</a></li>
                        <li><a href="{{ route('cart.index') }}" class="hover:text-orange-400 transition">Shopping Cart</a></li>
                    </ul>
                </div>

                <!-- Col 3: Direct Selling & Member Portal (2 cols) -->
                <div class="lg:col-span-2 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white">Member Portal</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('login') }}" class="hover:text-orange-400 transition">Member Login</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-orange-400 transition">Join Direct Selling</a></li>
                        <li><a href="{{ route('home') }}#cashback-section" class="hover:text-orange-400 transition">100% Cashback Rules</a></li>
                        <li><a href="{{ route('home') }}#matrix-section" class="hover:text-orange-400 transition">1:3 Placement Matrix</a></li>
                        <li><a href="{{ route('admin.login') }}" class="hover:text-orange-400 transition">Admin Portal</a></li>
                    </ul>
                </div>

                <!-- Col 4: Trust & Compliance (3 cols) -->
                <div class="lg:col-span-3 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white">Security & Transparency</h4>
                    <div class="space-y-2.5 text-xs text-slate-400">
                        <div class="flex items-start gap-2.5">
                            <i class="fas fa-sitemap text-orange-400 mt-0.5"></i>
                            <span><strong>1:3 Matrix Architecture:</strong> 20 levels of automated PV distribution with zero collision.</span>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <i class="fas fa-shield-halved text-emerald-400 mt-0.5"></i>
                            <span><strong>AES-256 KYC Security:</strong> Bank accounts and government IDs encrypted with HMAC verification.</span>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <i class="fas fa-receipt text-amber-400 mt-0.5"></i>
                            <span><strong>GST Compliant:</strong> Authentic tax invoices and UTR bank settlement records.</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Copyright & Tagline -->
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <div>
                    &copy; {{ date('Y') }} <strong class="text-slate-300">KharchDaan.Com</strong>. All rights reserved.
                </div>
                <div class="text-amber-400/90 font-semibold text-center sm:text-right">
                    "तेरा तुझको अर्पण" &mdash; Direct Selling & 100% Profit Cashback Management Engine
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
