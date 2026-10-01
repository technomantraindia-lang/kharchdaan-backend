@extends('admin.layouts.app')

@section('title', 'Sponsor Network')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Sponsor Network & Referral Hierarchy</h1>
            <p class="text-xs text-slate-500 mt-0.5">Direct referral relationships, personal downline performance, and sponsorship volume</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ admin_route('mlm.tree.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg text-xs font-semibold border border-blue-200 transition">
                <i class="fas fa-sitemap text-xs"></i> Placement Tree (1:3)
            </a>
            <a href="{{ admin_route('mlm.genealogy') }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-lg text-xs font-semibold transition">
                <i class="fas fa-project-diagram text-xs"></i> Genealogy View
            </a>
        </div>
    </div>

    <!-- Informational Banner -->
    <div class="p-4 rounded-xl bg-blue-50/80 border border-blue-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                <i class="fas fa-info"></i>
            </div>
            <div>
                <div class="font-bold text-xs text-blue-950">Sponsor Network vs. Placement Tree</div>
                <div class="text-[11px] text-blue-800/90 mt-0.5 max-w-3xl">
                    The <strong>Sponsor Network</strong> tracks who directly referred whom to KharchDaan. The <strong>Placement Tree</strong> is the 1:3 physical matrix where 20-level commission PV is distributed.
                </div>
            </div>
        </div>
        <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-blue-200/60 text-blue-900 self-start sm:self-center">Referral Engine</span>
    </div>

    <!-- Root Selector & Search Bar -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-4 shadow-xs">
        <form method="GET" action="{{ admin_route('mlm.sponsor-network') }}" class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 text-xs font-bold text-slate-600">
                    <i class="fas fa-user-check text-blue-500"></i>
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
                    <a href="{{ admin_route('mlm.sponsor-network') }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
                        <i class="fas fa-rotate-left text-xs"></i> Reset to Top
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

    @if($root && $networkData)
    <!-- Root Member Sponsor Summary Cards (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Direct Referrals</div>
            <div class="text-2xl font-extrabold text-blue-600 mt-2">
                {{ $networkData['direct_referrals_count'] }}
                <span class="text-xs font-semibold text-emerald-600">({{ $networkData['active_direct_count'] }} active)</span>
            </div>
            <div class="text-xs text-slate-400 mt-1">Personally sponsored distributors</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Sponsor Downline</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-2">
                {{ number_format($networkData['total_downline_count']) }}
                <span class="text-xs font-semibold text-emerald-600">({{ number_format($networkData['active_downline_count']) }} active)</span>
            </div>
            <div class="text-xs text-slate-400 mt-1">Multi-tier referral network</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Team Network PV</div>
            <div class="text-2xl font-extrabold font-mono text-emerald-600 mt-2">
                {{ number_format($networkData['network_pv'], 4) }} <span class="text-xs font-sans text-slate-400 font-normal">PV</span>
            </div>
            <div class="text-xs text-slate-400 mt-1">Personal: {{ number_format($networkData['personal_pv'], 4) }} PV</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Network Income Generated</div>
            <div class="text-2xl font-extrabold text-indigo-600 mt-2">
                ₹{{ number_format(round($networkData['network_income'])) }}
            </div>
            <div class="text-xs text-slate-400 mt-1">Root Earned: ₹{{ number_format(round($networkData['personal_income'])) }}</div>
        </div>
    </div>

    <!-- Selected Root Profile Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-orange-600 to-amber-600 text-white font-black flex items-center justify-center text-lg shadow-xs">
                    {{ strtoupper(substr($root->user?->name ?? 'M', 0, 1)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-slate-900">{{ $root->user?->name ?? 'Unnamed' }}</h2>
                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-xs font-mono font-bold border border-blue-200">{{ $root->customer_id }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $root->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">{{ ucfirst($root->status) }}</span>
                    </div>
                    <div class="text-xs text-slate-400 mt-0.5">{{ $root->user?->email }} &bull; {{ $root->user?->phone ?? 'No phone' }}</div>
                </div>
            </div>

            <div class="flex items-center gap-6 text-xs">
                <div>
                    <div class="text-slate-400 text-[11px]">Direct Sponsor</div>
                    @if($root->sponsor)
                        <div class="font-semibold text-slate-800">{{ $root->sponsor->user?->name }}</div>
                        <div class="text-[10px] font-mono text-slate-400">ID: {{ $root->sponsor->customer_id }}</div>
                    @else
                        <span class="text-slate-400 font-medium">None (Top Root)</span>
                    @endif
                </div>

                <div>
                    <div class="text-slate-400 text-[11px]">Joined Date & KYC</div>
                    <div class="font-semibold text-slate-800">{{ $root->joined_at?->format('M d, Y') ?? 'N/A' }}</div>
                    <div>
                        <span class="inline-flex px-1.5 py-0.2 rounded text-[9px] font-bold uppercase {{ $root->kyc_status === 'approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">{{ ucfirst($root->kyc_status ?? 'pending') }}</span>
                    </div>
                </div>

                <a href="{{ admin_route('mlm.members.show', $root) }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg text-xs font-semibold border border-blue-200 transition">
                    <i class="fas fa-arrow-up-right-from-square text-xs"></i> Full Profile
                </a>
            </div>
        </div>
    </div>

    <!-- Direct Referrals Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-users text-blue-600"></i> Directly Sponsored Distributors ({{ count($networkData['direct_referrals']) }})
            </h2>
            <span class="text-xs text-slate-400">Click Explore to navigate sub-network</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200/70 text-[10px]">
                    <tr>
                        <th class="px-5 py-3">Member Code</th>
                        <th class="px-5 py-3">Distributor Name</th>
                        <th class="px-5 py-3">Joined Date</th>
                        <th class="px-5 py-3 text-center">Direct Referrals</th>
                        <th class="px-5 py-3 text-center">Total Downline</th>
                        <th class="px-5 py-3 text-right">Team PV</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">KYC</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($networkData['direct_referrals'] as $item)
                    @php $m = $item['member']; @endphp
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-3 font-mono font-bold text-blue-600">{{ $m['customer_id'] ?? 'ID#'.$m['id'] }}</td>
                        <td class="px-5 py-3">
                            <div class="font-bold text-slate-900">{{ $m['name'] }}</div>
                            <div class="text-slate-400 text-[11px]">{{ $m['email'] ?? $m['phone'] }}</div>
                        </td>
                        <td class="px-5 py-3 text-slate-400 text-[11px]">{{ $m['joined_at'] ?? '-' }}</td>
                        <td class="px-5 py-3 text-center font-bold text-slate-800">{{ $item['direct_referrals_count'] }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="font-bold text-slate-900">{{ $item['total_downline_count'] }}</span>
                            <span class="text-[10px] text-emerald-600">({{ $item['active_downline_count'] }} active)</span>
                        </td>
                        <td class="px-5 py-3 text-right font-mono font-semibold text-blue-600">{{ number_format($item['network_pv'], 4) }} PV</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $m['status'] === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ $m['status_label'] }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $m['kyc_status'] === 'approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ ucfirst($m['kyc_status'] ?? 'pending') }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ admin_route('mlm.sponsor-network', ['root' => $m['id']]) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold border border-blue-200 transition">
                                    <i class="fas fa-sitemap text-[10px]"></i> Explore
                                </a>
                                <a href="{{ $m['details_url'] }}" class="inline-flex items-center px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition" title="View Details">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-5 py-8 text-center text-slate-400">
                            <i class="fas fa-user-friends text-2xl mb-1 block opacity-40"></i>
                            This member does not have any direct sponsored referrals yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl border border-slate-200/80 p-12 text-center text-slate-400">
        <i class="fas fa-users-slash text-3xl mb-2 text-slate-300 block"></i>
        <h3 class="text-sm font-bold text-slate-700">No Member Selected</h3>
        <p class="text-xs text-slate-400 mt-1">Please select a member above to inspect their direct sponsor network and referral volume.</p>
    </div>
    @endif
</div>
@endsection
