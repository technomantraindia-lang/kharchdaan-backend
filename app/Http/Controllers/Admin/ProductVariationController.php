<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariation;
use App\Services\ActivityLogService;
use App\Services\ImageUploadService;
use App\Services\InventoryService;
use App\Services\WooCommerce\ProductSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductVariationController extends Controller
{
    public function __construct(
        private ImageUploadService $uploader,
        private InventoryService $inventoryService
    ) {}

    public function index(Product $product)
    {
        $product->load(['variations.attribute', 'variations.multiAttributeValues.attribute', 'variations.multiAttributeValues.attributeValue']);
        $attributes = ProductAttribute::with('values')->where('status', 'active')->get();

        return view('admin.products.variations.index', compact('product', 'attributes'));
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100', 'unique:product_variations,sku'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_qty' => ['required', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'attr_id' => ['nullable', 'exists:product_attributes,id'],
            'attr_val' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $initialStock = (int) $validated['stock_qty'];
        $validated['stock_qty'] = 0;
        $validated['product_id'] = $product->id;

        if ($request->hasFile('image')) {
            $validated['image'] = $this->uploader->upload($request->file('image'), 'products/variations');
        }

        $variation = ProductVariation::create($validated);

        if ($initialStock > 0) {
            $this->inventoryService->adjustStock($variation, $initialStock, 'opening_variation_stock');
        }

        ActivityLogService::log(
            'create_variation',
            'products',
            "Created variation {$variation->sku} for product #{$product->id} ({$product->name})",
            $variation
        );

        if ($product->woocommerce_id) {
            app(ProductSyncService::class)->syncVariation($variation);
        }

        return redirect()->route('admin.products.variations.index', $product)
            ->with('success', 'Product variation created successfully.');
    }

    public function update(Request $request, Product $product, ProductVariation $variation)
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100', Rule::unique('product_variations', 'sku')->ignore($variation->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_qty' => ['required', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'attr_id' => ['nullable', 'exists:product_attributes,id'],
            'attr_val' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $targetStock = (int) $validated['stock_qty'];
        unset($validated['stock_qty']);

        if ($request->hasFile('image')) {
            if ($variation->image) {
                $this->uploader->delete($variation->image);
            }
            $validated['image'] = $this->uploader->upload($request->file('image'), 'products/variations');
        }

        $variation->update($validated);

        if ($targetStock !== (int) $variation->stock_qty) {
            $this->inventoryService->adjustStock($variation, $targetStock, 'admin_variation_update');
        }

        ActivityLogService::log(
            'update_variation',
            'products',
            "Updated variation {$variation->sku} for product #{$product->id}",
            $variation
        );

        if ($product->woocommerce_id) {
            app(ProductSyncService::class)->syncVariation($variation);
        }

        return redirect()->route('admin.products.variations.index', $product)
            ->with('success', 'Product variation updated successfully.');
    }

    public function destroy(Product $product, ProductVariation $variation)
    {
        if ($variation->image) {
            $this->uploader->delete($variation->image);
        }

        $sku = $variation->sku;
        $variation->delete();

        ActivityLogService::log(
            'delete_variation',
            'products',
            "Deleted variation {$sku} for product #{$product->id}"
        );

        return redirect()->route('admin.products.variations.index', $product)
            ->with('success', 'Product variation deleted successfully.');
    }
}
