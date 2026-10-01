<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
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
        $query = Product::with(['category', 'brand'])->latest();

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
        $data['stock_qty'] = 0;

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploader->upload($request->file('image'), 'products');
        }

        $product = Product::create($data);
        $this->saveGallery($product, $request);

        if ($initialStock > 0) {
            $this->inventoryService->adjustStock($product, $initialStock, 'opening_stock');
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'subCategory', 'subSubCategory', 'brand', 'images', 'variations', 'inventoryLogs.user']);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load('images');

        return view('admin.products.edit', array_merge(['product' => $product], $this->formData()));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request, $product->id);
        $data['featured'] = $request->boolean('featured');
        $targetStock = (int) ($data['stock_qty'] ?? 0);
        unset($data['stock_qty']);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($request->hasFile('image')) {
            $this->uploader->delete($product->image);
            $data['image'] = $this->uploader->upload($request->file('image'), 'products');
        }

        $product->update($data);
        $this->removeGalleryImages($request->input('remove_gallery', []));
        $this->saveGallery($product, $request);

        if ($targetStock !== (int) $product->stock_qty) {
            $this->inventoryService->adjustStock($product, $targetStock, 'admin_product_update');
        }

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $this->uploader->delete($product->image);
        foreach ($product->images as $img) {
            $this->uploader->delete($img->image);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
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
        ];
    }
}
