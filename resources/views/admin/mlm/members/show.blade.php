@extends('admin.layouts.app')

@section('title', 'Member Profile: ' . ($member->customer_id ?? 'ID#'.$member->id))

@section('content')
{{-- Member Profile Header Bar --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 shadow-sm" style="width: 60px; height: 60px; background: linear-gradient(135deg, #ea580c, #c2410c) !important;">
                    {{ strtoupper(substr($member->user?->name ?? 'M', 0, 1)) }}
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="fw-bold mb-0 text-dark">{{ $member->user?->name ?? 'Unnamed Member' }}</h4>
                        <span class="badge bg-primary fs-6 px-2 py-1">{{ $member->customer_id ?? 'ID#'.$member->id }}</span>
                        @if(str_contains(strtoupper($member->customer_id ?? ''), 'TEST') || str_contains(strtoupper($member->user?->name ?? ''), 'TEST'))
                            <span class="badge bg-warning text-dark px-2 py-1">TEST DATA</span>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3 mt-1 text-muted small">
                        <span><i class="fas fa-envelope me-1"></i>{{ $member->user?->email ?? 'N/A' }}</span>
                        <span><i class="fas fa-phone me-1"></i>{{ $member->mobile ?? $member->user?->phone ?? 'N/A' }}</span>
                        <span><i class="fas fa-calendar-alt me-1"></i>Joined: <strong>{{ $member->joined_at?->format('M d, Y') ?? 'N/A' }}</strong></span>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge bg-{{ $member->status === 'active' ? 'success' : ($member->status === 'blocked' ? 'danger' : 'warning text-dark') }} fs-6 px-3 py-2">
                    <i class="fas fa-circle me-1" style="font-size:0.5rem;"></i> {{ ucfirst($member->status) }}
                </span>
                <span class="badge bg-{{ $member->kyc_status === 'approved' ? 'success' : ($member->kyc_status === 'rejected' ? 'danger' : 'warning text-dark') }} fs-6 px-3 py-2">
                    KYC: {{ ucfirst(str_replace('_', ' ', $member->kyc_status ?? 'pending')) }}
                </span>

                @if(auth()->user()->hasPermission('mlm.manage') || auth()->user()->hasPermission('mlm.edit'))
                    <a href="{{ admin_route('mlm.members.edit', $member) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-edit me-1"></i> Edit
                    </a>
                @endif
                @if(auth()->user()->hasPermission('mlm.kyc.view'))
                    <a href="{{ admin_route('mlm.members.kyc', $member) }}" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-id-card me-1"></i> Review KYC
                    </a>
                @endif
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Quick Actions
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="{{ admin_route('mlm.sponsor-network', ['root' => $member->id]) }}"><i class="fas fa-users me-2 text-primary"></i> View Sponsor Team</a></li>
                        <li><a class="dropdown-item" href="{{ admin_route('mlm.tree.index', ['root' => $member->id]) }}"><i class="fas fa-sitemap me-2 text-success"></i> View 1:3 Placement</a></li>
                        <li><a class="dropdown-item" href="{{ admin_route('mlm.genealogy', ['root' => $member->id]) }}"><i class="fas fa-project-diagram me-2 text-info"></i> View Genealogy</a></li>
                        @if(auth()->user()->hasPermission('mlm.manage') || auth()->user()->hasPermission('mlm.move'))
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ admin_route('mlm.tree.move.form', $member) }}"><i class="fas fa-exchange-alt me-2 text-warning"></i> Move Placement</a></li>
                        @endif
                        @if(auth()->user()->isSuperAdmin())
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ admin_route('mlm.members.destroy', $member) }}" method="POST" onsubmit="return confirm('{{ $member->placement_parent_id === null ? "WARNING: This member is the Matrix Root Leader!\n\nDeleting will promote their primary placement child to become the new Root Leader and safely re-anchor all downlines.\n\nAre you sure you want to permanently delete this Root Leader?" : "Are you sure you want to permanently delete this member? All associated ledger lines and KYC records will be safely cleaned." }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-trash-alt me-2"></i> Delete Member (Super Admin)
                                    </button>
                                </form>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Quick Top Stats Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold text-uppercase">Accumulated Network PV</div>
                <h3 class="fw-bold text-primary mt-1 mb-0">{{ number_format($totalPv, 2) }} <small class="fs-6 text-muted">PV</small></h3>
                <div class="small text-muted mt-1">Placement Level: <span class="badge bg-secondary">Level {{ $placementLevel }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold text-uppercase">Total Commission Earned</div>
                <h3 class="fw-bold text-success mt-1 mb-0">₹{{ number_format(round($totalIncome)) }}</h3>
                <div class="small text-success mt-1"><i class="fas fa-check-circle me-1"></i>₹{{ number_format(round($paidIncome)) }} paid out</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold text-uppercase">Direct Referrals</div>
                <h3 class="fw-bold text-dark mt-1 mb-0">{{ count($directReferrals) }} <small class="fs-6 text-muted">Distributors</small></h3>
                <div class="small text-muted mt-1">{{ $directReferrals->where('status', 'active')->count() }} active direct referrals</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold text-uppercase">Up to 100% Cashback Purchases</div>
                <h3 class="fw-bold text-warning mt-1 mb-0">{{ count($cashbacks) }} <small class="fs-6 text-muted">Records</small></h3>
                <div class="small text-muted mt-1">₹{{ number_format($cashbacks->sum('eligible_amount'), 2) }} eligible volume</div>
            </div>
        </div>
    </div>
</div>

{{-- Main Tabbed Interface --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white p-0 border-bottom">
        <ul class="nav nav-tabs card-header-tabs m-0 px-3 pt-2" id="memberTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-3 fw-semibold" id="overview-tab" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">
                    <i class="fas fa-user me-1 text-primary"></i> Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3 fw-semibold" id="sponsor-tab" data-bs-toggle="tab" data-bs-target="#tab-sponsor" type="button" role="tab">
                    <i class="fas fa-user-friends me-1 text-success"></i> Sponsor & Referrals
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3 fw-semibold" id="placement-tab" data-bs-toggle="tab" data-bs-target="#tab-placement" type="button" role="tab">
                    <i class="fas fa-sitemap me-1 text-info"></i> 1:3 Placement Matrix
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3 fw-semibold" id="network-tab" data-bs-toggle="tab" data-bs-target="#tab-network" type="button" role="tab">
                    <i class="fas fa-chart-network me-1 text-warning"></i> Network Summary
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3 fw-semibold" id="ledgers-tab" data-bs-toggle="tab" data-bs-target="#tab-ledgers" type="button" role="tab">
                    <i class="fas fa-coins me-1 text-primary"></i> Income Ledgers ({{ $incomeLedgers->total() }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3 fw-semibold" id="payouts-tab" data-bs-toggle="tab" data-bs-target="#tab-payouts" type="button" role="tab">
                    <i class="fas fa-wallet me-1 text-success"></i> Payouts ({{ count($payoutLines) }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3 fw-semibold" id="cashback-tab" data-bs-toggle="tab" data-bs-target="#tab-cashback" type="button" role="tab">
                    <i class="fas fa-hand-holding-usd me-1 text-warning"></i> Up to 100% Cashback ({{ count($cashbacks) }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3 fw-semibold" id="activity-tab" data-bs-toggle="tab" data-bs-target="#tab-activity" type="button" role="tab">
                    <i class="fas fa-history me-1 text-secondary"></i> Audit & Activity
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-4">
        <div class="tab-content" id="memberTabContent">
            
            {{-- TAB 1: OVERVIEW --}}
            <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fas fa-id-badge me-2 text-primary"></i>Personal & Account Profile</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tr><td class="text-muted" style="width: 160px;">Full Name:</td><td class="fw-bold">{{ $member->user?->name ?? 'N/A' }}</td></tr>
                            <tr><td class="text-muted">Member ID / Code:</td><td><strong class="font-monospace text-primary">{{ $member->customer_id ?? 'ID#'.$member->id }}</strong></td></tr>
                            <tr><td class="text-muted">Registered Email:</td><td>{{ $member->user?->email ?? 'N/A' }}</td></tr>
                            <tr><td class="text-muted">Mobile Number:</td><td>{{ $member->mobile ?? $member->user?->phone ?? 'N/A' }}</td></tr>
                            <tr><td class="text-muted">Account Status:</td><td><span class="badge bg-{{ $member->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($member->status) }}</span></td></tr>
                            <tr><td class="text-muted">KYC Compliance:</td><td><span class="badge bg-{{ $member->kyc_status === 'approved' ? 'success' : 'warning text-dark' }}">{{ ucfirst(str_replace('_', ' ', $member->kyc_status ?? 'pending')) }}</span></td></tr>
                            <tr><td class="text-muted">Registered Date:</td><td>{{ $member->joined_at?->format('M d, Y h:i A') ?? '-' }}</td></tr>
                            <tr><td class="text-muted">Created By Admin:</td><td>{{ $member->createdBy?->name ?? 'System' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fas fa-university me-2 text-success"></i>Bank & Financial Details</h6>
                        @if($canViewSensitive)
                        <table class="table table-borderless table-sm mb-0">
                            <tr><td class="text-muted" style="width: 160px;">PAN Number:</td><td class="font-monospace fw-bold">{{ $member->masked_pan ?? 'Not Provided' }}</td></tr>
                            <tr><td class="text-muted">Aadhaar Ref:</td><td class="font-monospace">{{ $member->masked_aadhaar ?? 'Not Provided' }}</td></tr>
                            <tr><td class="text-muted">Bank Name:</td><td>{{ $member->bank_name ?? 'N/A' }}</td></tr>
                            <tr><td class="text-muted">Bank Branch:</td><td>{{ $member->bank_branch ?? 'N/A' }}</td></tr>
                            <tr><td class="text-muted">Account Holder:</td><td>{{ $member->bank_account_holder_name ?? 'N/A' }}</td></tr>
                            <tr><td class="text-muted">Account Number:</td><td class="font-monospace fw-bold text-dark">{{ $member->masked_bank_account ?? 'Not Provided' }}</td></tr>
                            <tr><td class="text-muted">IFSC Code:</td><td class="font-monospace">{{ $member->ifsc_code ?? 'N/A' }}</td></tr>
                            <tr><td class="text-muted">KYC Verified At:</td><td>{{ $member->kyc_verified_at?->format('M d, Y') ?? 'Pending' }}</td></tr>
                        </table>
                        @else
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-lock me-2"></i> Sensitive KYC data is restricted to authorized Admin users.
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- TAB 2: SPONSOR & REFERRALS --}}
            <div class="tab-pane fade" id="tab-sponsor" role="tabpanel">
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light border p-3">
                            <div class="text-muted small fw-bold text-uppercase">Direct Sponsor (Upline)</div>
                            @if($member->sponsor)
                                <div class="d-flex align-items-center gap-3 mt-2">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px;">
                                        {{ strtoupper(substr($member->sponsor->user?->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">{{ $member->sponsor->user?->name }}</h6>
                                        <div class="small font-monospace text-primary">{{ $member->sponsor->customer_id }}</div>
                                        <div class="small text-muted">{{ $member->sponsor->user?->email }}</div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ admin_route('mlm.members.show', $member->sponsor) }}" class="btn btn-sm btn-outline-primary">View Sponsor Profile</a>
                                </div>
                            @else
                                <div class="mt-2 text-muted">
                                    <i class="fas fa-crown text-warning me-1"></i> Company Top Root / No Sponsor
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light border p-3">
                            <div class="text-muted small fw-bold text-uppercase">Referral Team Volume</div>
                            <div class="d-flex justify-content-between align-items-baseline mt-2">
                                <h3 class="fw-bold text-dark mb-0">{{ count($directReferrals) }} Direct Referrals</h3>
                                <a href="{{ admin_route('mlm.sponsor-network', ['root' => $member->id]) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-users me-1"></i> Open Sponsor Tree
                                </a>
                            </div>
                            <div class="small text-muted mt-2">{{ $directReferrals->where('status', 'active')->count() }} active distributors in direct downline</div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-users text-primary me-2"></i>Personally Sponsored Distributors ({{ count($directReferrals) }})</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Member Code</th>
                                <th>Distributor Name</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>KYC</th>
                                <th>Joined Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($directReferrals as $ref)
                            <tr>
                                <td><strong class="text-primary">{{ $ref->customer_id ?? 'ID#'.$ref->id }}</strong></td>
                                <td><strong>{{ $ref->user?->name ?? 'Unknown' }}</strong></td>
                                <td class="small text-muted">{{ $ref->user?->email ?? $ref->mobile }}</td>
                                <td><span class="badge bg-{{ $ref->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($ref->status) }}</span></td>
                                <td><span class="badge bg-{{ $ref->kyc_status === 'approved' ? 'success' : 'warning text-dark' }}">{{ ucfirst($ref->kyc_status ?? 'pending') }}</span></td>
                                <td class="small text-muted">{{ $ref->joined_at?->format('M d, Y') ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ admin_route('mlm.members.show', $ref) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">This member has not sponsored any distributors yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 3: 1:3 PLACEMENT MATRIX --}}
            <div class="tab-pane fade" id="tab-placement" role="tabpanel">
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light border p-3">
                            <div class="text-muted small fw-bold text-uppercase">Placement Parent & Matrix Position</div>
                            <div class="mt-2">
                                @if($member->placementParent)
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-info text-dark text-uppercase px-2 py-1">Position: {{ $member->placement_position }}</span>
                                        <span class="badge bg-primary px-2 py-1">Placement Level {{ $placementLevel }}</span>
                                    </div>
                                    <div class="mt-2">
                                        <strong>{{ $member->placementParent->user?->name }}</strong> ({{ $member->placementParent->customer_id }})
                                    </div>
                                @else
                                    <span class="badge bg-secondary">Root Node (No Placement Parent)</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light border p-3">
                            <div class="text-muted small fw-bold text-uppercase">1:3 Placement Matrix Capacity</div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fs-5 fw-bold text-dark">{{ $member->placementChildren()->count() }} / 3 Slots Occupied</span>
                                <a href="{{ admin_route('mlm.tree.index', ['root' => $member->id]) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-sitemap me-1"></i> Interactive Tree
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-network-wired text-primary me-2"></i>Direct 1:3 Matrix Slots (Left, Middle, Right)</h6>
                <div class="row g-3">
                    @foreach($placementSlots as $slot)
                    <div class="col-md-4">
                        <div class="card h-100 border text-center p-3 {{ $slot['member'] ? 'bg-white shadow-sm' : 'bg-light border-dashed' }}">
                            <span class="badge bg-{{ $slot['position'] === 'left' ? 'primary' : ($slot['position'] === 'middle' ? 'info' : 'warning text-dark') }} text-uppercase mb-2">
                                {{ $slot['position_label'] }} Slot
                            </span>
                            @if($slot['member'])
                                <h6 class="fw-bold text-dark mb-1">{{ $slot['member']['name'] }}</h6>
                                <div class="font-monospace small text-primary mb-2">{{ $slot['member']['customer_id'] }}</div>
                                <span class="badge bg-{{ $slot['member']['status'] === 'active' ? 'success' : 'secondary' }} mb-3">{{ $slot['member']['status_label'] }}</span>
                                <div>
                                    <a href="{{ $slot['member']['details_url'] }}" class="btn btn-sm btn-outline-primary w-100">View Node</a>
                                </div>
                            @else
                                <div class="py-4 text-muted">
                                    <i class="fas fa-plus-circle fa-2x mb-2 text-secondary opacity-50"></i>
                                    <div>Empty Slot</div>
                                    <small class="text-muted">Available for placement</small>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- TAB 4: NETWORK SUMMARY --}}
            <div class="tab-pane fade" id="tab-network" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border p-3 h-100">
                            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-sitemap me-2"></i>Network Relationships (Sponsor & Placement)</h6>
                            <table class="table table-borderless table-sm mb-0">
                                <tr><td class="text-muted" style="width: 180px;">Direct Sponsor:</td><td>{{ $member->sponsor ? $member->sponsor->user?->name . ' (' . $member->sponsor->customer_id . ')' : 'None (Top Root)' }}</td></tr>
                                <tr><td class="text-muted">Placement Parent:</td><td>{{ $member->placementParent ? $member->placementParent->user?->name . ' (' . $member->placementParent->customer_id . ')' : 'None (Tree Root)' }}</td></tr>
                                <tr><td class="text-muted">Placement Position:</td><td><span class="badge bg-light text-dark border text-uppercase">{{ $member->placement_position ?? 'ROOT' }}</span></td></tr>
                                <tr><td class="text-muted">Placement Tree Level:</td><td><span class="badge bg-primary">Level {{ $placementLevel }}</span></td></tr>
                                <tr><td class="text-muted">Direct Referrals:</td><td><strong>{{ count($directReferrals) }}</strong> members</td></tr>
                                <tr><td class="text-muted">Placement Children:</td><td><strong>{{ $member->placementChildren()->count() }}</strong> / 3 slots</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border p-3 h-100">
                            <h6 class="fw-bold text-success mb-3"><i class="fas fa-coins me-2"></i>Financial & Commission Summary</h6>
                            <table class="table table-borderless table-sm mb-0">
                                <tr><td class="text-muted" style="width: 180px;">Accumulated PV:</td><td><strong class="text-primary">{{ number_format($totalPv, 2) }} PV</strong></td></tr>
                                <tr><td class="text-muted">Gross Income Earned:</td><td><strong class="text-success fs-5">₹{{ number_format(round($totalIncome)) }}</strong></td></tr>
                                <tr><td class="text-muted">Income Paid Out:</td><td><strong class="text-dark">₹{{ number_format(round($paidIncome)) }}</strong></td></tr>
                                <tr><td class="text-muted">Pending In-Cycle:</td><td><strong class="text-warning">₹{{ number_format(round($pendingIncome)) }}</strong></td></tr>
                                <tr><td class="text-muted">Up to 100% Cashback Count:</td><td><strong>{{ count($cashbacks) }}</strong> orders</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 5: INCOME LEDGERS --}}
            <div class="tab-pane fade" id="tab-ledgers" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-list text-primary me-2"></i>Detailed Income Ledgers by Level</h6>
                    <span class="badge bg-light text-dark border">Total PV: {{ number_format($totalPv, 2) }} &bull; Total: ₹{{ number_format(round($totalIncome)) }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Transaction Ref</th>
                                <th>Purchaser</th>
                                <th>Level</th>
                                <th class="text-end">Eligible Amt</th>
                                <th class="text-end">PV Generated</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">Calculated Income</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($incomeLedgers as $ledger)
                            <tr>
                                <td>
                                    <code class="text-dark fw-semibold">{{ $ledger->source_transaction_reference }}</code>
                                    @if(str_contains(strtoupper($ledger->source_transaction_reference), 'TEST'))
                                        <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;">TEST DATA</span>
                                    @endif
                                </td>
                                <td>{{ $ledger->purchasingMember?->user?->name ?? 'Self' }}</td>
                                <td>
                                    <span class="badge bg-{{ $ledger->level <= 7 ? 'primary' : 'secondary' }}">
                                        Level {{ $ledger->level }}
                                    </span>
                                </td>
                                <td class="text-end">₹{{ number_format($ledger->eligible_amount, 2) }}</td>
                                <td class="text-end fw-semibold text-primary">{{ number_format($ledger->pv, 2) }} PV</td>
                                <td class="text-end">{{ number_format($ledger->rate * 100, 0) }}%</td>
                                <td class="text-end fw-bold text-success">₹{{ number_format(round($ledger->calculated_amount)) }}</td>
                                <td>
                                    <span class="badge bg-{{ $ledger->status === 'paid' ? 'success' : ($ledger->status === 'reversed' ? 'danger' : 'warning text-dark') }}">
                                        {{ ucfirst(str_replace('_', ' ', $ledger->status)) }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ $ledger->transaction_date?->format('M d, Y') ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No income ledgers recorded for this member.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($incomeLedgers->hasPages())
                <div class="mt-3 d-flex justify-content-end">
                    {{ $incomeLedgers->links() }}
                </div>
                @endif
            </div>

            {{-- TAB 6: PAYOUTS --}}
            <div class="tab-pane fade" id="tab-payouts" role="tabpanel">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-wallet text-primary me-2"></i>Weekly Payout Lines & Settlements</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Weekly Cycle Ref</th>
                                <th>Period</th>
                                <th class="text-end">Gross Income</th>
                                <th class="text-end">Adjustment</th>
                                <th class="text-end">Net Payable</th>
                                <th>Status</th>
                                <th>Bank Snapshot</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payoutLines as $line)
                            <tr>
                                <td><strong>{{ $line->cycle?->cycle_reference ?? 'Cycle #'.$line->payout_cycle_id }}</strong></td>
                                <td>{{ $line->cycle?->period_start?->format('M d') }} – {{ $line->cycle?->period_end?->format('M d, Y') }}</td>
                                <td class="text-end">₹{{ number_format(round($line->gross_income)) }}</td>
                                <td class="text-end text-muted">₹{{ number_format(round($line->adjustment_amount)) }}</td>
                                <td class="text-end fw-bold text-success">₹{{ number_format(round($line->net_payable)) }}</td>
                                <td>
                                    <span class="badge bg-{{ $line->status === 'paid' ? 'success' : ($line->status === 'on_hold' ? 'danger' : 'warning text-dark') }}">
                                        {{ ucfirst(str_replace('_', ' ', $line->status)) }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $line->bank_name_snapshot ?? 'Default Bank' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No payout cycles generated for this member yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 7: UP TO 100% CASHBACK --}}
            <div class="tab-pane fade" id="tab-cashback" role="tabpanel">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-hand-holding-usd text-warning me-2"></i>Up to 100% Cashback Eligibility Records</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order Ref</th>
                                <th class="text-end">Eligible Amount</th>
                                <th class="text-end">Max Cashback Cap</th>
                                <th class="text-end">Recovered</th>
                                <th class="text-end">Remaining</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cashbacks as $cb)
                            <tr>
                                <td><strong>{{ $cb->order_reference }}</strong></td>
                                <td class="text-end">₹{{ number_format($cb->original_eligible_amount, 2) }}</td>
                                <td class="text-end fw-semibold text-primary">₹{{ number_format($cb->max_cashback_amount, 2) }}</td>
                                <td class="text-end text-success">₹{{ number_format($cb->recovered_amount, 2) }}</td>
                                <td class="text-end fw-bold text-warning">₹{{ number_format($cb->remaining_cashback_amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $cb->status === 'paid' ? 'success' : ($cb->status === 'allocated_to_pool' ? 'info' : 'warning text-dark') }}">
                                        {{ ucfirst(str_replace('_', ' ', $cb->status)) }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $cb->created_at->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No up to 100% cashback records registered for this member.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 8: AUDIT & ACTIVITY --}}
            <div class="tab-pane fade" id="tab-activity" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-dark mb-3"><i class="fas fa-history text-secondary me-2"></i>Member Activity History</h6>
                        @if($canViewActivity)
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle mb-0">
                                    <thead class="table-light"><tr><th>Action</th><th>Description</th><th>Admin</th><th>Date</th></tr></thead>
                                    <tbody>
                                        @forelse($activityLogs ?? [] as $log)
                                            <tr>
                                                <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</span></td>
                                                <td>{{ $log->description ?? '-' }}</td>
                                                <td class="small text-muted">{{ $log->user?->name ?? 'System' }}</td>
                                                <td class="small text-muted">{{ $log->created_at?->format('M d, Y') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center py-3 text-muted">No activity records logged.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-light border">Activity logs restricted.</div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-dark mb-3"><i class="fas fa-shield-alt text-info me-2"></i>KYC Status History</h6>
                        @if($canViewSensitive)
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle mb-0">
                                    <thead class="table-light"><tr><th>Previous</th><th>New</th><th>Reason</th><th>Admin</th><th>Date</th></tr></thead>
                                    <tbody>
                                        @forelse($kycHistories ?? [] as $kh)
                                            <tr>
                                                <td>{{ $kh->old_status ? ucfirst(str_replace('_', ' ', $kh->old_status)) : '-' }}</td>
                                                <td><span class="badge bg-{{ $kh->new_status === 'approved' ? 'success' : 'warning text-dark' }}">{{ ucfirst(str_replace('_', ' ', $kh->new_status)) }}</span></td>
                                                <td class="small">{{ $kh->rejection_reason ?? '-' }}</td>
                                                <td class="small text-muted">{{ $kh->changedBy?->name ?? 'System' }}</td>
                                                <td class="small text-muted">{{ $kh->changed_at?->format('M d, Y') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center py-3 text-muted">No KYC changes recorded.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-light border">KYC audit restricted.</div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
