@extends('admin.layouts.app')
@section('title', 'Inquiry from ' . $inquiry->name)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Inquiry from {{ $inquiry->name }}</h2>
    <a href="{{ admin_route('inquiries.index') }}" class="btn btn-secondary">Back</a>
</div>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4"><div class="card-header"><h5>Message</h5></div><div class="card-body">
            <p>{{ $inquiry->msg }}</p>
            <small class="text-muted">Received: {{ $inquiry->created_at->format('M d, Y H:i') }}</small>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4"><div class="card-header"><h5>Contact Info</h5></div><div class="card-body">
            <p><strong>Name:</strong> {{ $inquiry->name }}<br>
            <strong>Email:</strong> {{ $inquiry->email }}<br>
            <strong>Phone:</strong> {{ $inquiry->phone }}<br>
            @if($inquiry->product)<strong>Product:</strong> {{ $inquiry->product->name }}@endif</p>
        </div></div>
        <div class="card"><div class="card-header"><h5>Update Status</h5></div><div class="card-body">
            <form action="{{ admin_route('inquiries.update', $inquiry) }}" method="POST">@csrf @method('PUT')
                <div class="mb-3"><label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['pending','contacted','closed'] as $s)
                        <option value="{{ $s }}" @selected($inquiry->status===$s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Admin Note</label><textarea name="note" class="form-control" rows="3">{{ $inquiry->note }}</textarea></div>
                <button type="submit" class="btn btn-primary w-100">Update</button>
            </form>
        </div></div>
    </div>
</div>
@endsection
