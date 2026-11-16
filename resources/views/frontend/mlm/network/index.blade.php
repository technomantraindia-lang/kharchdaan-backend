@extends('frontend.layouts.app')

@section('title', 'Direct Selling Network')

@push('styles')
<style>
    .network-hero { background: linear-gradient(135deg, rgba(234,88,12,.08), rgba(245,158,11,.05)); border: 1px solid rgba(234,88,12,0.15); border-radius: 1.25rem; }
    .network-node { border-left: 4px solid #ea580c; }
    .network-node .network-meta { font-size: .84rem; }
    .network-slot-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
    .network-slot-empty { min-height: 116px; border: 1px dashed #fed7aa; border-radius: .75rem; background: #fffaf5; color: #9a3412; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 0.8rem; }
    .network-children { border-left: 2px solid #fed7aa; margin-left: 1rem; padding-left: 1rem; }
    .network-member-card { max-width: 520px; }
    @media (max-width: 768px) { .network-slot-grid { grid-template-columns: 1fr; } .network-children { margin-left: .25rem; padding-left: .75rem; } }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="fas fa-sitemap me-2 text-primary"></i>Direct Selling Network</h2>
        <p class="text-muted mb-0">Inspect your direct sponsor referrals and 1:3 physical placement hierarchy.</p>
    </div>
    <a href="{{ route('network.levels') }}" class="btn btn-outline-primary"><i class="fas fa-layer-group me-1"></i>Level PV Summary</a>
</div>

<div class="card network-hero border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-sm-6 col-lg-3">
                <div class="small text-muted text-uppercase fw-bold">Member Code</div>
                <div class="fw-bold font-monospace fs-5 text-primary">{{ $member['customer_id'] }}</div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-muted text-uppercase fw-bold">Account Status</div>
                <div class="fw-bold"><span class="badge bg-{{ $member['status'] === 'active' ? 'success' : 'secondary' }} text-uppercase">{{ $member['status_label'] }}</span></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-muted text-uppercase fw-bold">Direct Referrals</div>
                <a href="{{ route('network.referrals') }}" class="fw-bold fs-5 text-decoration-none d-inline-flex align-items-center gap-1">
                    {{ $direct_referral_count }}
                    <span class="small text-primary">view contacts →</span>
                </a>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-muted text-uppercase fw-bold">Placement Network</div>
                <div class="fw-bold fs-5 text-success">{{ $placement_network_count }} Members</div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-muted text-uppercase fw-bold">Own Month Volume</div>
                <div class="fw-bold fs-5">₹{{ number_format((float) ($own_month_volume ?? 0), 2) }}</div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-muted text-uppercase fw-bold">Downline Month Volume</div>
                <div class="fw-bold fs-5">₹{{ number_format((float) ($downline_month_volume ?? 0), 2) }}</div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-muted text-uppercase fw-bold">DS / Referral Status</div>
                <div class="d-flex flex-wrap gap-1 mt-1">
                    <span class="badge {{ !empty($ds_eligible) ? 'bg-success' : 'bg-danger' }}">{{ !empty($ds_eligible) ? 'DS Eligible' : 'DS Not Eligible' }}</span>
                    <span class="badge {{ !empty($referral_month_eligible) ? 'bg-success' : 'bg-danger' }}">{{ !empty($referral_month_eligible) ? 'Referral Eligible' : 'Referral Not Eligible' }}</span>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-muted text-uppercase fw-bold">Sponsor Team Month Total</div>
                <div class="fw-bold fs-5">₹{{ number_format((float) ($sponsor_team_month_total ?? 0), 2) }}</div>
            </div>
        </div>
        <div class="small text-muted mt-3 pt-3 border-top d-flex flex-wrap justify-content-between gap-2">
            <div>
                Direct Sponsor: <strong>{{ $member['sponsor']['name'] ?? 'Root / None' }}</strong>
                @if(!empty($member['sponsor']['customer_id'])) <span class="font-monospace text-primary">({{ $member['sponsor']['customer_id'] }})</span>@endif
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('network.placement') }}" class="btn btn-sm btn-outline-primary">Place Joinees</a>
                <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-outline-secondary">My Transactions</a>
            </div>
        </div>
        @if($placement_assignment_note)
            <div class="alert alert-warning mt-3 mb-0 py-2"><i class="fas fa-info-circle me-1"></i>{{ $placement_assignment_note }}</div>
        @endif
    </div>
</div>

@if(!empty($performance_chart))
@php
    $ownMax = max(1, max($performance_chart['own']));
    $downMax = max(1, max($performance_chart['downline']));
@endphp
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-bold">Performance — Past vs Current Month</div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="small text-muted text-uppercase fw-bold mb-2">Own Volume</div>
                @foreach ($performance_chart['labels'] as $i => $label)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small"><span>{{ $label }}</span><strong>₹{{ number_format($performance_chart['own'][$i], 2) }}</strong></div>
                        <div class="progress" style="height:10px;">
                            <div class="progress-bar bg-warning" style="width: {{ min(100, ($performance_chart['own'][$i] / $ownMax) * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="col-md-4">
                <div class="small text-muted text-uppercase fw-bold mb-2">Downline Volume</div>
                @foreach ($performance_chart['labels'] as $i => $label)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small"><span>{{ $label }}</span><strong>₹{{ number_format($performance_chart['downline'][$i], 2) }}</strong></div>
                        <div class="progress" style="height:10px;">
                            <div class="progress-bar bg-success" style="width: {{ min(100, ($performance_chart['downline'][$i] / $downMax) * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="col-md-4">
                <div class="small text-muted text-uppercase fw-bold mb-2">New Direct Referrals</div>
                @foreach ($performance_chart['labels'] as $i => $label)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small"><span>{{ $label }}</span><strong>{{ $performance_chart['direct_referrals'][$i] }}</strong></div>
                        <div class="progress" style="height:10px;">
                            <div class="progress-bar bg-primary" style="width: {{ min(100, ($performance_chart['direct_referrals'][$i] / max(1, max($performance_chart['direct_referrals']))) * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="small text-muted mt-2">Downline count (placement network): <strong>{{ $downline_count ?? $placement_network_count }}</strong></div>
    </div>
</div>
@endif

@if(!empty($sponsor_team_monthly))
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-bold">Sponsor Team — Monthly Status</div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Phone</th>
                    <th>Month Volume</th>
                    <th>Eligibility</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sponsor_team_monthly as $row)
                    <tr class="{{ !empty($row['highlight_red']) ? 'table-danger' : '' }}">
                        <td>
                            <div class="fw-semibold">{{ $row['name'] }}</div>
                            <div class="font-monospace small text-primary">{{ $row['customer_id'] }}</div>
                        </td>
                        <td>{{ $row['phone'] ?? '—' }}</td>
                        <td>₹{{ number_format((float) $row['month_volume'], 2) }}</td>
                        <td class="d-flex flex-wrap gap-1">
                            <span class="badge {{ $row['ds_eligible'] ? 'bg-success' : 'bg-danger' }}">{{ $row['status_badges']['ds'] }}</span>
                            <span class="badge {{ $row['referral_eligible'] ? 'bg-success' : 'bg-danger' }}">{{ $row['status_badges']['referral'] }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div id="networkMessage" class="alert d-none" role="status"></div>
<div class="mb-3">
    <label for="networkSearch" class="form-label fw-semibold">Search loaded team members</label>
    <input id="networkSearch" type="search" class="form-control" placeholder="Search by Member Code or Name...">
    <div class="form-text">Only members already expanded in your network are filtered.</div>
</div>

<ul class="nav nav-tabs mb-3" id="networkTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#placementPanel" type="button" role="tab">
            <i class="fas fa-sitemap me-1"></i>Placement Tree (1:3 Matrix)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sponsorPanel" type="button" role="tab">
            <i class="fas fa-users me-1"></i>Sponsor Tree (Direct Referrals)
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="placementPanel" role="tabpanel">
        <div class="card shadow-sm border-0"><div class="card-body p-4">
            <p class="text-muted small mb-3"><strong>Placement Tree</strong> displays physical Left, Middle, and Right positions. Hierarchy levels are shown relative to you (you are Level 1). Direct Selling Level PV income is still calculated across commission tiers 0–19.</p>
            <div id="placementRoot"></div>
        </div></div>
    </div>
    <div class="tab-pane fade" id="sponsorPanel" role="tabpanel">
        <div class="card shadow-sm border-0"><div class="card-body p-4">
            <p class="text-muted small mb-3"><strong>Sponsor Tree</strong> displays members directly introduced by you and your downlines.</p>
            <div id="sponsorRoot"></div>
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const childrenUrl = @json(route('network.children', ['member' => '__MEMBER__']));
    const sponsorRoot = @json($sponsor_root);
    const placementRoot = @json($placement_root);
    const message = document.getElementById('networkMessage');
    const search = document.getElementById('networkSearch');

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    })[character]);
    const statusClass = (status) => ({ active: 'bg-success', pending: 'bg-warning text-dark', inactive: 'bg-secondary', blocked: 'bg-danger' }[status] || 'bg-secondary');
    const showMessage = (text, type = 'danger') => { message.className = `alert alert-${type}`; message.textContent = text; };
    const clearMessage = () => { message.className = 'alert d-none'; message.textContent = ''; };

    const memberCard = (member, tree) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'network-member-card network-member mb-3';
        wrapper.dataset.searchText = `${member.customer_id} ${member.name}`.toLowerCase();
        wrapper.innerHTML = `
            <div class="card network-node shadow-sm"><div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div><div class="fw-bold">${escapeHtml(member.name)}</div><div class="font-monospace text-primary small">${escapeHtml(member.customer_id)}</div></div>
                    <span class="badge ${statusClass(member.status)}">${escapeHtml(member.status_label)}</span>
                </div>
                <div class="network-meta text-muted mt-2">
                    <div>Joined: ${escapeHtml(member.joined_at || 'Not available')}</div>
                    <div>Direct referrals: ${escapeHtml(member.direct_referral_count)}</div>
                    ${tree === 'placement' ? `<div>Position: <span class="fw-semibold text-dark">${escapeHtml(member.position_label)}</span> · Level ${escapeHtml(member.level_label)}</div>` : ''}
                </div>
                ${member.has_children ? `
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-primary network-toggle" type="button" aria-expanded="false">
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            <span class="toggle-text">Expand Downline</span>
                        </button>
                    </div>` : ''}
            </div></div>
            <div class="network-children d-none mt-2"></div>
        `;

        const toggleBtn = wrapper.querySelector('.network-toggle');
        const childrenContainer = wrapper.querySelector('.network-children');
        if (toggleBtn && childrenContainer) {
            let loaded = false;
            toggleBtn.addEventListener('click', async () => {
                clearMessage();
                if (loaded) {
                    const willOpen = childrenContainer.classList.contains('d-none');
                    childrenContainer.classList.toggle('d-none', !willOpen);
                    toggleBtn.querySelector('.toggle-text').textContent = willOpen ? 'Collapse' : 'Expand Downline';
                    toggleBtn.setAttribute('aria-expanded', String(willOpen));
                    return;
                }

                const spinner = toggleBtn.querySelector('.spinner-border');
                const label = toggleBtn.querySelector('.toggle-text');
                spinner.classList.remove('d-none');
                label.textContent = 'Loading...';
                toggleBtn.disabled = true;

                try {
                    const response = await fetch(`${childrenUrl.replace('__MEMBER__', member.id)}?tree=${encodeURIComponent(tree)}`, {
                        headers: { Accept: 'application/json' },
                    });
                    if (!response.ok) throw new Error('Unable to load branch.');
                    const payload = await response.json();
                    childrenContainer.innerHTML = '';

                    if (tree === 'sponsor') {
                        if (!payload.children?.length) {
                            childrenContainer.innerHTML = '<div class="text-muted small fst-italic mb-2">No direct downlines.</div>';
                        } else {
                            payload.children.forEach(child => childrenContainer.appendChild(memberCard(child, tree)));
                        }
                    } else {
                        const grid = document.createElement('div');
                        grid.className = 'network-slot-grid mb-2';
                        (payload.slots || []).forEach(slot => {
                            const slotWrapper = document.createElement('div');
                            if (slot.member) {
                                slotWrapper.appendChild(memberCard(slot.member, tree));
                            } else {
                                slotWrapper.className = 'network-slot-empty p-3';
                                slotWrapper.innerHTML = `<div><div class="fw-bold">${escapeHtml(slot.position_label)}</div><small class="text-muted">Empty Slot</small></div>`;
                            }
                            grid.appendChild(slotWrapper);
                        });
                        childrenContainer.appendChild(grid);
                    }

                    loaded = true;
                    childrenContainer.classList.remove('d-none');
                    label.textContent = 'Collapse';
                    toggleBtn.setAttribute('aria-expanded', 'true');
                } catch (error) {
                    showMessage(error.message || 'Unable to load member downline.');
                    label.textContent = 'Retry';
                } finally {
                    spinner.classList.add('d-none');
                    toggleBtn.disabled = false;
                }
            });
        }

        return wrapper;
    };

    if (sponsorRoot) document.getElementById('sponsorRoot')?.appendChild(memberCard(sponsorRoot, 'sponsor'));
    if (placementRoot) document.getElementById('placementRoot')?.appendChild(memberCard(placementRoot, 'placement'));

    search?.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();
        document.querySelectorAll('.network-member').forEach(element => {
            const matches = !query || (element.dataset.searchText || '').includes(query);
            element.classList.toggle('d-none', !matches);
        });
    });
})();
</script>
@endpush
