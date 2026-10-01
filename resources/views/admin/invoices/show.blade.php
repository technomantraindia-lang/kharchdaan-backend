@extends('admin.layouts.app')
@section('title', 'Invoice ' . $invoice->inv_num)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Invoice {{ $invoice->inv_num }}</h2>
    <a href="{{ admin_route('invoices.index') }}" class="btn btn-secondary">Back</a>
</div>
<div class="card">
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h5 class="fw-bold text-primary">KharchDaan.Com</h5>
                <div class="text-muted small">"तेरा तुझको अर्पण"</div>
                <p class="text-muted mt-1">Invoice: <strong>{{ $invoice->inv_num }}</strong><br>Date: {{ $invoice->created_at->format('M d, Y') }}</p>
            </div>
            <div class="col-md-6 text-end">
                <h5>Bill To</h5>
                <p>{{ $invoice->order?->user?->name }}<br>{{ $invoice->order?->user?->email }}<br>{{ $invoice->order?->user?->phone }}</p>
            </div>
        </div>
        @if($invoice->order)
        <table class="table">
            <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
            <tbody>
                @foreach($invoice->order->items as $item)
                <tr>
                    <td>{{ $item->product?->name ?? 'Product' }}</td>
                    <td>{{ $item->qty }}</td>
                    <td>₹{{ number_format($item->price, 2) }}</td>
                    <td>₹{{ number_format($item->qty * $item->price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td colspan="3" class="text-end"><strong>Total</strong></td><td><strong>₹{{ number_format($invoice->order->total, 2) }}</strong></td></tr>
            </tfoot>
        </table>
        <a href="{{ admin_route('orders.show', $invoice->order) }}" class="btn btn-sm btn-primary">View Order</a>
        @endif
    </div>
</div>
@endsection
