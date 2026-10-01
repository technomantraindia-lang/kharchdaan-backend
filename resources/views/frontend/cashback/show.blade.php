@extends('frontend.layouts.app')

@section('title', 'Cashback Details')

@section('content')
<div class="py-4">
    <a href="{{ route('cashback.index') }}">&larr; Back to cashback</a>
    <h1 class="mt-3">Cashback for Order {{ $cashback->order?->order_num ?? $cashback->order_id }}</h1>
    @include('frontend.cashback._card', ['cashback' => $cashback])
    <p class="text-muted small">Cashback is conditional, is not Direct Selling income or PV, and is not payable unless selected and approved from an available company profit pool.</p>
</div>
@endsection
