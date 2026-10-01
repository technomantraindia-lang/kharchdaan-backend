@extends('admin.layouts.app')

@section('title', 'Initiate RTO Shipment')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0"><i class="fas fa-shipping-fast text-danger me-2"></i>Initiate RTO Shipment</h3>
    <a href="{{ admin_route('rto.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Cancel</a>
</div>

<div class="card shadow-sm col-md-8 mx-auto">
    <div class="card-body">
        <form action="{{ admin_route('rto.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-bold">Select Order *</label>
                <select name="order_id" class="form-select @error('order_id') is-invalid @enderror" required>
                    <option value="">Choose Order</option>
                    @foreach($orders as $o)
                        <option value="{{ $o->id }}">#{{ $o->order_num }} — {{ $o->user?->name ?: 'Guest' }} (₹{{ number_format($o->total, 2) }})</option>
                    @endforeach
                </select>
                @error('order_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Shipment / Tracking Number</label>
                <input type="text" name="shipment_id" class="form-control" placeholder="e.g. AWB123456789">
            </div>

            <div class="mb-3">
                <label class="form-label">RTO Reason</label>
                <input type="text" name="reason" class="form-control" placeholder="e.g. Customer unreachable / Incorrect address">
            </div>

            <button type="submit" class="btn btn-danger w-100"><i class="fas fa-shipping-fast me-1"></i> Initiate RTO</button>
        </form>
    </div>
</div>
@endsection
