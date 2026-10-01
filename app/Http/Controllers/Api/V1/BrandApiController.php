<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Models\Brand;

class BrandApiController extends Controller
{
    public function index()
    {
        $brands = Brand::where('status', 'active')->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Brands fetched successfully.',
            'data' => BrandResource::collection($brands),
        ]);
    }
}
