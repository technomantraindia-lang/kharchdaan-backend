@extends('admin.layouts.app')

@section('title', 'Edit Category')

@section('content')
<h2 class="mb-4">Edit Category</h2>
<div class="card"><div class="card-body">
    <form action="{{ admin_route('categories.update', $category) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.categories._form')
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ admin_route('categories.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div></div>
@endsection
