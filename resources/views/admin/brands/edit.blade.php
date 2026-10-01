@extends('admin.layouts.app')

@section('title', 'Edit Brand')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0">Edit Brand: {{ $brand->name }}</h3>
    <a href="{{ admin_route('brands.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back to Brands
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ admin_route('brands.update', $brand) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Brand Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $brand->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Slug</label>
                    <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $brand->slug) }}">
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Brand Logo</label>
                    <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                    @if($brand->image)
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <span class="small text-muted">Current:</span>
                            <img src="{{ route('media.file', ['path' => $brand->image]) }}" alt="{{ $brand->name }}" class="rounded border" style="width: 40px; height: 40px; object-fit: contain;">
                        </div>
                    @endif
                    @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active" @selected(old('status', $brand->status) === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $brand->status) === 'inactive')>Inactive</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4">{{ old('description', $brand->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Brand</button>
                <a href="{{ admin_route('brands.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
