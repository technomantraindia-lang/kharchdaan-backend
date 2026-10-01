<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $images = [];

        // Primary Image
        if ($this->image_url) {
            $images[] = $this->image_url;
        }

        // Additional Gallery Images
        if ($this->relationLoaded('images') && $this->images) {
            foreach ($this->images as $img) {
                if (!empty($img->image)) {
                    $images[] = str_starts_with($img->image, 'http')
                        ? $img->image
                        : route('media.file', ['path' => ltrim($img->image, '/')]);
                }
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'short_description' => $this->short_desc ?? $this->short_description ?? null,
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price ? (float) $this->sale_price : null,
            'display_price' => (float) $this->display_price,
            'image_url' => $this->image_url,
            'stock_status' => $this->available_stock > 0 ? 'in_stock' : 'out_of_stock',
            'available_stock' => (int) $this->available_stock,
            'featured' => (bool) ($this->featured ?? false),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'images' => array_values(array_unique($images)),
            'variations' => VariationResource::collection($this->whenLoaded('variations')),
        ];
    }
}
