@extends('admin.layouts.app')
@section('title', 'Add Page')
@section('content')
<h2 class="mb-4">Add Page</h2>
<div class="card"><div class="card-body">
    <form action="{{ admin_route('pages.store') }}" method="POST">@csrf
        @include('admin.pages._form')
        <button type="submit" class="btn btn-primary">Create</button>
        <a href="{{ admin_route('pages.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div></div>
@endsection
