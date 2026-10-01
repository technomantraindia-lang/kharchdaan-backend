@extends('admin.layouts.app')
@section('title', 'Add Coupon')
@section('content')
<h2 class="mb-4">Add Coupon</h2>
<div class="card"><div class="card-body">
    <form action="{{ admin_route('coupons.store') }}" method="POST">@csrf
        @include('admin.coupons._form')
        <button type="submit" class="btn btn-primary">Create</button>
        <a href="{{ admin_route('coupons.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div></div>
@endsection
