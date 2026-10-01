<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasPermission('products.edit');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'sku' => 'required|string|max:100|unique:products,sku',
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
        ];
    }
}
