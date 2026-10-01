@extends('admin.layouts.app')
@section('title', 'Add Banner')
@section('content')
<h2 class="mb-4">Add Banner</h2>
<div class="card"><div class="card-body">
    <form action="{{ admin_route('banners.store') }}" method="POST" enctype="multipart/form-data">@csrf
        @include('admin.banners._form')
        <button type="submit" class="btn btn-primary">Create</button>
        <a href="{{ admin_route('banners.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div></div>
@endsection
