@extends('admin.layouts.app')
@section('title', 'Edit Coupon')
@section('content')
<h2 class="mb-4">Edit Coupon</h2>
<div class="card"><div class="card-body">
    <form action="{{ admin_route('coupons.update', $coupon) }}" method="POST">@csrf @method('PUT')
        @include('admin.coupons._form')
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ admin_route('coupons.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div></div>
@endsection
