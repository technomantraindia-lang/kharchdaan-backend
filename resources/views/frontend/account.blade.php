@extends('frontend.layouts.app')

@section('title', 'My Account')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-md overflow-hidden">
        <!-- Header Banner -->
        <div class="bg-gradient-to-r from-orange-600 via-orange-500 to-amber-500 p-6 sm:p-8 text-white relative">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-white text-orange-600 flex items-center justify-center font-black text-2xl shadow-lg ring-4 ring-white/20">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tight">{{ $user->name }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-white/20 text-white backdrop-blur-xs font-mono">
                            {{ $user->mlm_member_id ?? 'Direct Selling Member' }}
                        </span>
                        <span class="text-xs text-amber-100 font-semibold">"तेरा तुझको अर्पण"</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="p-6 sm:p-8 space-y-6">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Member Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/70 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-id-badge text-sm"></i>
                        </div>
                        <div>
                            <div class="text-slate-400 font-medium">Member Code</div>
                            <div class="font-bold text-slate-900 font-mono text-sm">{{ $user->mlm_member_id ?? 'Not Assigned' }}</div>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/70 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-envelope text-sm"></i>
                        </div>
                        <div>
                            <div class="text-slate-400 font-medium">Email Address</div>
                            <div class="font-bold text-slate-900 truncate max-w-[180px]">{{ $user->email }}</div>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/70 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-phone text-sm"></i>
                        </div>
                        <div>
                            <div class="text-slate-400 font-medium">Mobile Number</div>
                            <div class="font-bold text-slate-900">{{ $user->phone ?? 'Not Provided' }}</div>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/70 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-shield-check text-sm"></i>
                        </div>
                        <div>
                            <div class="text-slate-400 font-medium">Account Status</div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $user->status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Income (shown separately)</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mb-5">
                    <a href="{{ route('cashback.index') }}" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 font-bold text-xs hover:bg-emerald-100/80 transition">
                        <div class="text-[10px] uppercase tracking-wide text-emerald-600 mb-1">Cashback</div>
                        View cashback status &amp; payouts
                    </a>
                    <a href="{{ route('transactions.index') }}" class="p-4 rounded-2xl bg-sky-50 border border-sky-200 text-sky-900 font-bold text-xs hover:bg-sky-100/80 transition">
                        <div class="text-[10px] uppercase tracking-wide text-sky-600 mb-1">Referral Income</div>
                        Monthly ₹200 eligibility (structure)
                    </a>
                    <a href="{{ route('mlm-payouts.index') }}" class="p-4 rounded-2xl bg-violet-50 border border-violet-200 text-violet-900 font-bold text-xs hover:bg-violet-100/80 transition">
                        <div class="text-[10px] uppercase tracking-wide text-violet-600 mb-1">Direct Selling</div>
                        Level PV / DS income ledger
                    </a>
                </div>
            </div>

            <!-- Portal Links -->
            <div class="pt-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Direct Selling Portals</h3>
                <div class="space-y-2.5">
                    <a href="{{ route('network.index') }}" class="flex items-center justify-between p-4 rounded-2xl bg-orange-50 hover:bg-orange-100/80 border border-orange-200 text-orange-800 font-bold text-xs transition">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-sitemap text-orange-600 text-sm"></i>
                            <span>1:3 Placement & Sponsor Network Tree</span>
                        </div>
                        <i class="fas fa-arrow-right text-orange-600"></i>
                    </a>

                    <a href="{{ route('network.referrals') }}" class="flex items-center justify-between p-4 rounded-2xl bg-indigo-50 hover:bg-indigo-100/80 border border-indigo-200 text-indigo-900 font-bold text-xs transition">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-address-book text-indigo-600 text-sm"></i>
                            <span>Direct Referrals (contact list)</span>
                        </div>
                        <i class="fas fa-arrow-right text-indigo-600"></i>
                    </a>

                    <a href="{{ route('transactions.index') }}" class="flex items-center justify-between p-4 rounded-2xl bg-rose-50 hover:bg-rose-100/80 border border-rose-200 text-rose-800 font-bold text-xs transition">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-receipt text-rose-600 text-sm"></i>
                            <span>My Transactions & Eligibility</span>
                        </div>
                        <i class="fas fa-arrow-right text-rose-600"></i>
                    </a>

                    <a href="{{ route('network.placement') }}" class="flex items-center justify-between p-4 rounded-2xl bg-amber-50 hover:bg-amber-100/80 border border-amber-200 text-amber-900 font-bold text-xs transition">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-user-plus text-amber-600 text-sm"></i>
                            <span>Place Sponsored Joinees (after ₹200)</span>
                        </div>
                        <i class="fas fa-arrow-right text-amber-600"></i>
                    </a>

                    <a href="{{ route('products.index') }}" class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-800 font-bold text-xs transition">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-bag-shopping text-slate-600 text-sm"></i>
                            <span>Browse Direct Selling Store & Starter Kits</span>
                        </div>
                        <i class="fas fa-arrow-right text-slate-400"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
