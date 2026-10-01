@extends('admin.layouts.app')
@section('title', 'Add Tax Rate')
@section('content')
<h2 class="mb-4">Add Tax Rate</h2>
<div class="card"><div class="card-body">
    <form action="{{ admin_route('taxes.store') }}" method="POST">@csrf
        <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div>
        <div class="mb-3"><label class="form-label">Percentage *</label><input type="number" step="0.01" name="percentage" class="form-control" value="{{ old('percentage') }}" required></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="desc" class="form-control" rows="2">{{ old('desc') }}</textarea></div>
        <div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
        <button type="submit" class="btn btn-primary">Create</button>
        <a href="{{ admin_route('taxes.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div></div>
@endsection
