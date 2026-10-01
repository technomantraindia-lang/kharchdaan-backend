@extends('admin.layouts.app')
@section('title', 'Payments')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Payments</h2>
    <form class="d-flex" method="GET">
        <select name="status" class="form-select">
            <option value="">All Status</option>
            @foreach(['pending','paid','failed','refunded'] as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-primary ms-2">Filter</button>
    </form>
</div>
<div class="card"><div class="card-body">
    <table class="table table-hover">
        <thead><tr><th>ID</th><th>Order #</th><th>Customer</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
            @forelse ($payments as $payment)
            <tr>
                <td>#{{ $payment->id }}</td>
                <td>{{ $payment->order?->order_num ?? '-' }}</td>
                <td>{{ $payment->order?->user?->name ?? '-' }}</td>
                <td>₹{{ number_format($payment->amount, 2) }}</td>
                <td>{{ strtoupper(str_replace('_', ' ', $payment->method)) }}</td>
                <td><span class="badge bg-{{ $payment->status==='paid'?'success':($payment->status==='failed'?'danger':'warning') }}">{{ ucfirst($payment->status) }}</span></td>
                <td>{{ $payment->created_at->format('M d, Y') }}</td>
                <td><a href="{{ admin_route('payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted">No payments found</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $payments->links() }}
</div></div>
@endsection
