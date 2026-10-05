@extends('frontend.layouts.app')

@section('title', 'Direct Selling & Up to 100% Cashback Platform')

@section('content')
<div class="space-y-24 pb-20">

    <!-- ========================================================================= -->
    <!-- HERO SLIDER SECTION (3 Dynamic & Interactive Sliding Banners) -->
    <!-- ========================================================================= -->
    <section class="relative bg-gradient-to-b from-orange-50/70 via-stone-50/40 to-white border-b border-stone-200/70 overflow-hidden" id="heroSliderContainer">
        <!-- Subtle ambient background accents -->
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-orange-400/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/4 w-96 h-96 bg-amber-400/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Slider Wrapper -->
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-20 z-10 min-h-[580px] flex items-center">
            
            <!-- SLIDE 1: Direct Selling & 1:3 Placement Matrix -->
            <div class="hero-slide active-slide grid grid-cols-1 lg:grid-cols-12 gap-12 items-center w-full transition-opacity duration-700 ease-in-out" data-slide="0">
                <!-- Slide 1 Left: Content -->
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-orange-100/80 text-orange-800 border border-orange-200 shadow-2xs">
                        <i class="fas fa-sitemap text-orange-600 text-xs"></i>
                        <span>"तेरा तुझको अर्पण" &mdash; 1:3 Placement Matrix</span>
                    </div>

                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-slate-900 tracking-tight leading-tight">
                        Next-Gen <span class="text-orange-600">Direct Selling</span> & <span class="text-amber-500">Up to 100% Cashback</span>
                    </h1>

                    <div class="text-xl sm:text-2xl font-bold text-orange-600 tracking-wide">
                        "तेरा तुझको अर्पण"
                    </div>

                    <p class="text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                        A transparent Direct Selling ecosystem built with a structured 1:3 placement matrix across 20 levels, automated weekly direct bank settlements, and 100% conditional company profit cashback.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3.5 pt-2">
                        <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-6 py-3.5 text-sm font-bold text-white bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 rounded-xl shadow-md shadow-orange-500/20 hover:shadow-orange-500/35 transition duration-200">
                            <i class="fas fa-bag-shopping"></i> Explore Store Products
                        </a>
                        
                        @auth
                            <a href="{{ route('network.index') }}" class="inline-flex items-center gap-2 px-6 py-3.5 text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-300 rounded-xl transition duration-200">
                                <i class="fas fa-sitemap"></i> My Direct Selling Tree
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3.5 text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-300 rounded-xl shadow-xs transition duration-200">
                                <i class="fas fa-user-plus"></i> Join Direct Selling
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-5 py-3.5 text-sm font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl transition shadow-2xs">
                                <i class="fas fa-right-to-bracket text-slate-400"></i> Member Login
                            </a>
                        @endauth
                    </div>

                    <!-- Trust highlights -->
                    <div class="pt-4 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs text-slate-500 font-medium">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-circle-check text-emerald-500"></i>
                            <span>1:3 Ternary Matrix</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-circle-check text-emerald-500"></i>
                            <span>20 Income Levels</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-circle-check text-emerald-500"></i>
                            <span>Zero Hidden Clauses</span>
                        </div>
                    </div>
                </div>

                <!-- Slide 1 Right: Visual Card -->
                <div class="lg:col-span-5">
                    <div class="bg-white border border-stone-200/90 rounded-3xl p-6 sm:p-8 shadow-xl shadow-orange-500/5 space-y-6">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 border border-orange-200/80 flex items-center justify-center">
                                    <i class="fas fa-sitemap text-base"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-900">Direct Selling Engine</div>
                                    <div class="text-[11px] text-slate-400">1:3 Matrix Architecture</div>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span> Active
                            </span>
                        </div>

                        <div class="space-y-3.5">
                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/60 flex items-start gap-3.5">
                                <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-700 flex items-center justify-center flex-shrink-0 text-sm mt-0.5">
                                    <i class="fas fa-network-wired"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">1:3 Placement Matrix (Levels 0–19)</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">Left, Middle, & Right slot allocation with strict hierarchy isolation.</div>
                                </div>
                            </div>

                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/60 flex items-start gap-3.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0 text-sm mt-0.5">
                                    <i class="fas fa-hand-holding-dollar"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Up to 100% Conditional Profit Cashback</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">"तेरा तुझको अर्पण" — up to 100% cashback returned upon company declared profits.</div>
                                </div>
                            </div>

                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/60 flex items-start gap-3.5">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center flex-shrink-0 text-sm mt-0.5">
                                    <i class="fas fa-clock-rotate-left"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Automated Weekly Settlements</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">Transparent Monday-to-Sunday calculation cycles with bank UTR proof.</div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 text-xs font-bold text-white bg-stone-900 hover:bg-orange-600 rounded-xl transition shadow-xs">
                                <i class="fas fa-tachometer-alt"></i> Access Member Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 2: Up to 100% Cashback Program ("तेरा तुझको अर्पण") -->
            <div class="hero-slide hidden-slide grid grid-cols-1 lg:grid-cols-12 gap-12 items-center w-full transition-opacity duration-700 ease-in-out" data-slide="1">
                <!-- Slide 2 Left: Content -->
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 shadow-2xs">
                        <i class="fas fa-hand-holding-dollar text-emerald-600 text-xs"></i>
                        <span>"तेरा तुझको अर्पण" &mdash; Profit Pools</span>
                    </div>

                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-slate-900 tracking-tight leading-tight">
                        Shop Products & Earn <span class="text-emerald-600">Up to 100% Cashback</span>
                    </h1>

                    <div class="text-xl sm:text-2xl font-bold text-amber-500 tracking-wide">
                        "तेरा तुझको अर्पण"
                    </div>

                    <p class="text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                        Every qualifying product purchase participates in transparent company profit pool batches. When profits are declared, up to 100% of qualifying cashback is disbursed directly to your bank account.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3.5 pt-2">
                        <a href="#cashback-section" class="inline-flex items-center gap-2 px-6 py-3.5 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition duration-200">
                            <i class="fas fa-piggy-bank"></i> How Up to 100% Cashback Works
                        </a>
                        <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-5 py-3.5 text-sm font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl transition shadow-2xs">
                            <i class="fas fa-tag text-emerald-600"></i> Browse Cashback Products
                        </a>
                    </div>

                    <!-- Trust highlights -->
                    <div class="pt-4 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs text-slate-500 font-medium">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-circle-check text-emerald-500"></i>
                            <span>100% Profit Sharing Pools</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-circle-check text-emerald-500"></i>
                            <span>Bank UTR Proof Records</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-circle-check text-emerald-500"></i>
                            <span>Zero Hidden Deductions</span>
                        </div>
                    </div>
                </div>

                <!-- Slide 2 Right: Visual Card -->
                <div class="lg:col-span-5">
                    <div class="bg-white border border-emerald-200/80 rounded-3xl p-6 sm:p-8 shadow-xl shadow-emerald-100/60 space-y-6">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200/80 flex items-center justify-center">
                                    <i class="fas fa-piggy-bank text-base"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-900">100% Profit Pool Engine</div>
                                    <div class="text-[11px] text-slate-400">"तेरा तुझको अर्पण" Model</div>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                <i class="fas fa-check-double text-[9px] mr-1"></i> Audited
                            </span>
                        </div>

                        <div class="space-y-3.5">
                            <div class="bg-emerald-50/60 rounded-2xl p-4 border border-emerald-100 flex items-start gap-3.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 text-sm mt-0.5">
                                    <i class="fas fa-cart-shopping"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Step 1: Qualifying Purchase</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">Shop any product or package tagged with up to 100% cashback eligibility.</div>
                                </div>
                            </div>

                            <div class="bg-emerald-50/60 rounded-2xl p-4 border border-emerald-100 flex items-start gap-3.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 text-sm mt-0.5">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Step 2: Transparent Batch Queue</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">Transactions are batched and tracked in real-time on your dashboard.</div>
                                </div>
                            </div>

                            <div class="bg-emerald-50/60 rounded-2xl p-4 border border-emerald-100 flex items-start gap-3.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 text-sm mt-0.5">
                                    <i class="fas fa-building-columns"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Step 3: Direct Bank Disbursement</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">Up to 100% cashback returned upon company declared profits with bank UTR proof.</div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <a href="{{ route('register') }}" class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition shadow-xs">
                                <i class="fas fa-arrow-right"></i> Register for Cashback Account
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 3: Store & Starter Packages -->
            <div class="hero-slide hidden-slide grid grid-cols-1 lg:grid-cols-12 gap-12 items-center w-full transition-opacity duration-700 ease-in-out" data-slide="2">
                <!-- Slide 3 Left: Content -->
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-orange-50 text-orange-700 border border-orange-200/80 shadow-2xs">
                        <i class="fas fa-box-open text-orange-600 text-xs"></i>
                        <span>Direct Selling Starter Kits & Wellness</span>
                    </div>

                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-slate-900 tracking-tight leading-tight">
                        Premium Store & <span class="text-orange-600">Starter Packages</span>
                    </h1>

                    <div class="text-xl sm:text-2xl font-bold text-amber-500 tracking-wide">
                        "तेरा तुझको अर्पण"
                    </div>

                    <p class="text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                        Order high-quality daily essentials, wellness items, and direct selling starter packs. Fast shipping, 100% GST compliance, and instant Point Volume (PV) calculation.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3.5 pt-2">
                        <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-6 py-3.5 text-sm font-bold text-white bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 rounded-xl shadow-xs transition duration-200">
                            <i class="fas fa-boxes-stacked"></i> Shop Store Catalog
                        </a>
                        <a href="{{ route('cart.index') }}" class="inline-flex items-center gap-2 px-5 py-3.5 text-sm font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl transition shadow-2xs">
                            <i class="fas fa-bag-shopping text-orange-600"></i> View Shopping Cart
                        </a>
                    </div>

                    <!-- Trust highlights -->
                    <div class="pt-4 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs text-slate-500 font-medium">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-circle-check text-emerald-500"></i>
                            <span>Authentic GST Invoices</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-circle-check text-emerald-500"></i>
                            <span>Fast Doorstep Delivery</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-circle-check text-emerald-500"></i>
                            <span>Integrated PV Point Credits</span>
                        </div>
                    </div>
                </div>

                <!-- Slide 3 Right: Visual Card -->
                <div class="lg:col-span-5">
                    <div class="bg-white border border-stone-200/90 rounded-3xl p-6 sm:p-8 shadow-xl shadow-orange-500/5 space-y-6">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 border border-orange-200/80 flex items-center justify-center">
                                    <i class="fas fa-boxes-stacked text-base"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-900">Direct Selling Starter Kits</div>
                                    <div class="text-[11px] text-slate-400">Integrated Point Volume (PV)</div>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                <i class="fas fa-star text-amber-500 text-[9px] mr-1"></i> Top Rated
                            </span>
                        </div>

                        <div class="space-y-3.5">
                            <div class="bg-orange-50/60 rounded-2xl p-4 border border-orange-100 flex items-start gap-3.5">
                                <div class="w-8 h-8 rounded-lg bg-orange-600 text-white flex items-center justify-center flex-shrink-0 text-sm mt-0.5">
                                    <i class="fas fa-cubes"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Complete Starter Kit Packages</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">Curated bundles to activate your Direct Selling placement position immediately.</div>
                                </div>
                            </div>

                            <div class="bg-orange-50/60 rounded-2xl p-4 border border-orange-100 flex items-start gap-3.5">
                                <div class="w-8 h-8 rounded-lg bg-orange-600 text-white flex items-center justify-center flex-shrink-0 text-sm mt-0.5">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">High Point Volume (PV) Allocation</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">Earn High PV Rate (13.5) on Levels 0–7 and Low PV Rate (0.75) on Levels 8–19.</div>
                                </div>
                            </div>

                            <div class="bg-orange-50/60 rounded-2xl p-4 border border-orange-100 flex items-start gap-3.5">
                                <div class="w-8 h-8 rounded-lg bg-orange-600 text-white flex items-center justify-center flex-shrink-0 text-sm mt-0.5">
                                    <i class="fas fa-truck-fast"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Direct Express Shipping</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">Reliable dispatch across India with tracking updates.</div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <a href="{{ route('products.index') }}" class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 text-xs font-bold text-white bg-stone-900 hover:bg-orange-600 rounded-xl transition shadow-xs">
                                <i class="fas fa-shopping-bag"></i> Browse Starter Packages
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Carousel Left & Right Controls -->
        <button type="button" id="prevSlideBtn" class="absolute left-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/90 hover:bg-white border border-slate-200/80 text-slate-700 hover:text-orange-600 shadow-md flex items-center justify-center transition z-20 focus:outline-none" aria-label="Previous Slide">
            <i class="fas fa-chevron-left text-sm"></i>
        </button>
        <button type="button" id="nextSlideBtn" class="absolute right-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/90 hover:bg-white border border-slate-200/80 text-slate-700 hover:text-orange-600 shadow-md flex items-center justify-center transition z-20 focus:outline-none" aria-label="Next Slide">
            <i class="fas fa-chevron-right text-sm"></i>
        </button>

        <!-- Carousel Pagination Dots -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20">
            <button type="button" class="slide-dot w-8 h-2.5 rounded-full bg-orange-600 transition-all duration-300" data-dot="0" aria-label="Slide 1"></button>
            <button type="button" class="slide-dot w-2.5 h-2.5 rounded-full bg-slate-300 hover:bg-slate-400 transition-all duration-300" data-dot="1" aria-label="Slide 2"></button>
            <button type="button" class="slide-dot w-2.5 h-2.5 rounded-full bg-slate-300 hover:bg-slate-400 transition-all duration-300" data-dot="2" aria-label="Slide 3"></button>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- LIVE STATS COUNTER RIBBON -->
    <!-- ========================================================================= -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex items-center gap-4 hover:border-orange-200 transition">
                <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <div class="text-2xl font-black text-slate-900">{{ number_format($stats['members']) }}+</div>
                    <div class="text-xs font-semibold text-slate-500">Registered Members</div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex items-center gap-4 hover:border-amber-200 transition">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <div class="text-2xl font-black text-slate-900">20 Levels</div>
                    <div class="text-xs font-semibold text-slate-500">Placement Matrix Depth</div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex items-center gap-4 hover:border-emerald-200 transition">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fas fa-hand-holding-dollar"></i>
                </div>
                <div>
                    <div class="text-2xl font-black text-emerald-600">100%</div>
                    <div class="text-xs font-semibold text-slate-500">Profit Cashback Program</div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex items-center gap-4 hover:border-orange-200 transition">
                <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <div class="text-2xl font-black text-slate-900">Weekly</div>
                    <div class="text-xs font-semibold text-slate-500">Direct Bank Settlements</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- STORE PRODUCTS CATALOG SECTION -->
    <!-- ========================================================================= -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-orange-50 text-orange-700 border border-orange-200 mb-2">
                    <i class="fas fa-boxes-stacked text-xs"></i> E-Commerce Store
                </div>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    Featured Products & Starter Packages
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Authentic products eligible for Direct Selling Point Volume (PV) and 100% Profit Cashback.
                </p>
            </div>
            <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-orange-600 hover:text-orange-700 hover:underline">
                View All Products in Catalog <i class="fas fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse($featuredProducts as $product)
            <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs hover:shadow-lg hover:-translate-y-1 transition duration-200 flex flex-col group">
                <!-- Thumbnail -->
                <div class="relative bg-slate-50 aspect-square p-4 flex items-center justify-center overflow-hidden border-b border-slate-100">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="max-h-48 w-auto object-contain group-hover:scale-105 transition duration-300" onerror="this.onerror=null;this.src='{{ asset('images/default-product.svg') }}';">
                    
                    @if($product->sale_price && $product->sale_price < $product->price)
                        @php
                            $discount = round((($product->price - $product->sale_price) / $product->price) * 100);
                        @endphp
                        <span class="absolute top-3 left-3 bg-amber-500 text-slate-900 font-bold text-[10px] px-2 py-0.5 rounded-md shadow-xs">
                            {{ $discount }}% OFF
                        </span>
                    @endif

                    @if($product->featured)
                        <span class="absolute top-3 right-3 bg-orange-600 text-white font-bold text-[10px] px-2 py-0.5 rounded-md shadow-xs">
                            Featured
                        </span>
                    @endif
                </div>

                <!-- Product Body -->
                <div class="p-5 flex flex-col flex-grow">
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <span class="text-[11px] font-semibold text-orange-600 uppercase tracking-wider">
                            {{ $product->category?->name ?? 'Store Product' }}
                        </span>
                        <span class="text-[10px] font-mono text-slate-400">SKU: {{ $product->sku }}</span>
                    </div>

                    <h3 class="font-bold text-slate-900 text-sm leading-snug line-clamp-2 hover:text-orange-600 transition mb-2">
                        <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                    </h3>

                    <p class="text-xs text-slate-500 line-clamp-2 mb-4 flex-grow">
                        {{ $product->short_desc ?? 'Premium quality direct selling product with full GST compliance and cashback eligibility.' }}
                    </p>

                    <!-- Price & Cart Actions -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2 mt-auto">
                        <div>
                            <div class="text-lg font-black text-slate-900 leading-none">
                                ₹{{ number_format(round($product->display_price)) }}
                            </div>
                            @if($product->sale_price && $product->sale_price < $product->price)
                                <div class="text-[11px] text-slate-400 line-through mt-0.5">
                                    ₹{{ number_format(round($product->price)) }}
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-1.5">
                            <form action="{{ route('cart.add', $product) }}" method="POST">
                                @csrf
                                <button type="submit" title="Add to Cart" class="w-9 h-9 rounded-xl bg-orange-50 hover:bg-orange-600 text-orange-600 hover:text-white border border-orange-200 hover:border-orange-600 flex items-center justify-center transition">
                                    <i class="fas fa-cart-plus text-xs"></i>
                                </button>
                            </form>
                            <a href="{{ route('products.show', $product->slug) }}" class="px-3 py-2 text-xs font-bold text-white bg-stone-900 hover:bg-orange-600 rounded-xl transition">
                                View
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-4 py-12 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
                <i class="fas fa-box-open text-4xl mb-3 text-slate-300 block"></i>
                <p class="font-semibold text-slate-600">Products are being updated</p>
                <p class="text-xs text-slate-400 mt-1">Please check back shortly or browse through our categories.</p>
            </div>
            @endforelse
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- UP TO 100% CASHBACK PROGRAM SPOTLIGHT ("तेरा तुझको अर्पण") -->
    <!-- ========================================================================= -->
    <section id="cashback-section" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-3xl border border-slate-200/90 p-8 sm:p-12 shadow-xs">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-7 space-y-5">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-orange-50 text-orange-700 border border-orange-200">
                        <i class="fas fa-hand-holding-dollar text-xs"></i> "तेरा तुझको अर्पण"
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        Up to 100% Conditional Profit Cashback Program
                    </h2>

                    <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
                        At BachatGanga.Org, we believe platform value belongs to the community. Through our <strong>"तेरा तुझको अर्पण"</strong> philosophy, qualifying member transactions are placed into transparent company profit pool batches.
                    </p>

                    <!-- Feature List -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4">
                            <div class="text-amber-600 font-bold text-sm flex items-center gap-2 mb-1">
                                <i class="fas fa-piggy-bank"></i> Profit Pool Batches
                            </div>
                            <div class="text-xs text-slate-500">Cashback allocated transparently when company profits are declared.</div>
                        </div>

                        <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4">
                            <div class="text-orange-600 font-bold text-sm flex items-center gap-2 mb-1">
                                <i class="fas fa-clipboard-check"></i> Audit Reconciliation
                            </div>
                            <div class="text-xs text-slate-500">Complete ledger traceability with zero hidden terms or deductions.</div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 text-xs font-bold text-white bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 rounded-xl shadow-xs transition">
                            <i class="fas fa-arrow-right"></i> Start Earning Cashback Today
                        </a>
                    </div>
                </div>

                <!-- Right Visual -->
                <div class="lg:col-span-5">
                    <div class="bg-slate-50 rounded-2xl border border-slate-200/80 p-6 space-y-4">
                        <div class="text-xs font-bold text-slate-700 uppercase tracking-wider">How Up to 100% Cashback Works</div>
                        
                        <div class="space-y-3 text-xs">
                            <div class="flex items-start gap-3 bg-white p-3 rounded-xl border border-slate-200/60">
                                <div class="w-6 h-6 rounded-full bg-orange-600 text-white font-bold flex items-center justify-center flex-shrink-0 text-[11px]">1</div>
                                <div>
                                    <strong class="text-slate-900">Shop Qualifying Products:</strong> Purchase items tagged with up to 100% cashback eligibility.
                                </div>
                            </div>
                            <div class="flex items-start gap-3 bg-white p-3 rounded-xl border border-slate-200/60">
                                <div class="w-6 h-6 rounded-full bg-orange-600 text-white font-bold flex items-center justify-center flex-shrink-0 text-[11px]">2</div>
                                <div>
                                    <strong class="text-slate-900">Enter Profit Pool:</strong> Transaction is queued into the declared company profit pool batch.
                                </div>
                            </div>
                            <div class="flex items-start gap-3 bg-white p-3 rounded-xl border border-slate-200/60">
                                <div class="w-6 h-6 rounded-full bg-orange-600 text-white font-bold flex items-center justify-center flex-shrink-0 text-[11px]">3</div>
                                <div>
                                    <strong class="text-slate-900">Declared Profit Allocation:</strong> Once audited, up to 100% cashback is approved.
                                </div>
                            </div>
                            <div class="flex items-start gap-3 bg-white p-3 rounded-xl border border-slate-200/60">
                                <div class="w-6 h-6 rounded-full bg-emerald-600 text-white font-bold flex items-center justify-center flex-shrink-0 text-[11px]">4</div>
                                <div>
                                    <strong class="text-slate-900">Direct Bank Settlement:</strong> Amount is disbursed directly to your bank account with UTR proof.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 1:3 PLACEMENT MATRIX & 20 LEVELS EXPLAINER -->
    <!-- ========================================================================= -->
    <section id="matrix-section" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-orange-50 text-orange-700 border border-orange-200 mb-2">
                <i class="fas fa-sitemap text-xs"></i> Direct Selling Architecture
            </div>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                1:3 Placement Matrix & 20 Income Levels
            </h2>
            <p class="text-sm text-slate-500 mt-2">
                A mathematically sound, balanced ternary placement hierarchy designed for sustainability and weekly payouts.
            </p>
        </div>

        <!-- 3 Pillars Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Pillar 1 -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs hover:border-orange-300 transition">
                <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center text-xl mb-4">
                    <i class="fas fa-sitemap"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">1:3 Placement Structure</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Each member node supports exactly 3 downline placement positions: <strong>Left</strong>, <strong>Middle</strong>, and <strong>Right</strong>, creating a balanced ternary tree across 20 full levels (Levels 0–19).
                </p>
            </div>

            <!-- Pillar 2 -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs hover:border-amber-300 transition">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl mb-4">
                    <i class="fas fa-code-fork"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Dual Tree Isolation</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Direct sponsor referrals and physical placement positions operate independently. This ensures sponsor attribution is preserved while income flows smoothly upward along placement lines.
                </p>
            </div>

            <!-- Pillar 3 -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs hover:border-emerald-300 transition">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl mb-4">
                    <i class="fas fa-calculator"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Automated PV Engine</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    High PV Rate of <strong>13.5</strong> across Levels 0–7 and Low PV Rate of <strong>0.75</strong> across Levels 8–19 with 20% commission conversion and automated Monday-to-Sunday weekly settlement cycles.
                </p>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- 4-STEP ONBOARDING GUIDE -->
    <!-- ========================================================================= -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-3xl border border-slate-200/80 p-8 sm:p-12 shadow-xs">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    How to Get Started with BachatGanga.Org
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Start your direct selling career in 4 simple and transparent steps.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Step 1 -->
                <div class="relative p-5 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-3">
                    <span class="w-8 h-8 rounded-lg bg-orange-600 text-white text-xs font-black flex items-center justify-center">1</span>
                    <h4 class="font-bold text-slate-900 text-sm">Register & KYC</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">Create your member account with sponsor ID and complete bank & ID verification.</p>
                </div>

                <!-- Step 2 -->
                <div class="relative p-5 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-3">
                    <span class="w-8 h-8 rounded-lg bg-amber-500 text-slate-900 text-xs font-black flex items-center justify-center">2</span>
                    <h4 class="font-bold text-slate-900 text-sm">Shop Products</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">Purchase quality starter packages or everyday products and earn your initial PV.</p>
                </div>

                <!-- Step 3 -->
                <div class="relative p-5 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-3">
                    <span class="w-8 h-8 rounded-lg bg-stone-900 text-white text-xs font-black flex items-center justify-center">3</span>
                    <h4 class="font-bold text-slate-900 text-sm">Build 1:3 Network</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">Refer direct selling partners and structure your Left, Middle, and Right teams.</p>
                </div>

                <!-- Step 4 -->
                <div class="relative p-5 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-3">
                    <span class="w-8 h-8 rounded-lg bg-emerald-600 text-white text-xs font-black flex items-center justify-center">4</span>
                    <h4 class="font-bold text-slate-900 text-sm">Earn Weekly Income</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">Receive weekly direct bank payouts and participate in 100% profit cashback pools.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- FINAL CTA BANNER -->
    <!-- ========================================================================= -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-stone-950 via-stone-900 to-stone-950 border border-stone-800 rounded-3xl p-8 sm:p-12 text-white text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-6 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-80 h-80 bg-orange-600/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="space-y-2 max-w-xl relative z-10">
                <div class="text-amber-400 font-bold text-xs uppercase tracking-wider">"तेरा तुझको अर्पण"</div>
                <h3 class="text-2xl sm:text-3xl font-black text-white tracking-tight">Ready to build your Direct Selling business?</h3>
                <p class="text-slate-300 text-xs sm:text-sm">Join active direct selling members across India and achieve true financial empowerment.</p>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0 relative z-10">
                @auth
                    <a href="{{ route('account') }}" class="px-6 py-3.5 text-xs font-bold text-white bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 rounded-xl shadow-xs transition">
                        Open Member Dashboard
                    </a>
                @else
                    <a href="{{ route('register') }}" class="px-6 py-3.5 text-xs font-extrabold text-slate-950 bg-gradient-to-r from-amber-400 to-orange-400 hover:from-amber-300 hover:to-orange-300 rounded-xl shadow-md shadow-orange-500/20 hover:scale-105 transition">
                        Join Network Now
                    </a>
                    <a href="{{ route('login') }}" class="px-5 py-3.5 text-xs font-semibold text-white bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl transition">
                        Member Login
                    </a>
                @endauth
            </div>
        </div>
    </section>

</div>

<!-- Carousel & Sliding JavaScript Controller -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.slide-dot');
    const prevBtn = document.getElementById('prevSlideBtn');
    const nextBtn = document.getElementById('nextSlideBtn');
    const container = document.getElementById('heroSliderContainer');

    let currentSlide = 0;
    const totalSlides = slides.length;
    let slideInterval = null;

    function showSlide(index) {
        if (index < 0) {
            index = totalSlides - 1;
        } else if (index >= totalSlides) {
            index = 0;
        }
        currentSlide = index;

        slides.forEach((slide, i) => {
            if (i === currentSlide) {
                slide.classList.remove('hidden', 'hidden-slide');
                slide.classList.add('grid', 'active-slide');
                slide.style.opacity = '1';
            } else {
                slide.classList.add('hidden', 'hidden-slide');
                slide.classList.remove('grid', 'active-slide');
                slide.style.opacity = '0';
            }
        });

        dots.forEach((dot, i) => {
            if (i === currentSlide) {
                dot.classList.remove('w-2.5', 'bg-slate-300');
                dot.classList.add('w-8', 'bg-orange-600');
            } else {
                dot.classList.remove('w-8', 'bg-orange-600');
                dot.classList.add('w-2.5', 'bg-slate-300');
            }
        });
    }

    function nextSlide() {
        showSlide(currentSlide + 1);
    }

    function prevSlide() {
        showSlide(currentSlide - 1);
    }

    function startAutoSlide() {
        if (!slideInterval) {
            slideInterval = setInterval(nextSlide, 5000);
        }
    }

    function stopAutoSlide() {
        if (slideInterval) {
            clearInterval(slideInterval);
            slideInterval = null;
        }
    }

    if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); stopAutoSlide(); startAutoSlide(); });
    if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); stopAutoSlide(); startAutoSlide(); });

    dots.forEach((dot) => {
        dot.addEventListener('click', function() {
            const dotIndex = parseInt(this.getAttribute('data-dot'));
            showSlide(dotIndex);
            stopAutoSlide();
            startAutoSlide();
        });
    });

    if (container) {
        container.addEventListener('mouseenter', stopAutoSlide);
        container.addEventListener('mouseleave', startAutoSlide);
    }

    // Initialize first slide and start auto timer
    showSlide(0);
    startAutoSlide();
});
</script>

<style>
.hero-slide.hidden-slide {
    display: none !important;
    opacity: 0;
}
.hero-slide.active-slide {
    display: grid !important;
    opacity: 1;
}
</style>
@endsection

