<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attributes = [];
        if ($this->multiAttributeValues && $this->multiAttributeValues->count()) {
            foreach ($this->multiAttributeValues as $vav) {
                if ($vav->attribute && $vav->attributeValue) {
                    $attributes[$vav->attribute->name] = $vav->attributeValue->value;
                }
            }
        } elseif ($this->attribute) {
            $attributes[$this->attribute->name] = $this->attr_val;
        }

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price ? (float) $this->sale_price : null,
            'stock_status' => $this->isInStock() ? 'in_stock' : 'out_of_stock',
            'available_stock' => (int) $this->available_stock,
            'image' => $this->image ? route('media.file', ['path' => ltrim($this->image, '/')]) : null,
            'weight' => $this->weight ? (float) $this->weight : null,
            'attributes' => $attributes,
        ];
    }
}
