@extends('frontend.layouts.app')

@section('title', 'My Cashback')

@section('content')
<div class="py-4">
    <h1 class="mb-2">My Cashback</h1>
    <p class="text-muted">Cashback is conditional and depends on company profit availability and approval. It is not an instant or guaranteed payment.</p>

    @forelse($cashbacks as $cashback)
        @include('frontend.cashback._card', ['cashback' => $cashback])
    @empty
        <div class="alert alert-light border">No cashback records are available for your account.</div>
    @endforelse

    {{ $cashbacks->links() }}
</div>
@endsection
