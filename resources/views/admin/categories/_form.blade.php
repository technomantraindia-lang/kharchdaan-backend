<div class="mb-3">
    <label class="form-label">Name *</label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name ?? '') }}" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label">Parent Category (for sub-category)</label>
    <select name="parent_id" class="form-select">
        <option value="">None (Top Level)</option>
        @foreach ($categoryOptions as $option)
        <option value="{{ $option['id'] }}" @selected(old('parent_id', $category->parent_id ?? '') == $option['id'])>{{ $option['label'] }}</option>
        @endforeach
    </select>
    <div class="form-text">Choose a parent category to make this a sub-category. Leave blank for a top-level category.</div>
</div>
<div class="mb-3">
    <label class="form-label">Category Image</label>
    <input type="file" name="image" class="form-control" accept="image/*">
    @if(isset($category) && $category->image)
    <img src="{{ asset('storage/'.$category->image) }}" class="mt-2 rounded" style="max-height:100px;">
    @endif
</div>
<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description', $category->description ?? '') }}</textarea>
</div>
<div class="mb-3">
    <label class="form-label">Status *</label>
    <select name="status" class="form-select" required>
        <option value="active" @selected(old('status', $category->status ?? 'active')==='active')>Active</option>
        <option value="inactive" @selected(old('status', $category->status ?? '')==='inactive')>Inactive</option>
    </select>
</div>
