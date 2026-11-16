@extends('frontend.layouts.app')

@section('title', 'Place Joinees')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-user-plus me-2 text-primary"></i>Sponsor Placement</h2>
            <p class="text-muted mb-0">{{ $note }}</p>
        </div>
        <a href="{{ route('network.index') }}" class="btn btn-outline-secondary btn-sm">Back to Network</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('network.placement.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Joinee (your referral)</label>
                    <select name="joinee_member_id" class="form-select" required>
                        <option value="">Select joinee</option>
                        @foreach ($placeable_joinees as $joinee)
                            <option value="{{ $joinee['id'] }}" @disabled(! $joinee['can_place']) @selected(old('joinee_member_id') == $joinee['id'])>
                                {{ $joinee['customer_id'] }} — {{ $joinee['name'] }}
                                {{ $joinee['can_place'] ? '' : '(needs ₹'.$min_purchase.' purchase)' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Empty slot under your network</label>
                    <select name="slot" id="placementSlot" class="form-select" required>
                        <option value="">Select open slot</option>
                        @foreach ($open_slots as $slot)
                            <option value="{{ $slot['parent_id'] }}|{{ $slot['position'] }}"
                                data-parent="{{ $slot['parent_id'] }}"
                                data-position="{{ $slot['position'] }}"
                                @selected(old('new_parent_id') == $slot['parent_id'] && old('new_position') == $slot['position'])>
                                {{ $slot['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="new_parent_id" id="newParentId" value="{{ old('new_parent_id') }}">
                    <input type="hidden" name="new_position" id="newPosition" value="{{ old('new_position') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Reason (optional)</label>
                    <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" maxlength="500">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" @disabled(empty($open_slots))>Place Joinee</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">Your direct referrals</div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Customer ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Current Placement</th>
                        <th>₹{{ $min_purchase }} Ready</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($placeable_joinees as $joinee)
                        <tr class="{{ $joinee['can_place'] ? '' : 'table-danger' }}">
                            <td class="font-monospace">{{ $joinee['customer_id'] }}</td>
                            <td>{{ $joinee['name'] }}</td>
                            <td>{{ $joinee['phone'] ?? '—' }}</td>
                            <td>{{ $joinee['placement_position'] ? ucfirst($joinee['placement_position']) : 'Unassigned' }}</td>
                            <td>
                                <span class="badge {{ $joinee['can_place'] ? 'bg-success' : 'bg-danger' }}">
                                    {{ $joinee['can_place'] ? 'Yes' : 'No' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No direct referrals yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('placementSlot')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    document.getElementById('newParentId').value = opt?.dataset?.parent || '';
    document.getElementById('newPosition').value = opt?.dataset?.position || '';
});
</script>
@endpush
