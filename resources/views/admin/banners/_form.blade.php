<div class="mb-3"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" value="{{ old('title', $banner->title ?? '') }}" required></div>
<div class="mb-3"><label class="form-label">Banner Image *</label>
    <input type="file" name="image" class="form-control" accept="image/*" {{ !isset($banner) ? 'required' : '' }}>
    @if(isset($banner) && $banner->image)<img src="{{ asset('storage/'.$banner->image) }}" class="mt-2 rounded" style="max-height:120px;">@endif
</div>
<div class="mb-3"><label class="form-label">Button Text</label><input type="text" name="button_text" class="form-control" value="{{ old('button_text', $banner->button_text ?? '') }}" placeholder="Shop Now"></div>
<div class="mb-3"><label class="form-label">Button Link</label><input type="text" name="link" class="form-control" value="{{ old('link', $banner->link ?? '') }}" placeholder="/products"></div>
<div class="mb-3"><label class="form-label">Sort Order</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" min="0"></div>
<div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" @selected(old('status', $banner->status ?? 'active')==='active')>Active</option><option value="inactive" @selected(old('status', $banner->status ?? '')==='inactive')>Inactive</option></select></div>
