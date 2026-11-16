@extends('frontend.layouts.app')

@section('title', 'Direct Referrals')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-users me-2 text-primary"></i>Direct Referrals</h2>
            <p class="text-muted mb-0">
                Sponsor {{ $sponsor['customer_id'] ?? '' }} — {{ $direct_referral_count }} direct members with contact details.
            </p>
        </div>
        <a href="{{ route('network.index') }}" class="btn btn-outline-secondary btn-sm">Back to Network</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Customer ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Placement</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($referrals as $ref)
                        <tr>
                            <td class="font-monospace fw-semibold text-primary">{{ $ref['customer_id'] }}</td>
                            <td>{{ $ref['name'] }}</td>
                            <td>{{ $ref['phone'] ?? '—' }}</td>
                            <td>{{ $ref['email'] ?? '—' }}</td>
                            <td>{{ $ref['joined_at_label'] ?? $ref['joined_at'] }}</td>
                            <td><span class="badge bg-secondary">{{ $ref['status'] }}</span></td>
                            <td>{{ !empty($ref['has_placement']) ? ucfirst($ref['placement_position'] ?? 'Yes') : 'Pending' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">No direct referrals yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
