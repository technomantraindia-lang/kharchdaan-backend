@extends('admin.layouts.app')
@section('title', 'Edit Shipping Method')
@section('content')
<h2 class="mb-4">Edit Shipping Method</h2>
<div class="card"><div class="card-body">
    <form action="{{ admin_route('shipping.update', $method) }}" method="POST">@csrf @method('PUT')
        @include('admin.shipping._form')
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ admin_route('shipping.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div></div>
@endsection
