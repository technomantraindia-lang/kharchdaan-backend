@extends('admin.layouts.app')
@section('title', 'Edit Banner')
@section('content')
<h2 class="mb-4">Edit Banner</h2>
<div class="card"><div class="card-body">
    <form action="{{ admin_route('banners.update', $banner) }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT')
        @include('admin.banners._form')
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ admin_route('banners.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div></div>
@endsection
