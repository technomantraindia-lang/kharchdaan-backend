<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryApiController extends Controller
{
    public function index()
    {
        $categories = Category::where('status', 'active')->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Categories fetched successfully.',
            'data' => CategoryResource::collection($categories),
        ]);
    }

    public function show($slug)
    {
        $category = Category::where('status', 'active')
            ->where(fn ($q) => is_numeric($slug) ? $q->where('id', $slug) : $q->where('slug', $slug))
            ->first();

        if (! $category) {
            return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category details fetched successfully.',
            'data' => new CategoryResource($category),
        ]);
    }
}
