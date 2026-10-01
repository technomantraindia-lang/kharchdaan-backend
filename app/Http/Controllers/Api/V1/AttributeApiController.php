<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttributeResource;
use App\Models\ProductAttribute;

class AttributeApiController extends Controller
{
    public function index()
    {
        $attributes = ProductAttribute::with('values')->where('status', 'active')->get();

        return response()->json([
            'success' => true,
            'message' => 'Attributes fetched successfully.',
            'data' => AttributeResource::collection($attributes),
        ]);
    }
}
