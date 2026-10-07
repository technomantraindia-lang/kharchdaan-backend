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
        if ($this->image) {
            $images[] = $this->image;
        } elseif ($this->image_url) {
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

        $price = (float) ($this->sale_price ?: $this->price ?: 0);
        $mrp = (float) ($this->price ?: $price);
        if ($mrp < $price) {
            $mrp = round($price * 1.20);
        }
        $discountPct = ($mrp > $price) ? round((($mrp - $price) / $mrp) * 100) : 15;

        $categoryName = $this->category ? $this->category->name : 'Daily Needs';
        $brandName = $this->brand ? $this->brand->name : 'KharchDaan';

        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'brand' => $brandName,
            'brand_name' => $brandName,
            'brand_details' => $this->whenLoaded('brand', fn() => new BrandResource($this->brand)),
            'category' => $categoryName,
            'category_name' => $categoryName,
            'category_slug' => $this->category ? $this->category->slug : 'daily-needs',
            'category_details' => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
            'subCategory' => $this->subCategory ? $this->subCategory->name : null,
            'sub_category' => $this->subCategory ? $this->subCategory->name : null,
            'description' => $this->description,
            'shortDescription' => $this->short_desc ?? $this->short_description ?? null,
            'short_description' => $this->short_desc ?? $this->short_description ?? null,
            'price' => $price,
            'mrp' => $mrp,
            'sale_price' => $this->sale_price ? (float) $this->sale_price : null,
            'display_price' => $price,
            'discount' => "{$discountPct}% OFF",
            'weight' => $this->weight ? ($this->weight . ($this->unit ? ' ' . $this->unit : '')) : ($this->unit ?: '1 Unit'),
            'image' => $this->image ?: $this->image_url,
            'image_url' => $this->image_url,
            'images' => array_values(array_unique($images)),
            'cashbackPercent' => 100,
            'cashback_percent' => 100,
            'cashbackAmount' => max(15, (int) round($price * 0.10)),
            'cashback_amount' => max(15, (int) round($price * 0.10)),
            'pvPoints' => max(25, (int) round($price * 0.20)),
            'pv_points' => max(25, (int) round($price * 0.20)),
            'rating' => 4.9,
            'reviews' => 240,
            'inStock' => $this->available_stock > 0,
            'stock_status' => $this->available_stock > 0 ? 'in_stock' : 'out_of_stock',
            'available_stock' => (int) $this->available_stock,
            'featured' => (bool) ($this->featured ?? false),
            'variants' => $this->whenLoaded('variations', function () use ($price, $mrp) {
                if ($this->variations->isEmpty()) {
                    return [
                        ['id' => 'v1', 'size' => $this->unit ?: 'Standard', 'price' => $price, 'mrp' => $mrp, 'isDefault' => true]
                    ];
                }
                return $this->variations->map(function ($v, $idx) use ($mrp) {
                    $vPrice = (float) ($v->sale_price ?: $v->price ?: 0);
                    $vMrp = (float) ($v->price ?: $mrp);
                    return [
                        'id' => 'v' . ($idx + 1),
                        'size' => $v->attr_val,
                        'price' => $vPrice,
                        'mrp' => $vMrp,
                        'isDefault' => $idx === 0,
                    ];
                })->values();
            }),
            'variations' => VariationResource::collection($this->whenLoaded('variations')),
        ];
    }
}
