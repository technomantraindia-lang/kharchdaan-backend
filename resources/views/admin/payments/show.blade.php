@extends('admin.layouts.app')
@section('title', 'Payment #' . $payment->id)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Payment #{{ $payment->id }}</h2>
    <a href="{{ admin_route('payments.index') }}" class="btn btn-secondary">Back</a>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4"><div class="card-header"><h5>Payment Details</h5></div><div class="card-body">
            <p><strong>Amount:</strong> ₹{{ number_format($payment->amount, 2) }}<br>
            <strong>Method:</strong> {{ strtoupper(str_replace('_', ' ', $payment->method)) }}<br>
            <strong>Status:</strong> <span class="badge bg-{{ $payment->status==='paid'?'success':'warning' }}">{{ ucfirst($payment->status) }}</span><br>
            <strong>Transaction ID:</strong> {{ $payment->txn_id ?? '-' }}<br>
            <strong>Date:</strong> {{ $payment->created_at->format('M d, Y H:i') }}</p>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-header"><h5>Related Order</h5></div><div class="card-body">
            @if($payment->order)
            <p><strong>Order #:</strong> {{ $payment->order->order_num }}<br>
            <strong>Customer:</strong> {{ $payment->order->user?->name }}<br>
            <strong>Order Total:</strong> ₹{{ number_format($payment->order->total, 2) }}</p>
            <a href="{{ admin_route('orders.show', $payment->order) }}" class="btn btn-sm btn-primary">View Order</a>
            @else
            <p class="text-muted">No order linked</p>
            @endif
        </div></div>
    </div>
</div>
@endsection
