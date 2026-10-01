@extends('admin.layouts.app')

@section('title', 'Add Category')

@section('content')
<h2 class="mb-4">Add Category</h2>
<div class="card"><div class="card-body">
    <form action="{{ admin_route('categories.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('admin.categories._form')
        <button type="submit" class="btn btn-primary">Create</button>
        <a href="{{ admin_route('categories.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div></div>
@endsection
