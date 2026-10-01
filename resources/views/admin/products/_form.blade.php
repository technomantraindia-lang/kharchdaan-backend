<div class="row">
    <div class="col-md-8 mb-3">
        <label class="form-label">Product Name *</label>
        <input type="text" name="name" id="productName" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Slug</label>
        <input type="text" name="slug" id="productSlug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $product->slug ?? '') }}" placeholder="Auto-generated if empty">
        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">SKU *</label>
        <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $product->sku ?? '') }}" required>
        @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Category *</label>
        <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
            <option value="">Select Category</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id ?? '') == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Sub Category</label>
        <select name="sub_category_id" id="sub_category_id" class="form-select @error('sub_category_id') is-invalid @enderror">
            <option value="">None</option>
            @foreach($subCategories as $sub)
                <option value="{{ $sub->id }}" data-parent="{{ $sub->parent_id }}" @selected(old('sub_category_id', $product->sub_category_id ?? '') == $sub->id)>{{ $sub->name }}</option>
            @endforeach
        </select>
        @error('sub_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Brand</label>
        <select name="brand_id" class="form-select">
            <option value="">None</option>
            @foreach($brands as $brand)
                <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id ?? '') == $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 mb-3">
        <label class="form-label">HSN Code</label>
        <input type="text" name="hsn_code" class="form-control" value="{{ old('hsn_code', $product->hsn_code ?? '') }}" placeholder="e.g. 1006">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Regular Price (&#8377;) *</label>
        <input type="number" step="0.01" name="price" id="productPrice" class="form-control" value="{{ old('price', $product->price ?? '') }}" required>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Sale Price (&#8377;)</label>
        <input type="number" step="0.01" name="sale_price" id="productSalePrice" class="form-control" value="{{ old('sale_price', $product->sale_price ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label text-primary">Cost Price (&#8377;) <small class="text-muted">(Internal Only)</small></label>
        <input type="number" step="0.01" name="cost_price" id="productCostPrice" class="form-control" value="{{ old('cost_price', $product->cost_price ?? '') }}" placeholder="0.00">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">GST %</label>
        <input type="number" step="0.01" name="gst_percentage" id="gstPercentage" class="form-control" value="{{ old('gst_percentage', $product->gst_percentage ?? 5) }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Physical Stock *</label>
        <input type="number" name="stock_qty" class="form-control" value="{{ old('stock_qty', $product->stock_qty ?? 0) }}" required>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Reserved Stock</label>
        <input type="number" class="form-control bg-light" value="{{ $product->reserved_stock ?? 0 }}" readonly>
        <small class="text-muted">Reserved by orders/cart</small>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Available Stock</label>
        <input type="number" class="form-control bg-light fw-bold text-success" value="{{ isset($product) ? $product->available_stock : 0 }}" readonly>
        <small class="text-muted">Physical - Reserved</small>
    </div>
    <div class="col-md-2 mb-3">
        <label class="form-label">Unit</label>
        <select name="unit" class="form-select">
            @foreach($units as $u)
                <option value="{{ $u }}" @selected(old('unit', $product->unit ?? 'pcs') === $u)>{{ $u }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 mb-3">
        <label class="form-label">Min Order Qty</label>
        <input type="number" name="min_order_qty" class="form-control" value="{{ old('min_order_qty', $product->min_order_qty ?? 1) }}" min="1">
    </div>
    <div class="col-md-2 mb-3">
        <label class="form-label">Low Stock Alert</label>
        <input type="number" name="low_stock_qty" class="form-control" value="{{ old('low_stock_qty', $product->low_stock_qty ?? 5) }}">
    </div>
    <div class="col-md-2 mb-3">
        <label class="form-label">Weight (kg)</label>
        <input type="number" step="0.01" name="weight" class="form-control" value="{{ old('weight', $product->weight ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Status *</label>
        <select name="status" class="form-select" required>
            <option value="active" @selected(old('status', $product->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $product->status ?? '') === 'inactive')>Inactive</option>
        </select>
    </div>
    <div class="col-md-3 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input type="checkbox" name="featured" value="1" class="form-check-input" id="featured" @checked(old('featured', $product->featured ?? false))>
            <label class="form-check-label" for="featured">Featured Product</label>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">GST Amount (&#8377;)</label>
        <input type="text" id="gstAmount" class="form-control" value="0.00" readonly>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Price After GST (&#8377;)</label>
        <input type="text" id="priceAfterGst" class="form-control" value="0.00" readonly>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Main Product Image</label>
        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp">
        @if(isset($product) && $product->image)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="mt-2 rounded" style="max-height:120px;">
        @endif
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Gallery Images</label>
        <input type="file" name="gallery[]" id="galleryImages" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp" multiple>
        <small class="text-muted d-block mt-1">Select up to 10 images at a time.</small>
        <div id="galleryPreview" class="d-flex flex-wrap gap-2 mt-3"></div>
        @if(isset($product) && $product->images->count())
            <div class="d-flex flex-wrap gap-2 mt-2">
                @foreach($product->images as $img)
                    <div class="position-relative border rounded p-1">
                        <img src="{{ $img->image_url }}" alt="{{ $product->name }} gallery image" style="width:80px;height:80px;object-fit:cover;" class="rounded">
                        <label class="d-block small text-danger mt-1"><input type="checkbox" name="remove_gallery[]" value="{{ $img->id }}"> Remove</label>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    <div class="col-md-12 mb-3">
        <label class="form-label">Short Description</label>
        <textarea name="short_desc" class="form-control" rows="2">{{ old('short_desc', $product->short_desc ?? '') }}</textarea>
    </div>
    <div class="col-md-12 mb-3">
        <label class="form-label">Full Description</label>
        <textarea name="description" class="form-control" rows="5">{{ old('description', $product->description ?? '') }}</textarea>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Meta Title</label>
        <input type="text" name="seo_title" class="form-control" value="{{ old('seo_title', $product->seo_title ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Meta Keywords</label>
        <input type="text" name="seo_keywords" class="form-control" value="{{ old('seo_keywords', $product->seo_keywords ?? '') }}">
    </div>
    <div class="col-md-12 mb-3">
        <label class="form-label">Meta Description</label>
        <textarea name="seo_desc" class="form-control" rows="2">{{ old('seo_desc', $product->seo_desc ?? '') }}</textarea>
    </div>
</div>
<script>
document.getElementById('productName')?.addEventListener('input', function () {
    const slug = document.getElementById('productSlug');
    if (!slug.dataset.edited) {
        slug.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }
});

document.getElementById('productSlug')?.addEventListener('input', function () {
    this.dataset.edited = '1';
});

document.addEventListener('DOMContentLoaded', function () {
    const priceInput = document.getElementById('productPrice');
    const salePriceInput = document.getElementById('productSalePrice');
    const gstPercentageInput = document.getElementById('gstPercentage');
    const gstAmountInput = document.getElementById('gstAmount');
    const priceAfterGstInput = document.getElementById('priceAfterGst');
    const galleryInput = document.getElementById('galleryImages');
    const galleryPreview = document.getElementById('galleryPreview');

    function formatAmount(value) {
        return (Number.isFinite(value) ? value : 0).toFixed(2);
    }

    function updateGstCalculation() {
        if (!priceInput || !gstPercentageInput || !gstAmountInput || !priceAfterGstInput) return;

        const price = parseFloat(priceInput.value) || 0;
        const salePrice = parseFloat(salePriceInput?.value) || 0;
        const gstPercentage = parseFloat(gstPercentageInput.value) || 0;
        const basePrice = salePrice > 0 ? salePrice : price;
        const gstAmount = (basePrice * gstPercentage) / 100;

        gstAmountInput.value = formatAmount(gstAmount);
        priceAfterGstInput.value = formatAmount(basePrice + gstAmount);
    }

    [priceInput, salePriceInput, gstPercentageInput].forEach((input) => {
        input?.addEventListener('input', updateGstCalculation);
        input?.addEventListener('change', updateGstCalculation);
    });

    function renderGalleryPreview(files) {
        if (!galleryPreview) return;

        galleryPreview.innerHTML = '';

        Array.from(files).slice(0, 10).forEach((file) => {
            const card = document.createElement('div');
            card.className = 'border rounded p-2 bg-white';
            card.style.width = '120px';

            const img = document.createElement('img');
            img.alt = file.name;
            img.className = 'rounded mb-2';
            img.style.width = '100%';
            img.style.height = '100px';
            img.style.objectFit = 'cover';
            img.src = URL.createObjectURL(file);

            img.onload = () => URL.revokeObjectURL(img.src);

            const name = document.createElement('div');
            name.className = 'small text-muted text-truncate';
            name.title = file.name;
            name.textContent = file.name;

            card.appendChild(img);
            card.appendChild(name);
            galleryPreview.appendChild(card);
        });
    }

    galleryInput?.addEventListener('change', function () {
        const files = Array.from(this.files || []);
        if (files.length > 10) {
            alert('Please select up to 10 gallery images at a time.');
            this.value = '';
            if (galleryPreview) galleryPreview.innerHTML = '';
            return;
        }

        renderGalleryPreview(files);
    });

    updateGstCalculation();
});
</script>
