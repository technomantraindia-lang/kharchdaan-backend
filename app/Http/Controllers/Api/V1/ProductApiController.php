<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\VariationResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'variations.multiAttributeValues.attribute', 'variations.multiAttributeValues.attributeValue'])
            ->where('status', 'active');

        // 1. Search Query
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'like', "%{$search}%"));
            });
        }

        // 2. Category Filter (slug or ID)
        if ($request->filled('category')) {
            $cat = $request->category;
            $query->whereHas('category', function ($cq) use ($cat) {
                if (is_numeric($cat)) {
                    $cq->where('id', $cat);
                } else {
                    $cq->where('slug', $cat);
                }
            });
        }

        // Subcategory Filter (slug or ID)
        if ($request->filled('subcategory')) {
            $subcat = $request->subcategory;
            $query->whereHas('subCategory', function ($scq) use ($subcat) {
                if (is_numeric($subcat)) {
                    $scq->where('id', $subcat);
                } else {
                    $scq->where('slug', $subcat);
                }
            });
        }

        // 3. Brand Filter (slug or ID)
        if ($request->filled('brand')) {
            $brand = $request->brand;
            $query->whereHas('brand', function ($bq) use ($brand) {
                if (is_numeric($brand)) {
                    $bq->where('id', $brand);
                } else {
                    $bq->where('slug', $brand);
                }
            });
        }

        // Attribute & Attribute Value Filters
        if ($request->filled('attribute') || $request->filled('attribute_value')) {
            $attr = $request->attribute;
            $attrVal = $request->attribute_value;
            $query->whereHas('variations.multiAttributeValues', function ($mavQ) use ($attr, $attrVal) {
                if ($attr) {
                    $mavQ->whereHas('attribute', function ($aq) use ($attr) {
                        if (is_numeric($attr)) {
                            $aq->where('id', $attr);
                        } else {
                            $aq->where('name', 'like', "%{$attr}%")->orWhere('slug', $attr);
                        }
                    });
                }
                if ($attrVal) {
                    $mavQ->whereHas('attributeValue', function ($avq) use ($attrVal) {
                        if (is_numeric($attrVal)) {
                            $avq->where('id', $attrVal);
                        } else {
                            $avq->where('value', 'like', "%{$attrVal}%");
                        }
                    });
                }
            });
        }

        // 4. Price Filters
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        // 5. Featured Filter
        if ($request->filled('featured')) {
            $isFeatured = filter_var($request->featured, FILTER_VALIDATE_BOOLEAN);
            $query->where('featured', $isFeatured);
        }

        // 6. In Stock Filter
        if ($request->filled('in_stock')) {
            $inStock = filter_var($request->in_stock, FILTER_VALIDATE_BOOLEAN);
            if ($inStock) {
                $query->where('stock_qty', '>', 0);
            }
        }

        // 7. Sorting
        switch ($request->get('sort')) {
            case 'oldest':
                $query->oldest();
                break;
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            default:
                $query->latest();
                break;
        }

        // 8. Pagination with cap
        $perPage = (int) $request->get('per_page', 20);
        if ($perPage <= 0) $perPage = 20;
        if ($perPage > 100) $perPage = 100; // Cap maximum to prevent memory exhaustion

        $products = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Products fetched successfully.',
            'data' => ProductResource::collection($products),
            'pagination' => [
                'total' => $products->total(),
                'count' => $products->count(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'total_pages' => $products->lastPage(),
            ],
        ]);
    }

    public function show($slug)
    {
        $product = Product::with(['category', 'brand', 'variations.multiAttributeValues.attribute', 'variations.multiAttributeValues.attributeValue'])
            ->where('status', 'active')
            ->where(function ($q) use ($slug) {
                if (is_numeric($slug)) {
                    $q->where('id', $slug);
                } else {
                    $q->where('slug', $slug);
                }
            })
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product details fetched successfully.',
            'data' => new ProductResource($product),
        ]);
    }

    public function variations(Product $product)
    {
        $product->load(['variations.multiAttributeValues.attribute', 'variations.multiAttributeValues.attributeValue']);

        return response()->json([
            'success' => true,
            'message' => 'Product variations fetched successfully.',
            'data' => VariationResource::collection($product->variations),
        ]);
    }
}
