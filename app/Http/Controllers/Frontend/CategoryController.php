<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

class CategoryController extends Controller
{
    public function show(string $slug)
    {
        $category = Category::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $products = Product::active()
            ->where(function ($query) use ($category) {
                $query->where('category_id', $category->id)
                      ->orWhere('sub_category_id', $category->id)
                      ->orWhere('sub_sub_category_id', $category->id);
            })
            ->with('category')
            ->latest()
            ->paginate(12);

        return view('frontend.categories.show', compact('category', 'products'));
    }
}
