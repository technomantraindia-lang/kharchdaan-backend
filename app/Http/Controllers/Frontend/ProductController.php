<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::active()->with('category');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('short_desc', 'like', "%{$s}%"));
        }

        $products = $query->latest()->paginate(12)->withQueryString();
        $categories = Category::where('status', 'active')->whereNull('parent_id')->get();

        return view('frontend.products.index', compact('products', 'categories'));
    }

    public function show(string $slug)
    {
        $product = Product::active()->with(['category', 'subCategory', 'subSubCategory', 'brand', 'images'])->where('slug', $slug)->firstOrFail();

        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)->get();

        return view('frontend.products.show', compact('product', 'related'));
    }
}
