@extends('admin.layouts.app')
@section('title', 'Inquiries')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Customer Inquiries</h2>
    <form class="d-flex" method="GET">
        <select name="status" class="form-select">
            <option value="">All Status</option>
            @foreach(['pending','contacted','closed'] as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-primary ms-2">Filter</button>
    </form>
</div>
<div class="card"><div class="card-body">
    <table class="table table-hover">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Product</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
            @forelse ($inquiries as $inquiry)
            <tr>
                <td><strong>{{ $inquiry->name }}</strong></td>
                <td>{{ $inquiry->email }}</td>
                <td>{{ $inquiry->phone }}</td>
                <td>{{ $inquiry->product?->name ?? 'General' }}</td>
                <td><span class="badge bg-{{ $inquiry->status==='pending'?'warning':($inquiry->status==='contacted'?'info':'success') }}">{{ ucfirst($inquiry->status) }}</span></td>
                <td>{{ $inquiry->created_at->format('M d, Y') }}</td>
                <td><a href="{{ admin_route('inquiries.show', $inquiry) }}" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted">No inquiries found</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $inquiries->links() }}
</div></div>
@endsection
