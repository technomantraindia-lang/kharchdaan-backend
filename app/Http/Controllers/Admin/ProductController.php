<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\AttributeValue;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\VariationAttributeValue;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Services\InventoryService;

class ProductController extends Controller
{
    public function __construct(
        private ImageUploadService $uploader,
        private InventoryService $inventoryService
    ) {}

    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'variations.attribute'])->withCount('variations')->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::where('status', 'active')->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return view('admin.products.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);
        $data['featured'] = $request->boolean('featured');
        $initialStock = (int) ($data['stock_qty'] ?? 0);
        $data['stock_qty'] = $initialStock;

        if (empty($data['slug'])) {
            $baseSlug = Str::slug($data['name']) ?: 'product-' . time();
            $slug = $baseSlug;
            $i = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$i}";
                $i++;
            }
            $data['slug'] = $slug;
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploader->upload($request->file('image'), 'products');
        }

        if ($request->input('hsn_code') === 'custom' && $request->filled('custom_hsn_code')) {
            $data['hsn_code'] = trim((string)$request->input('custom_hsn_code'));
        }

        $variationsData = $this->extractVariations($request);

        // If variations are provided, align base price and base stock to variations if not explicitly given
        if (!empty($variationsData)) {
            $validPrices = collect($variationsData)->pluck('price')->filter(fn($p) => $p > 0);
            if ($validPrices->isNotEmpty() && (empty($data['price']) || $data['price'] == 0)) {
                $data['price'] = $validPrices->min();
            }
            $validSalePrices = collect($variationsData)->pluck('sale_price')->filter(fn($p) => !is_null($p) && $p > 0);
            if ($validSalePrices->isNotEmpty() && empty($data['sale_price'])) {
                $data['sale_price'] = $validSalePrices->min();
            }
            $totalVarStock = collect($variationsData)->sum('stock_qty');
            if ($totalVarStock > 0 && ($data['stock_qty'] ?? 0) == 0) {
                $data['stock_qty'] = $totalVarStock;
            }
        }

        $product = DB::transaction(function () use ($data, $request, $variationsData) {
            $product = Product::create($data);
            $this->saveGallery($product, $request);
            $this->saveVariations($product, $variationsData);
            return $product;
        });

        return redirect(admin_route('products.index'))->with('success', "Product '{$product->name}' was added successfully to the catalog!");
    }

    public function show(Product $product)
    {
        $product->load(['category', 'subCategory', 'subSubCategory', 'brand', 'images', 'variations.attribute', 'inventoryLogs.user']);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load(['images', 'variations.attribute']);

        return view('admin.products.edit', array_merge(['product' => $product], $this->formData()));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request, $product->id);
        $data['featured'] = $request->boolean('featured');

        if (empty($data['slug'])) {
            $baseSlug = Str::slug($data['name']) ?: 'product-' . time();
            $slug = $baseSlug;
            $i = 1;
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = "{$baseSlug}-{$i}";
                $i++;
            }
            $data['slug'] = $slug;
        }

        if ($request->hasFile('image')) {
            if ($product->image) {
                $this->uploader->delete($product->image);
            }
            $data['image'] = $this->uploader->upload($request->file('image'), 'products');
        }

        if ($request->input('hsn_code') === 'custom' && $request->filled('custom_hsn_code')) {
            $data['hsn_code'] = trim((string)$request->input('custom_hsn_code'));
        }

        $variationsData = $this->extractVariations($request);

        if (!empty($variationsData)) {
            $validPrices = collect($variationsData)->pluck('price')->filter(fn($p) => $p > 0);
            if ($validPrices->isNotEmpty() && (empty($data['price']) || $data['price'] == 0)) {
                $data['price'] = $validPrices->min();
            }
            $validSalePrices = collect($variationsData)->pluck('sale_price')->filter(fn($p) => !is_null($p) && $p > 0);
            if ($validSalePrices->isNotEmpty() && empty($data['sale_price'])) {
                $data['sale_price'] = $validSalePrices->min();
            }
        }

        DB::transaction(function () use ($product, $data, $request, $variationsData) {
            $product->update($data);
            $this->removeGalleryImages($request->input('remove_gallery', []));
            $this->saveGallery($product, $request);
            $this->syncVariations($product, $variationsData);
        });

        return redirect(admin_route('products.index'))->with('success', "Product '{$product->name}' was updated successfully!");
    }

    public function destroy(Product $product)
    {
        $name = $product->name;
        if ($product->image) {
            $this->uploader->delete($product->image);
        }
        foreach ($product->images as $img) {
            $this->uploader->delete($img->image);
        }
        $product->delete();

        return redirect(admin_route('products.index'))->with('success', "Product '{$name}' deleted successfully.");
    }

    public function bulkCreate()
    {
        return view('admin.products.bulk-create', $this->formData());
    }

    public function bulkStore(Request $request)
    {
        $request->validate(['products' => 'required|array|min:1']);

        $count = 0;
        DB::transaction(function () use ($request, &$count) {
            foreach ($request->products as $i => $row) {
                if (empty($row['name']) || empty($row['sku'])) {
                    continue;
                }

                $initialStock = max((int) ($row['stock_qty'] ?? 0), 0);
                $slug = $row['slug'] ?? Str::slug($row['name']);
                $data = [
                    'name' => $row['name'],
                    'slug' => $slug,
                    'sku' => $row['sku'],
                    'category_id' => $row['category_id'],
                    'price' => $row['price'] ?? 0,
                    'sale_price' => $row['sale_price'] ?? null,
                    'stock_qty' => 0,
                    'unit' => $row['unit'] ?? 'pcs',
                    'status' => $row['status'] ?? 'active',
                    'featured' => ! empty($row['featured']),
                    'gst_percentage' => $row['gst_percentage'] ?? 5,
                ];

                $product = Product::create($data);
                if ($initialStock > 0) {
                    $this->inventoryService->adjustStock($product, $initialStock, 'opening_stock');
                }
                $count++;
            }
        });

        return redirect()->route('admin.products.index')->with('success', "{$count} products added successfully.");
    }

    public function importForm()
    {
        return view('admin.products.import');
    }

    public function importStore(Request $request)
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt|max:2048']);

        $file = fopen($request->file('csv_file')->getRealPath(), 'r');
        $header = fgetcsv($file);
        $count = 0;

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($header, $row);
            if (empty($data['name']) || empty($data['sku'])) {
                continue;
            }

            $category = Category::where('name', $data['category'] ?? '')->first();
            if (! $category) {
                continue;
            }

            $initialStock = max((int) ($data['stock_qty'] ?? 0), 0);

            $existing = Product::where('sku', $data['sku'])->first();
            if ($existing) {
                $existing->update([
                    'name' => $data['name'],
                    'slug' => $data['slug'] ?? Str::slug($data['name']),
                    'category_id' => $category->id,
                    'price' => $data['price'] ?? 0,
                    'sale_price' => $data['sale_price'] ?? null,
                    'unit' => $data['unit'] ?? 'pcs',
                    'status' => $data['status'] ?? 'active',
                    'gst_percentage' => $data['gst_percentage'] ?? 5,
                ]);
                $this->inventoryService->adjustStock($existing, $initialStock, 'csv_import_stock');
            } else {
                $product = Product::create([
                    'sku' => $data['sku'],
                    'name' => $data['name'],
                    'slug' => $data['slug'] ?? Str::slug($data['name']),
                    'category_id' => $category->id,
                    'price' => $data['price'] ?? 0,
                    'sale_price' => $data['sale_price'] ?? null,
                    'stock_qty' => 0,
                    'unit' => $data['unit'] ?? 'pcs',
                    'status' => $data['status'] ?? 'active',
                    'gst_percentage' => $data['gst_percentage'] ?? 5,
                ]);
                if ($initialStock > 0) {
                    $this->inventoryService->adjustStock($product, $initialStock, 'opening_stock');
                }
            }
            $count++;
        }
        fclose($file);

        return redirect()->route('admin.products.index')->with('success', "{$count} products imported successfully.");
    }

    public function toggleStatus(Product $product)
    {
        $product->update(['status' => $product->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', 'Product status updated.');
    }

    public function toggleFeatured(Product $product)
    {
        $product->update(['featured' => ! $product->featured]);

        return back()->with('success', 'Featured status updated.');
    }

    private function validateProduct(Request $request, ?int $id = null): array
    {
        $skuRule = 'required|string|max:100|unique:products,sku' . ($id ? ",{$id}" : '');
        $slugRule = 'nullable|string|max:255|unique:products,slug' . ($id ? ",{$id}" : '');

        return $request->validate([
            'name' => 'required|string|max:255',
            'slug' => $slugRule,
            'sku' => $skuRule,
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'hsn_code' => 'nullable|string|max:50',
            'custom_hsn_code' => 'nullable|string|max:50',
            'gst_percentage' => 'nullable|numeric|min:0|max:100',
            'stock_qty' => 'required|integer|min:0',
            'low_stock_qty' => 'nullable|integer|min:0',
            'unit' => 'nullable|string|max:20',
            'min_order_qty' => 'nullable|integer|min:1',
            'weight' => 'nullable|numeric|min:0',
            'short_desc' => 'nullable|string',
            'description' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_desc' => 'nullable|string',
            'seo_keywords' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'featured' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'gallery' => 'nullable|array|max:10',
            'gallery.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);
    }

    private function saveGallery(Product $product, Request $request): void
    {
        if (! $request->hasFile('gallery')) {
            return;
        }

        $sort = $product->images()->max('sort_order') ?? 0;
        foreach ($this->uploader->uploadMany($request->file('gallery'), 'products/gallery') as $path) {
            $product->images()->create(['image' => $path, 'sort_order' => ++$sort]);
        }
    }

    private function removeGalleryImages(array $ids): void
    {
        foreach (ProductImage::whereIn('id', $ids)->get() as $img) {
            $this->uploader->delete($img->image);
            $img->delete();
        }
    }

    private function formData(): array
    {
        return [
            'categories' => Category::where('status', 'active')->whereNull('parent_id')->orderBy('name')->get(),
            'subCategories' => Category::where('status', 'active')->whereNotNull('parent_id')->whereHas('parent', fn($q) => $q->whereNull('parent_id'))->orderBy('name')->get(),
            'brands' => Brand::where('status', 'active')->orderBy('name')->get(),
            'units' => ['pcs', 'kg', 'g', 'box', 'packet', 'litre', 'ml'],
            'attributes' => ProductAttribute::with(['values' => fn($q) => $q->where('status', 'active')])->where('status', 'active')->orderBy('name')->get(),
            'hsnCodes' => self::getHsnCodesList(),
        ];
    }

    public static function getHsnCodesList(): array
    {
        return [
            ['code' => '1006', 'name' => 'Basmati & Regular Rice, Grains & Cereals', 'gst' => 5, 'category' => 'Grains, Rice & Cereals'],
            ['code' => '1101', 'name' => 'Wheat Flour / Atta / Maida / Suji', 'gst' => 5, 'category' => 'Grains, Rice & Cereals'],
            ['code' => '0713', 'name' => 'Dried Legumes / Pulses & Dals (Moong, Toor, Chana)', 'gst' => 5, 'category' => 'Pulses & Dals'],
            ['code' => '0405', 'name' => 'Pure Desi Ghee / Butter / Dairy Fats', 'gst' => 12, 'category' => 'Dairy Products & Ghee'],
            ['code' => '0401', 'name' => 'Fresh Milk / Pasteurized Milk / Paneer / Dahi', 'gst' => 0, 'category' => 'Dairy Products & Ghee'],
            ['code' => '0409', 'name' => 'Natural Forest Honey', 'gst' => 5, 'category' => 'Honey & Natural Sweeteners'],
            ['code' => '0902', 'name' => 'Tea Leaves / CTC & Green Tea / Chai', 'gst' => 5, 'category' => 'Beverages, Tea & Coffee'],
            ['code' => '0901', 'name' => 'Coffee Beans / Ground Filter Coffee', 'gst' => 5, 'category' => 'Beverages, Tea & Coffee'],
            ['code' => '0910', 'name' => 'Ginger, Turmeric / Haldi, Saffron, Garam Masala', 'gst' => 5, 'category' => 'Spices & Seasonings'],
            ['code' => '0904', 'name' => 'Black Pepper, Red Chilli, Cumin / Jeera, Coriander', 'gst' => 5, 'category' => 'Spices & Seasonings'],
            ['code' => '1515', 'name' => 'Mustard, Groundnut, Coconut & Cooking Oils', 'gst' => 5, 'category' => 'Cooking & Edible Oils'],
            ['code' => '1701', 'name' => 'Cane Sugar, Organic Jaggery / Gur / Shakkar', 'gst' => 5, 'category' => 'Sugar, Jaggery & Sweeteners'],
            ['code' => '1905', 'name' => 'Khakhra, Farsan, Namkeen & Traditional Snacks', 'gst' => 5, 'category' => 'Snacks & Bakery'],
            ['code' => '1905B', 'name' => 'Biscuits, Cookies, Wafers & Confectionery', 'gst' => 18, 'category' => 'Snacks & Bakery'],
            ['code' => '0801', 'name' => 'Almonds, Cashews, Walnuts, Pistachios & Raisins', 'gst' => 12, 'category' => 'Dry Fruits & Nuts'],
            ['code' => '3004', 'name' => 'Ayurvedic Formulations, Chyawanprash & Herbal Meds', 'gst' => 12, 'category' => 'Ayurveda & Health Wellness'],
            ['code' => '3307', 'name' => 'Agarbatti, Dhoop Sticks, Hawan Samagri & Puja Items', 'gst' => 5, 'category' => 'Puja, Spiritual & Incense'],
            ['code' => '3401', 'name' => 'Herbal Bath Soaps, Shampoos & Cleansing Bars', 'gst' => 18, 'category' => 'Personal Care & Hygiene'],
            ['code' => '3304', 'name' => 'Face Creams, Body Lotions & Skincare Beauty', 'gst' => 18, 'category' => 'Cosmetics & Skincare'],
            ['code' => '5208', 'name' => 'Khadi, Cotton Apparel, Kurtas & Handloom', 'gst' => 5, 'category' => 'Apparel, Khadi & Textiles'],
            ['code' => '3402', 'name' => 'Detergents, Dishwash & Home Cleaners', 'gst' => 18, 'category' => 'Home Care & Cleaning'],
            ['code' => '2106', 'name' => 'Food Preparations, Health Mixes & Supplements', 'gst' => 18, 'category' => 'Packaged & Health Foods'],
            ['code' => '9999', 'name' => 'General FMCG & Standard Consumer Goods (18% GST)', 'gst' => 18, 'category' => 'General Goods'],
            ['code' => '0000', 'name' => 'Exempted Fresh Produce & Agricultural Staples (0% GST)', 'gst' => 0, 'category' => 'Tax-Exempt Produce'],
        ];
    }

    private function extractVariations(Request $request): array
    {
        $rawVariations = $request->input('variations', []);
        if (!is_array($rawVariations)) {
            return [];
        }

        $cleaned = [];
        $seenSkus = [];
        $baseSku = trim((string)($request->input('sku') ?: 'PROD-' . time()));

        foreach ($rawVariations as $index => $var) {
            if (!is_array($var)) {
                continue;
            }

            $attrVal = trim((string)($var['attr_val'] ?? ''));
            $price = isset($var['price']) && $var['price'] !== '' ? (float)$var['price'] : null;
            $stockQty = isset($var['stock_qty']) && $var['stock_qty'] !== '' ? (int)$var['stock_qty'] : null;

            // Skip empty placeholder row if no attribute value, price, or custom SKU was given
            if (empty($attrVal) && $price === null && empty($var['sku'])) {
                continue;
            }

            // Determine or generate SKU
            $sku = trim((string)($var['sku'] ?? ''));
            if (empty($sku)) {
                $suffix = Str::slug($attrVal) ?: ($index + 1);
                $sku = strtoupper("{$baseSku}-{$suffix}");
            }

            // Ensure unique SKU in this set
            $originalSku = $sku;
            $c = 1;
            while (in_array($sku, $seenSkus)) {
                $sku = "{$originalSku}-{$c}";
                $c++;
            }
            $seenSkus[] = $sku;

            $cleaned[] = [
                'id' => !empty($var['id']) ? (int)$var['id'] : null,
                'attr_id' => !empty($var['attr_id']) ? (int)$var['attr_id'] : null,
                'attr_val' => $attrVal ?: null,
                'sku' => $sku,
                'price' => $price ?? (float)($request->input('price') ?? 0),
                'sale_price' => isset($var['sale_price']) && $var['sale_price'] !== '' ? (float)$var['sale_price'] : null,
                'cost_price' => isset($var['cost_price']) && $var['cost_price'] !== '' ? (float)$var['cost_price'] : null,
                'stock_qty' => max($stockQty ?? 0, 0),
                'weight' => isset($var['weight']) && $var['weight'] !== '' ? (float)$var['weight'] : null,
                'status' => in_array($var['status'] ?? '', ['active', 'inactive']) ? $var['status'] : 'active',
            ];
        }

        return $cleaned;
    }

    private function saveVariations(Product $product, array $variations): void
    {
        foreach ($variations as $varData) {
            unset($varData['id']);
            $varData['product_id'] = $product->id;
            $variation = ProductVariation::create($varData);
            $this->linkVariationAttributeValue($variation);
        }
    }

    private function syncVariations(Product $product, array $variations): void
    {
        $keepIds = [];
        foreach ($variations as $varData) {
            $id = $varData['id'] ?? null;
            unset($varData['id']);
            $varData['product_id'] = $product->id;

            if ($id) {
                $existing = ProductVariation::where('id', $id)->where('product_id', $product->id)->first();
                if ($existing) {
                    $existing->update($varData);
                    $this->linkVariationAttributeValue($existing);
                    $keepIds[] = $existing->id;
                    continue;
                }
            }

            $variation = ProductVariation::create($varData);
            $this->linkVariationAttributeValue($variation);
            $keepIds[] = $variation->id;
        }

        if (!empty($keepIds)) {
            $product->variations()->whereNotIn('id', $keepIds)->delete();
        } elseif (empty($variations) && $product->variations()->exists()) {
            $product->variations()->delete();
        }
    }

    private function linkVariationAttributeValue(ProductVariation $variation): void
    {
        if ($variation->attr_id && $variation->attr_val) {
            $attrValModel = AttributeValue::where('attribute_id', $variation->attr_id)
                ->where(function ($q) use ($variation) {
                    $q->where('value', $variation->attr_val)
                      ->orWhere('slug', Str::slug($variation->attr_val));
                })->first();

            if ($attrValModel) {
                VariationAttributeValue::updateOrCreate(
                    [
                        'product_variation_id' => $variation->id,
                        'product_attribute_id' => $variation->attr_id,
                    ],
                    [
                        'attribute_value_id' => $attrValModel->id,
                    ]
                );
            }
        }
    }
}
