@extends('admin.layouts.app')
@section('title', 'Invoices')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Invoices</h2>
    <form class="d-flex" method="GET">
        <input type="text" name="search" class="form-control" placeholder="Search invoice number..." value="{{ request('search') }}">
        <button class="btn btn-outline-primary ms-2">Search</button>
    </form>
</div>
<div class="card"><div class="card-body">
    <table class="table table-hover">
        <thead><tr><th>Invoice #</th><th>Order #</th><th>Customer</th><th>Order Total</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
            @forelse ($invoices as $invoice)
            <tr>
                <td><strong>{{ $invoice->inv_num }}</strong></td>
                <td>{{ $invoice->order?->order_num ?? '-' }}</td>
                <td>{{ $invoice->order?->user?->name ?? '-' }}</td>
                <td>₹{{ number_format($invoice->order?->total ?? 0, 2) }}</td>
                <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                <td><a href="{{ admin_route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted">No invoices found</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $invoices->links() }}
</div></div>
@endsection
