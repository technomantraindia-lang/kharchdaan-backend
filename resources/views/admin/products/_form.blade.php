<div class="row">
    <!-- 1. Basic Product Info -->
    <div class="col-12 mb-2">
        <h5 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-2">
            <i class="fas fa-info-circle text-blue-500"></i> General Information
        </h5>
    </div>

    <div class="col-md-8 mb-3">
        <label class="form-label">Product Name *</label>
        <input type="text" name="name" id="productName" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name ?? '') }}" required placeholder="e.g. Premium Basmati Rice / Pure Desi Ghee">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Slug</label>
        <input type="text" name="slug" id="productSlug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $product->slug ?? '') }}" placeholder="Auto-generated if empty">
        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Main Base SKU *</label>
        <input type="text" name="sku" id="productBaseSku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $product->sku ?? '') }}" required placeholder="e.g. BG-RICE-01">
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
            <option value="">None / House Brand</option>
            @foreach($brands as $brand)
                <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id ?? '') == $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label m-0 font-semibold text-slate-700">HSN Code & GST Category *</label>
            <span class="inline-flex items-center gap-1 text-[10px] text-amber-800 font-bold bg-amber-50 border border-amber-300 px-2 py-0.5 rounded-full shadow-2xs" id="autoHsnBadge" style="display:none;">
                <i class="fas fa-wand-magic-sparkles text-[9px] text-amber-600"></i> Auto-Matched
            </span>
        </div>
        @php
            $currentHsn = old('hsn_code', $product->hsn_code ?? '');
            $hsnList = $hsnCodes ?? \App\Http\Controllers\Admin\ProductController::getHsnCodesList();
            $groupedHsn = collect($hsnList)->groupBy('category');
            $selectedHsnItem = collect($hsnList)->firstWhere('code', $currentHsn);
            $isKnownHsn = !empty($selectedHsnItem);
            $catIcons = [
                'Grains, Rice & Cereals' => 'fa-wheat-awn text-amber-600',
                'Pulses & Dals' => 'fa-seedling text-emerald-600',
                'Dairy Products & Ghee' => 'fa-cow text-amber-700',
                'Honey & Natural Sweeteners' => 'fa-jar text-amber-500',
                'Beverages, Tea & Coffee' => 'fa-mug-hot text-amber-800',
                'Spices & Seasonings' => 'fa-pepper-hot text-rose-500',
                'Cooking & Edible Oils' => 'fa-bottle-droplet text-amber-500',
                'Sugar, Jaggery & Sweeteners' => 'fa-cubes-stacked text-amber-700',
                'Snacks & Bakery' => 'fa-cookie-bite text-orange-500',
                'Dry Fruits & Nuts' => 'fa-bowl-food text-amber-700',
                'Ayurveda & Health Wellness' => 'fa-mortar-pestle text-emerald-600',
                'Puja, Spiritual & Incense' => 'fa-om text-orange-600',
                'Personal Care & Hygiene' => 'fa-pump-soap text-blue-500',
                'Cosmetics & Skincare' => 'fa-sparkles text-pink-500',
                'Apparel, Khadi & Textiles' => 'fa-shirt text-indigo-500',
                'Home Care & Cleaning' => 'fa-broom text-teal-600',
                'Packaged & Health Foods' => 'fa-box-open text-orange-600',
                'General Goods' => 'fa-boxes-stacked text-slate-500',
                'Tax-Exempt Produce' => 'fa-leaf text-emerald-600',
            ];
        @endphp

        <!-- Hidden input storing selected value -->
        <input type="hidden" name="hsn_code" id="productHsnCode" value="{{ $currentHsn }}">

        <!-- Premium Custom Dropdown Wrapper -->
        <div class="position-relative" id="premiumHsnDropdown">
            <!-- Dropdown Trigger Button -->
            <button type="button" id="hsnDropdownTrigger" class="hsn-dropdown-trigger">
                <div class="d-flex align-items-center gap-2 text-truncate me-2" id="hsnTriggerDisplay">
                    @if($selectedHsnItem)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-900 text-white font-mono text-[11px] font-semibold tracking-wide flex-shrink-0 shadow-2xs">
                            HSN {{ $selectedHsnItem['code'] }}
                        </span>
                        <span class="text-xs text-slate-900 font-semibold text-truncate">
                            {{ $selectedHsnItem['name'] }}
                        </span>
                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2 py-0.5 ms-auto flex-shrink-0">
                            {{ $selectedHsnItem['gst'] }}% GST
                        </span>
                    @elseif($currentHsn)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-700 text-white font-mono text-[11px] font-semibold flex-shrink-0">
                            HSN {{ $currentHsn }}
                        </span>
                        <span class="text-xs text-slate-800 font-semibold text-truncate">Custom HSN Code</span>
                        <span class="badge bg-slate-100 text-slate-600 border border-slate-200 text-[10px] ms-auto flex-shrink-0">Custom</span>
                    @else
                        <span class="text-xs text-slate-400 d-flex align-items-center gap-2 font-normal">
                            <i class="fas fa-barcode text-slate-400 text-sm"></i>
                            <span>Select Product HSN & Tax Rate...</span>
                        </span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-1.5 flex-shrink-0 ms-2">
                    <div class="w-6 h-6 rounded-full bg-slate-100 d-flex align-items-center justify-content-center text-slate-400 transition" id="hsnChevronWrap">
                        <i class="fas fa-chevron-down text-[10px] transition-transform" id="hsnChevron"></i>
                    </div>
                </div>
            </button>

            <!-- Dropdown Menu Panel -->
            <div id="hsnDropdownPanel" class="d-none hsn-dropdown-menu" style="min-width: 350px;">
                
                <!-- Search Input & GST Filter Chips -->
                <div class="p-3 bg-slate-50/90 border-bottom border-slate-100">
                    <div class="position-relative mb-2">
                        <i class="fas fa-search position-absolute text-slate-400 text-xs" style="left: 12px; top: 11px;"></i>
                        <input type="text" id="hsnSearchInput" placeholder="Search HSN code, product, category..." class="form-control form-control-sm ps-4 pe-4 bg-white border border-slate-200 text-xs rounded-lg" style="height: 34px; padding-left: 32px !important;" autocomplete="off">
                        <button type="button" id="hsnSearchClear" class="d-none position-absolute border-0 bg-transparent text-slate-400 p-0 text-xs hover-text-slate-600" style="right: 10px; top: 8px;">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>

                    <!-- GST Quick Filter Pills -->
                    <div class="d-flex align-items-center gap-1 overflow-x-auto pb-0.5" id="hsnGstChips">
                        <button type="button" class="hsn-rate-chip is-active" data-gst-filter="all">All</button>
                        <button type="button" class="hsn-rate-chip" data-gst-filter="0">0% GST</button>
                        <button type="button" class="hsn-rate-chip" data-gst-filter="5">5% GST</button>
                        <button type="button" class="hsn-rate-chip" data-gst-filter="12">12% GST</button>
                        <button type="button" class="hsn-rate-chip" data-gst-filter="18">18% GST</button>
                    </div>
                </div>

                <!-- Items Scrollable List -->
                <div class="overflow-y-auto p-2" id="hsnOptionsList" style="max-height: 280px;">
                    @foreach($groupedHsn as $categoryName => $items)
                        @php $iconClass = $catIcons[$categoryName] ?? 'fa-tag text-slate-400'; @endphp
                        <div class="hsn-category-group mb-2">
                            <div class="px-2.5 py-1 text-[10px] font-bold text-uppercase text-slate-500 bg-slate-100/80 rounded-md mb-1 d-flex justify-content-between align-items-center">
                                <span class="d-flex align-items-center gap-1.5">
                                    <i class="fas {{ $iconClass }}"></i>
                                    <span>{{ $categoryName }}</span>
                                </span>
                                <span class="text-[9px] text-slate-400 font-normal">{{ count($items) }} items</span>
                            </div>
                            <div class="category-items">
                                @foreach($items as $hsn)
                                    <div class="hsn-item-row {{ $currentHsn == $hsn['code'] ? 'is-selected' : '' }}"
                                         data-code="{{ $hsn['code'] }}"
                                         data-name="{{ $hsn['name'] }}"
                                         data-category="{{ $categoryName }}"
                                         data-gst="{{ $hsn['gst'] }}">
                                        <div class="d-flex align-items-center gap-2 min-w-0 pe-2">
                                            <span class="badge bg-slate-900 text-white font-mono text-[11px] px-2 py-0.5 rounded-md flex-shrink-0 shadow-2xs">
                                                {{ $hsn['code'] }}
                                            </span>
                                            <span class="text-xs text-slate-800 font-medium text-truncate">
                                                {{ $hsn['name'] }}
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                            <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2 py-0.5">
                                                {{ $hsn['gst'] }}% GST
                                            </span>
                                            @if($currentHsn == $hsn['code'])
                                                <i class="fas fa-check-circle text-emerald-600 text-xs check-icon ms-1"></i>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- No Results State -->
                <div id="hsnNoResults" class="d-none p-4 text-center text-slate-400">
                    <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 d-inline-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-search text-base"></i>
                    </div>
                    <div class="text-xs font-semibold text-slate-700">No matching HSN code found</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Try a different keyword or enter a custom HSN code.</div>
                    <button type="button" id="btnUseCustomFromSearch" class="btn btn-sm btn-outline-primary mt-2.5 px-3 py-1 text-xs">
                        <i class="fas fa-pen me-1"></i> Enter Custom HSN
                    </button>
                </div>

                <!-- Bottom Footer Bar -->
                <div class="p-2 bg-slate-50 border-top border-slate-100 d-flex align-items-center justify-content-between text-[11px]">
                    <button type="button" id="btnCustomHsnTrigger" class="btn btn-sm btn-link text-slate-600 p-0 text-decoration-none d-flex align-items-center gap-1.5 hover-text-orange-600">
                        <i class="fas fa-pen-to-square text-orange-500"></i>
                        <span class="font-semibold">Enter Custom HSN Code</span>
                    </button>
                    <span class="text-slate-400 font-mono text-[10px]" id="hsnItemCountBadge">
                        {{ count($hsnList) }} categories
                    </span>
                </div>

            </div>
        </div>

        <!-- Custom HSN Input Container -->
        <div id="customHsnContainer" class="mt-2.5 p-2.5 bg-amber-50/60 border border-amber-200/80 rounded-xl {{ $currentHsn && !$isKnownHsn ? '' : 'd-none' }}">
            <div class="d-flex align-items-center justify-content-between mb-1.5">
                <span class="text-xs font-bold text-amber-900 d-flex align-items-center gap-1.5">
                    <i class="fas fa-pen-clip text-amber-600"></i> Manual Custom HSN Code
                </span>
                <button type="button" id="btnCancelCustomHsn" class="btn btn-link text-slate-400 p-0 text-[11px] text-decoration-none hover-text-slate-600">
                    <i class="fas fa-times"></i> Revert
                </button>
            </div>
            <div class="position-relative">
                <input type="text" name="custom_hsn_code" id="customHsnInput" class="form-control form-control-sm pe-4 font-mono font-bold text-slate-800" placeholder="e.g. 2105, 3302, 8471" value="{{ $currentHsn && !$isKnownHsn ? $currentHsn : '' }}" maxlength="20">
                <span class="position-absolute text-[11px] text-slate-400 font-mono font-semibold" style="right: 10px; top: 7px;">HSN</span>
            </div>
            <div class="text-[11px] text-amber-800/80 mt-1 d-flex align-items-center gap-1">
                <i class="fas fa-info-circle text-[10px]"></i> Saved directly with product for invoice & GST billing.
            </div>
        </div>

        @error('hsn_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Status *</label>
        <select name="status" class="form-select" required>
            <option value="active" @selected(old('status', $product->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $product->status ?? '') === 'inactive')>Inactive</option>
        </select>
    </div>

    <!-- 2. Base Pricing & Stock Settings -->
    <div class="col-12 mt-3 mb-2">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="text-xs font-bold uppercase tracking-wider text-slate-500 m-0 flex items-center gap-2">
                <i class="fas fa-tag text-emerald-500"></i> Base Pricing & Inventory
            </h5>
            <span class="text-xs text-slate-400">Default pricing (overridden per variation if configured below)</span>
        </div>
        <hr class="my-2 border-slate-100">
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Base Regular Price (&#8377;) *</label>
        <input type="number" step="0.01" name="price" id="productPrice" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $product->price ?? '') }}" required placeholder="0.00">
        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Base Sale Price (&#8377;)</label>
        <input type="number" step="0.01" name="sale_price" id="productSalePrice" class="form-control" value="{{ old('sale_price', $product->sale_price ?? '') }}" placeholder="0.00 (Optional)">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label text-primary">Cost Price (&#8377;) <small class="text-muted">(Internal)</small></label>
        <input type="number" step="0.01" name="cost_price" id="productCostPrice" class="form-control" value="{{ old('cost_price', $product->cost_price ?? '') }}" placeholder="0.00">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">GST %</label>
        <input type="number" step="0.01" name="gst_percentage" id="gstPercentage" class="form-control" value="{{ old('gst_percentage', $product->gst_percentage ?? 5) }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Base Physical Stock *</label>
        <input type="number" name="stock_qty" id="productBaseStock" class="form-control @error('stock_qty') is-invalid @enderror" value="{{ old('stock_qty', $product->stock_qty ?? 0) }}" required min="0">
        @error('stock_qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
    <div class="col-md-3 mb-3">
        <label class="form-label">Unit</label>
        <select name="unit" class="form-select">
            @foreach($units as $u)
                <option value="{{ $u }}" @selected(old('unit', $product->unit ?? 'pcs') === $u)>{{ $u }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Min Order Qty</label>
        <input type="number" name="min_order_qty" class="form-control" value="{{ old('min_order_qty', $product->min_order_qty ?? 1) }}" min="1">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Low Stock Alert</label>
        <input type="number" name="low_stock_qty" class="form-control" value="{{ old('low_stock_qty', $product->low_stock_qty ?? 5) }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Weight (kg)</label>
        <input type="number" step="0.01" name="weight" class="form-control" value="{{ old('weight', $product->weight ?? '') }}" placeholder="e.g. 1.0">
    </div>
    <div class="col-md-3 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input type="checkbox" name="featured" value="1" class="form-check-input" id="featured" @checked(old('featured', $product->featured ?? false))>
            <label class="form-check-label font-semibold text-slate-700" for="featured">Featured Product</label>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">GST Tax Amount (&#8377;)</label>
        <input type="text" id="gstAmount" class="form-control bg-slate-50" value="0.00" readonly>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Estimated Price After GST (&#8377;)</label>
        <input type="text" id="priceAfterGst" class="form-control bg-slate-50 font-bold text-slate-800" value="0.00" readonly>
    </div>

    <!-- 3. Product Attributes & Variations Matrix -->
    <div class="col-12 mt-4 mb-2">
        <div class="card border border-indigo-200 bg-indigo-50/30 rounded-xl overflow-hidden shadow-2xs">
            <div class="card-header bg-white border-b border-indigo-100 p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 m-0 flex items-center gap-2">
                            <i class="fas fa-layer-group text-indigo-600"></i> Product Attributes & Price Variations
                            <span id="variationCountBadge" class="badge bg-indigo-100 text-indigo-700 text-[11px] px-2 py-0.5 rounded-full font-semibold">
                                {{ isset($product) && $product->variations ? $product->variations->count() : 0 }} configured
                            </span>
                        </h4>
                        <p class="text-xs text-slate-500 m-0 mt-1">
                            Add attribute variations (e.g. Weight: <code>500g</code>, <code>1kg</code> or Pack Size: <code>Small</code>, <code>Large</code>) with separate prices and stock levels.
                        </p>
                    </div>
                    <div class="d-flex items-center gap-2 flex-wrap">
                        <select id="attrQuickSelect" class="form-select form-select-sm" style="max-width: 220px;">
                            <option value="">-- Choose Attribute --</option>
                            @foreach($attributes as $attr)
                                <option value="{{ $attr->id }}" data-name="{{ $attr->name }}" data-values="{{ json_encode($attr->values->pluck('value')) }}">
                                    {{ $attr->name }} ({{ $attr->values->pluck('value')->join(', ') }})
                                </option>
                            @endforeach
                        </select>
                        <button type="button" id="btnAutoGenerate" class="btn btn-sm btn-outline-indigo inline-flex items-center gap-1.5" title="Generate variation rows for all option values of selected attribute">
                            <i class="fas fa-bolt text-amber-500"></i> Auto-Generate
                        </button>
                        <button type="button" id="btnAddVariationRow" class="btn btn-sm btn-primary inline-flex items-center gap-1.5">
                            <i class="fas fa-plus"></i> Add Row
                        </button>
                        <button type="button" id="btnClearAllVariations" class="btn btn-sm btn-outline-danger" title="Clear all variation rows">
                            <i class="fas fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-xs" id="variationsTable">
                        <thead class="bg-slate-100/80 text-slate-700 font-semibold border-b border-slate-200">
                            <tr>
                                <th style="min-width: 140px;">Attribute</th>
                                <th style="min-width: 140px;">Option Value / Variant *</th>
                                <th style="min-width: 150px;">Variant SKU *</th>
                                <th style="min-width: 120px;">Regular Price (&#8377;) *</th>
                                <th style="min-width: 120px;">Sale Price (&#8377;)</th>
                                <th style="min-width: 110px;">Cost Price (&#8377;)</th>
                                <th style="min-width: 100px;">Stock Qty *</th>
                                <th style="min-width: 90px;">Weight (kg)</th>
                                <th style="min-width: 100px;">Status</th>
                                <th class="text-center" style="width: 50px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="variationsTableBody" class="divide-y divide-slate-100">
                            @php
                                $existingVariations = old('variations', isset($product) ? $product->variations : []);
                            @endphp

                            @forelse($existingVariations as $idx => $v)
                                @php
                                    $varId = is_array($v) ? ($v['id'] ?? null) : $v->id;
                                    $varAttrId = is_array($v) ? ($v['attr_id'] ?? null) : $v->attr_id;
                                    $varAttrVal = is_array($v) ? ($v['attr_val'] ?? '') : $v->attr_val;
                                    $varSku = is_array($v) ? ($v['sku'] ?? '') : $v->sku;
                                    $varPrice = is_array($v) ? ($v['price'] ?? '') : $v->price;
                                    $varSalePrice = is_array($v) ? ($v['sale_price'] ?? '') : $v->sale_price;
                                    $varCostPrice = is_array($v) ? ($v['cost_price'] ?? '') : $v->cost_price;
                                    $varStock = is_array($v) ? ($v['stock_qty'] ?? 0) : $v->stock_qty;
                                    $varWeight = is_array($v) ? ($v['weight'] ?? '') : $v->weight;
                                    $varStatus = is_array($v) ? ($v['status'] ?? 'active') : $v->status;
                                @endphp
                                <tr class="variation-row bg-white" data-index="{{ $idx }}">
                                    <td>
                                        @if($varId)
                                            <input type="hidden" name="variations[{{ $idx }}][id]" value="{{ $varId }}">
                                        @endif
                                        <select name="variations[{{ $idx }}][attr_id]" class="form-select form-select-sm var-attr-id">
                                            <option value="">Custom / Direct</option>
                                            @foreach($attributes as $attr)
                                                <option value="{{ $attr->id }}" @selected($varAttrId == $attr->id)>{{ $attr->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="variations[{{ $idx }}][attr_val]" class="form-control form-control-sm var-attr-val" value="{{ $varAttrVal }}" placeholder="e.g. 500g, 1kg, Large" required>
                                    </td>
                                    <td>
                                        <input type="text" name="variations[{{ $idx }}][sku]" class="form-control form-control-sm font-mono var-sku" value="{{ $varSku }}" placeholder="e.g. SKU-500G" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="variations[{{ $idx }}][price]" class="form-control form-control-sm font-bold text-slate-900 var-price" value="{{ $varPrice }}" placeholder="0.00" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="variations[{{ $idx }}][sale_price]" class="form-control form-control-sm text-emerald-600 var-sale-price" value="{{ $varSalePrice }}" placeholder="0.00">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="variations[{{ $idx }}][cost_price]" class="form-control form-control-sm var-cost-price" value="{{ $varCostPrice }}" placeholder="0.00">
                                    </td>
                                    <td>
                                        <input type="number" min="0" name="variations[{{ $idx }}][stock_qty]" class="form-control form-control-sm var-stock" value="{{ $varStock }}" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="variations[{{ $idx }}][weight]" class="form-control form-control-sm var-weight" value="{{ $varWeight }}" placeholder="kg">
                                    </td>
                                    <td>
                                        <select name="variations[{{ $idx }}][status]" class="form-select form-select-sm var-status">
                                            <option value="active" @selected($varStatus === 'active')>Active</option>
                                            <option value="inactive" @selected($varStatus === 'inactive')>Inactive</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row p-1 px-2" title="Remove Variation">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="noVariationsNotice" class="p-6 text-center text-slate-400 {{ (isset($product) && $product->variations->count()) || old('variations') ? 'd-none' : '' }}">
                    <i class="fas fa-boxes-stacked text-3xl text-slate-300 mb-2 block"></i>
                    <p class="font-medium text-slate-600 text-xs m-0">No product variations configured.</p>
                    <p class="text-[11px] text-slate-400 m-0 mt-1">If this is a simple single-price product, leave this empty. To add attribute-based pricing (e.g. 500g, 1kg), select an attribute and click <strong>"Auto-Generate"</strong> or <strong>"Add Row"</strong> above.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Media & Gallery Images -->
    <div class="col-12 mt-4 mb-2">
        <h5 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-2">
            <i class="fas fa-image text-blue-500"></i> Media & Gallery Assets
        </h5>
        <hr class="my-2 border-slate-100">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label font-semibold text-slate-700">Main Product Image</label>
        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp">
        @if(isset($product) && $product->image)
            <div class="mt-2.5 p-2 bg-slate-50 border rounded-lg inline-block">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded" style="max-height:110px; max-width:160px; object-fit:contain;">
            </div>
        @endif
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label font-semibold text-slate-700">Gallery Images</label>
        <input type="file" name="gallery[]" id="galleryImages" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp" multiple>
        <small class="text-muted d-block mt-1">Select up to 10 additional images.</small>
        <div id="galleryPreview" class="d-flex flex-wrap gap-2 mt-3"></div>
        @if(isset($product) && $product->images->count())
            <div class="d-flex flex-wrap gap-2 mt-2">
                @foreach($product->images as $img)
                    <div class="position-relative border rounded p-1 bg-white shadow-2xs">
                        <img src="{{ $img->image_url }}" alt="{{ $product->name }} gallery image" style="width:75px;height:75px;object-fit:cover;" class="rounded">
                        <label class="d-block text-[11px] text-danger mt-1 cursor-pointer"><input type="checkbox" name="remove_gallery[]" value="{{ $img->id }}"> Remove</label>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- 5. Descriptions & SEO -->
    <div class="col-12 mt-4 mb-2">
        <h5 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-2">
            <i class="fas fa-file-lines text-blue-500"></i> Descriptions & SEO
        </h5>
        <hr class="my-2 border-slate-100">
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">Short Description</label>
        <textarea name="short_desc" class="form-control" rows="2" placeholder="Brief summary of the product displayed on product cards and quick view">{{ old('short_desc', $product->short_desc ?? '') }}</textarea>
    </div>
    <div class="col-md-12 mb-3">
        <label class="form-label">Full Description</label>
        <textarea name="description" class="form-control" rows="4" placeholder="Detailed product specifications, nutritional facts, usage guidelines, and features">{{ old('description', $product->description ?? '') }}</textarea>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Meta Title</label>
        <input type="text" name="seo_title" class="form-control" value="{{ old('seo_title', $product->seo_title ?? '') }}" placeholder="SEO title for search engines">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Meta Keywords</label>
        <input type="text" name="seo_keywords" class="form-control" value="{{ old('seo_keywords', $product->seo_keywords ?? '') }}" placeholder="e.g. basmati rice, pure desi ghee, organic">
    </div>
    <div class="col-md-12 mb-3">
        <label class="form-label">Meta Description</label>
        <textarea name="seo_desc" class="form-control" rows="2" placeholder="Search engine snippet description">{{ old('seo_desc', $product->seo_desc ?? '') }}</textarea>
    </div>
</div>

<style>
/* Premium HSN Custom Dropdown Styles */
#premiumHsnDropdown {
    position: relative;
    user-select: none;
}
.hsn-dropdown-trigger {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 0.75rem;
    padding: 0.5rem 0.85rem;
    min-height: 44px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04);
}
.hsn-dropdown-trigger:hover {
    border-color: #fb923c;
    background-color: #fffaf5;
    box-shadow: 0 2px 6px -1px rgba(249, 115, 22, 0.12);
}
.hsn-dropdown-trigger.active-open {
    border-color: #ea580c;
    box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.18), 0 4px 12px -2px rgba(0, 0, 0, 0.08);
    background-color: #ffffff;
}
.hsn-dropdown-menu {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.15), 0 8px 10px -6px rgba(15, 23, 42, 0.08);
    z-index: 1060;
    overflow: hidden;
    animation: hsnDropdownPop 0.18s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes hsnDropdownPop {
    from { opacity: 0; transform: translateY(-6px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.hsn-item-row {
    padding: 0.55rem 0.75rem;
    border-radius: 0.625rem;
    cursor: pointer;
    transition: all 0.15s ease;
    border: 1px solid transparent;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2px;
}
.hsn-item-row:hover {
    background-color: #fff7ed;
    border-color: #fed7aa;
}
.hsn-item-row.is-selected {
    background-color: #ffedd5;
    border-color: #fdba74;
}
.hsn-rate-chip {
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    white-space: nowrap;
}
.hsn-rate-chip:hover {
    border-color: #fed7aa;
    background: #fff7ed;
    color: #ea580c;
}
.hsn-rate-chip.is-active {
    background: #ea580c;
    border-color: #ea580c;
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(234, 88, 12, 0.25);
}
</style>

<script>
// Configured Attributes Data for dynamic rows
const configuredAttributesList = @json($attributes ?? []);

document.getElementById('productName')?.addEventListener('input', function () {
    const slug = document.getElementById('productSlug');
    if (slug && !slug.dataset.edited) {
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
    const productBaseSkuInput = document.getElementById('productBaseSku');
    const productNameInput = document.getElementById('productName');

    const hsnHiddenInput = document.getElementById('productHsnCode');
    const hsnDropdownWrapper = document.getElementById('premiumHsnDropdown');
    const hsnTrigger = document.getElementById('hsnDropdownTrigger');
    const hsnTriggerDisplay = document.getElementById('hsnTriggerDisplay');
    const hsnChevron = document.getElementById('hsnChevron');
    const hsnPanel = document.getElementById('hsnDropdownPanel');
    const hsnSearchInput = document.getElementById('hsnSearchInput');
    const hsnSearchClear = document.getElementById('hsnSearchClear');
    const hsnOptionsList = document.getElementById('hsnOptionsList');
    const hsnNoResults = document.getElementById('hsnNoResults');
    const customHsnContainer = document.getElementById('customHsnContainer');
    const customHsnInput = document.getElementById('customHsnInput');
    const autoHsnBadge = document.getElementById('autoHsnBadge');
    const btnUseCustomFromSearch = document.getElementById('btnUseCustomFromSearch');
    const btnCustomHsnTrigger = document.getElementById('btnCustomHsnTrigger');
    const btnCancelCustomHsn = document.getElementById('btnCancelCustomHsn');
    const hsnItemCountBadge = document.getElementById('hsnItemCountBadge');

    let currentGstFilter = 'all';

    function openHsnDropdown() {
        if (!hsnPanel) return;
        hsnPanel.classList.remove('d-none');
        hsnTrigger?.classList.add('active-open');
        if (hsnChevron) hsnChevron.style.transform = 'rotate(180deg)';
        if (hsnSearchInput) {
            hsnSearchInput.value = '';
            currentGstFilter = 'all';
            document.querySelectorAll('.hsn-rate-chip').forEach(c => {
                if (c.dataset.gstFilter === 'all') c.classList.add('is-active');
                else c.classList.remove('is-active');
            });
            filterHsnList();
            setTimeout(() => hsnSearchInput.focus(), 50);
        }
    }

    function closeHsnDropdown() {
        if (!hsnPanel) return;
        hsnPanel.classList.add('d-none');
        hsnTrigger?.classList.remove('active-open');
        if (hsnChevron) hsnChevron.style.transform = 'rotate(0deg)';
    }

    hsnTrigger?.addEventListener('click', function (e) {
        e.stopPropagation();
        if (hsnPanel.classList.contains('d-none')) {
            openHsnDropdown();
        } else {
            closeHsnDropdown();
        }
    });

    document.addEventListener('click', function (e) {
        if (hsnDropdownWrapper && !hsnDropdownWrapper.contains(e.target)) {
            closeHsnDropdown();
        }
    });

    // Handle Escape key to close dropdown
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && hsnPanel && !hsnPanel.classList.contains('d-none')) {
            closeHsnDropdown();
        }
    });

    function selectHsnItem(code, name, gst, isUserAction = true) {
        if (hsnHiddenInput) hsnHiddenInput.value = code;

        if (code === 'custom') {
            if (customHsnContainer) {
                customHsnContainer.classList.remove('d-none');
                if (customHsnInput) customHsnInput.focus();
            }
            if (hsnTriggerDisplay) {
                hsnTriggerDisplay.innerHTML = `
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-700 text-white font-mono text-[11px] font-semibold flex-shrink-0">
                        HSN Custom
                    </span>
                    <span class="text-xs text-slate-800 font-semibold text-truncate">Custom / Manual HSN Code</span>
                    <span class="badge bg-slate-100 text-slate-600 border border-slate-200 text-[10px] ms-auto flex-shrink-0">Custom</span>
                `;
            }
        } else {
            if (customHsnContainer) customHsnContainer.classList.add('d-none');
            if (hsnTriggerDisplay) {
                hsnTriggerDisplay.innerHTML = `
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-900 text-white font-mono text-[11px] font-semibold tracking-wide flex-shrink-0 shadow-2xs">
                        HSN ${code}
                    </span>
                    <span class="text-xs text-slate-900 font-semibold text-truncate">
                        ${name}
                    </span>
                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2 py-0.5 ms-auto flex-shrink-0">
                        ${gst}% GST
                    </span>
                `;
            }
            if (gstPercentageInput && gst !== undefined && gst !== null) {
                gstPercentageInput.value = gst;
                updateGstCalculation();
            }
        }

        // Highlight selected item in list
        document.querySelectorAll('.hsn-item-row').forEach(row => {
            const check = row.querySelector('.check-icon');
            if (row.dataset.code === code) {
                row.classList.add('is-selected');
                if (!check) {
                    const iconWrap = row.querySelector('.flex-shrink-0');
                    if (iconWrap) iconWrap.insertAdjacentHTML('beforeend', '<i class="fas fa-check-circle text-emerald-600 text-xs check-icon ms-1"></i>');
                }
            } else {
                row.classList.remove('is-selected');
                check?.remove();
            }
        });

        if (isUserAction) {
            hsnHiddenInput.dataset.userChanged = '1';
            if (autoHsnBadge) autoHsnBadge.style.display = 'none';
        }

        closeHsnDropdown();
    }

    document.querySelectorAll('.hsn-item-row').forEach(row => {
        row.addEventListener('click', function () {
            selectHsnItem(this.dataset.code, this.dataset.name, this.dataset.gst, true);
        });
    });

    btnUseCustomFromSearch?.addEventListener('click', function () {
        selectHsnItem('custom', 'Custom / Other HSN Code', 5, true);
    });

    btnCustomHsnTrigger?.addEventListener('click', function () {
        selectHsnItem('custom', 'Custom / Other HSN Code', 5, true);
    });

    btnCancelCustomHsn?.addEventListener('click', function () {
        if (customHsnContainer) customHsnContainer.classList.add('d-none');
        if (customHsnInput) customHsnInput.value = '';
        if (hsnHiddenInput) hsnHiddenInput.value = '';
        if (hsnTriggerDisplay) {
            hsnTriggerDisplay.innerHTML = `
                <span class="text-xs text-slate-400 d-flex align-items-center gap-2 font-normal">
                    <i class="fas fa-barcode text-slate-400 text-sm"></i>
                    <span>Select Product HSN & Tax Rate...</span>
                </span>
            `;
        }
    });

    // GST Filter Chips Handler
    document.querySelectorAll('.hsn-rate-chip').forEach(chip => {
        chip.addEventListener('click', function () {
            document.querySelectorAll('.hsn-rate-chip').forEach(c => c.classList.remove('is-active'));
            this.classList.add('is-active');
            currentGstFilter = this.dataset.gstFilter || 'all';
            filterHsnList();
        });
    });

    function filterHsnList() {
        const q = (hsnSearchInput?.value || '').toLowerCase().trim();
        let totalVisible = 0;

        document.querySelectorAll('.hsn-category-group').forEach(group => {
            let groupVisible = 0;
            group.querySelectorAll('.hsn-item-row').forEach(row => {
                const code = (row.dataset.code || '').toLowerCase();
                const name = (row.dataset.name || '').toLowerCase();
                const category = (row.dataset.category || '').toLowerCase();
                const gst = (row.dataset.gst || '').toString();

                const matchesGst = currentGstFilter === 'all' || gst === currentGstFilter;
                const matchesQuery = !q || code.includes(q) || name.includes(q) || category.includes(q) || gst.includes(q);

                if (matchesGst && matchesQuery) {
                    row.classList.remove('d-none');
                    groupVisible++;
                    totalVisible++;
                } else {
                    row.classList.add('d-none');
                }
            });

            if (groupVisible > 0) {
                group.classList.remove('d-none');
            } else {
                group.classList.add('d-none');
            }
        });

        if (hsnSearchClear) {
            if (q) hsnSearchClear.classList.remove('d-none');
            else hsnSearchClear.classList.add('d-none');
        }

        if (hsnItemCountBadge) {
            hsnItemCountBadge.textContent = `${totalVisible} items`;
        }

        if (hsnNoResults && hsnOptionsList) {
            if (totalVisible === 0) {
                hsnNoResults.classList.remove('d-none');
                hsnOptionsList.classList.add('d-none');
            } else {
                hsnNoResults.classList.add('d-none');
                hsnOptionsList.classList.remove('d-none');
            }
        }
    }

    hsnSearchInput?.addEventListener('input', function () {
        filterHsnList();
    });

    hsnSearchClear?.addEventListener('click', function () {
        if (hsnSearchInput) {
            hsnSearchInput.value = '';
            filterHsnList();
            hsnSearchInput.focus();
        }
    });

    // Smart auto-match HSN Code and GST % from Product Name
    const hsnKeywordsMap = [
        { regex: /rice|chawal|basmati|paddy/i, code: '1006', gst: 5 },
        { regex: /atta|flour|maida|suji|sooji|wheat/i, code: '1101', gst: 5 },
        { regex: /dal|pulse|moong|chana|toor|urad|rajma|legume/i, code: '0713', gst: 5 },
        { regex: /ghee|butter|makhan/i, code: '0405', gst: 12 },
        { regex: /milk|paneer|dahi|curd|dairy/i, code: '0401', gst: 0 },
        { regex: /honey|madhu/i, code: '0409', gst: 5 },
        { regex: /tea|chai|green tea/i, code: '0902', gst: 5 },
        { regex: /coffee/i, code: '0901', gst: 5 },
        { regex: /turmeric|haldi|ginger|adrak|masala|garam masala|saffron|kesar/i, code: '0910', gst: 5 },
        { regex: /pepper|mirch|chilli|cumin|jeera|coriander|dhaniya|spice/i, code: '0904', gst: 5 },
        { regex: /oil|tel|mustard|sarson|groundnut|coconut oil/i, code: '1515', gst: 5 },
        { regex: /sugar|cheeni|jaggery|gur|shakkar|sweetener/i, code: '1701', gst: 5 },
        { regex: /khakhra|farsan|namkeen|snack|bhujia/i, code: '1905', gst: 5 },
        { regex: /biscuit|cookie|confectionery|chocolate|wafer/i, code: '1905B', gst: 18 },
        { regex: /almond|badam|cashew|kaju|walnut|akhrot|pista|raisin|kismis|dry fruit/i, code: '0801', gst: 12 },
        { regex: /agarbatti|dhoop|hawan|samagri|camphor|kapoor|puja|spiritual/i, code: '3307', gst: 5 },
        { regex: /ayurved|chyawanprash|kadha|herbal|vati|churna/i, code: '3004', gst: 12 },
        { regex: /soap|sabun|shampoo|face wash|cleanser/i, code: '3401', gst: 18 },
        { regex: /cream|lotion|moisturizer|serum|skincare|beauty/i, code: '3304', gst: 18 },
        { regex: /khadi|kurta|cotton|fabric|saree|dupatta|textile/i, code: '5208', gst: 5 },
        { regex: /detergent|dishwash|cleaner|floor cleaner/i, code: '3402', gst: 18 },
        { regex: /protein|supplement|health drink|food prep/i, code: '2106', gst: 18 },
    ];

    productNameInput?.addEventListener('input', function () {
        if (hsnHiddenInput && !hsnHiddenInput.dataset.userChanged && (!hsnHiddenInput.value || hsnHiddenInput.dataset.autoMatched === '1')) {
            const val = this.value;
            let matched = false;
            for (const item of hsnKeywordsMap) {
                if (item.regex.test(val)) {
                    const rowMatch = document.querySelector(`.hsn-item-row[data-code="${item.code}"]`);
                    const itemName = rowMatch ? rowMatch.dataset.name : item.code;
                    selectHsnItem(item.code, itemName, item.gst, false);
                    hsnHiddenInput.dataset.autoMatched = '1';
                    if (autoHsnBadge) autoHsnBadge.style.display = 'inline-block';
                    matched = true;
                    break;
                }
            }
            if (!matched && hsnHiddenInput.dataset.autoMatched === '1') {
                hsnHiddenInput.value = '';
                delete hsnHiddenInput.dataset.autoMatched;
                if (hsnTriggerDisplay) {
                    hsnTriggerDisplay.innerHTML = `
                        <span class="text-xs text-slate-400 d-flex align-items-center gap-2 font-normal">
                            <i class="fas fa-barcode text-slate-400 text-sm"></i>
                            <span>Select Product HSN & Tax Rate...</span>
                        </span>
                    `;
                }
                if (autoHsnBadge) autoHsnBadge.style.display = 'none';
            }
        }
    });

    [priceInput, salePriceInput, gstPercentageInput].forEach((input) => {
        input?.addEventListener('input', updateGstCalculation);
        input?.addEventListener('change', updateGstCalculation);
    });

    function updateVariationsUI() {
        const rows = variationsTableBody.querySelectorAll('.variation-row');
        const count = rows.length;
        if (variationCountBadge) {
            variationCountBadge.textContent = `${count} configured`;
        }
        if (noVariationsNotice) {
            if (count > 0) {
                noVariationsNotice.classList.add('d-none');
            } else {
                noVariationsNotice.classList.remove('d-none');
            }
        }
    }

    function buildAttributeSelectOptions(selectedAttrId) {
        let options = '<option value="">Custom / Direct</option>';
        configuredAttributesList.forEach(attr => {
            const isSel = selectedAttrId && parseInt(selectedAttrId) === parseInt(attr.id) ? 'selected' : '';
            options += `<option value="${attr.id}" ${isSel}>${attr.name}</option>`;
        });
        return options;
    }

    function createVariationRow(data = {}) {
        const idx = currentVarIndex++;
        const baseSku = (productBaseSkuInput?.value || 'SKU').trim().toUpperCase();
        const attrVal = data.attr_val || '';
        const slugVal = attrVal.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '').toUpperCase();
        const genSku = data.sku || (baseSku && slugVal ? `${baseSku}-${slugVal}` : (baseSku ? `${baseSku}-VAR${idx + 1}` : ''));
        const regPrice = data.price !== undefined ? data.price : (priceInput?.value || '');
        const salePrice = data.sale_price !== undefined ? data.sale_price : (salePriceInput?.value || '');
        const costPrice = data.cost_price !== undefined ? data.cost_price : '';
        const stockQty = data.stock_qty !== undefined ? data.stock_qty : 10;
        const weight = data.weight !== undefined ? data.weight : '';
        const status = data.status || 'active';

        const tr = document.createElement('tr');
        tr.className = 'variation-row bg-white';
        tr.dataset.index = idx;
        tr.innerHTML = `
            <td>
                ${data.id ? `<input type="hidden" name="variations[${idx}][id]" value="${data.id}">` : ''}
                <select name="variations[${idx}][attr_id]" class="form-select form-select-sm var-attr-id">
                    ${buildAttributeSelectOptions(data.attr_id)}
                </select>
            </td>
            <td>
                <input type="text" name="variations[${idx}][attr_val]" class="form-control form-control-sm var-attr-val" value="${attrVal}" placeholder="e.g. 500g, 1kg, Large" required>
            </td>
            <td>
                <input type="text" name="variations[${idx}][sku]" class="form-control form-control-sm font-mono var-sku" value="${genSku}" placeholder="e.g. SKU-500G" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="variations[${idx}][price]" class="form-control form-control-sm font-bold text-slate-900 var-price" value="${regPrice}" placeholder="0.00" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="variations[${idx}][sale_price]" class="form-control form-control-sm text-emerald-600 var-sale-price" value="${salePrice}" placeholder="0.00">
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="variations[${idx}][cost_price]" class="form-control form-control-sm var-cost-price" value="${costPrice}" placeholder="0.00">
            </td>
            <td>
                <input type="number" min="0" name="variations[${idx}][stock_qty]" class="form-control form-control-sm var-stock" value="${stockQty}" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="variations[${idx}][weight]" class="form-control form-control-sm var-weight" value="${weight}" placeholder="kg">
            </td>
            <td>
                <select name="variations[${idx}][status]" class="form-select form-select-sm var-status">
                    <option value="active" ${status === 'active' ? 'selected' : ''}>Active</option>
                    <option value="inactive" ${status === 'inactive' ? 'selected' : ''}>Inactive</option>
                </select>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row p-1 px-2" title="Remove Variation">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;

        // Row events
        const valInput = tr.querySelector('.var-attr-val');
        const skuInput = tr.querySelector('.var-sku');
        valInput?.addEventListener('input', function() {
            if (!skuInput.dataset.edited) {
                const bSku = (productBaseSkuInput?.value || 'SKU').trim().toUpperCase();
                const sVal = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '').toUpperCase();
                if (sVal) {
                    skuInput.value = `${bSku}-${sVal}`;
                }
            }
        });

        skuInput?.addEventListener('input', function() {
            this.dataset.edited = '1';
        });

        tr.querySelector('.btn-remove-row')?.addEventListener('click', function () {
            tr.remove();
            updateVariationsUI();
        });

        variationsTableBody.appendChild(tr);
        updateVariationsUI();
    }

    // Attach listener to existing rows remove button
    document.querySelectorAll('.btn-remove-row').forEach(btn => {
        btn.addEventListener('click', function () {
            this.closest('.variation-row')?.remove();
            updateVariationsUI();
        });
    });

    btnAddVariationRow?.addEventListener('click', function () {
        const selectedAttrOption = attrQuickSelect?.selectedOptions[0];
        const attrId = selectedAttrOption && selectedAttrOption.value ? selectedAttrOption.value : '';
        createVariationRow({ attr_id: attrId });
    });

    btnAutoGenerate?.addEventListener('click', function () {
        const selectedAttrOption = attrQuickSelect?.selectedOptions[0];
        if (!selectedAttrOption || !selectedAttrOption.value) {
            alert('Please select an Attribute from the dropdown first (e.g. Weight or Pack Size).');
            return;
        }

        const attrId = selectedAttrOption.value;
        const attrValuesJson = selectedAttrOption.dataset.values;
        let values = [];
        try {
            values = JSON.parse(attrValuesJson || '[]');
        } catch (e) {
            values = [];
        }

        if (!values.length) {
            alert('No option values found for this attribute. You can add a manual row or add values at Attributes & Variations menu.');
            createVariationRow({ attr_id: attrId });
            return;
        }

        values.forEach(val => {
            createVariationRow({
                attr_id: attrId,
                attr_val: val
            });
        });
    });

    btnClearAllVariations?.addEventListener('click', function () {
        if (confirm('Are you sure you want to remove all variation rows?')) {
            variationsTableBody.innerHTML = '';
            updateVariationsUI();
        }
    });

    function renderGalleryPreview(files) {
        if (!galleryPreview) return;
        galleryPreview.innerHTML = '';

        Array.from(files).slice(0, 10).forEach((file) => {
            const card = document.createElement('div');
            card.className = 'border rounded p-2 bg-white';
            card.style.width = '110px';

            const img = document.createElement('img');
            img.alt = file.name;
            img.className = 'rounded mb-2';
            img.style.width = '100%';
            img.style.height = '90px';
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

    syncHsnGstRate();
    updateGstCalculation();
    updateVariationsUI();
});
</script>
