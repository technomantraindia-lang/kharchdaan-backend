<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_num,
            'status' => $this->status,
            'payment_status' => $this->pay_status,
            'total' => (float) $this->total,
            'subtotal' => (float) $this->subtotal,
            'shipping_charge' => (float) $this->ship_charge,
            'discount' => (float) $this->discount,
            'currency' => $this->currency ?? 'INR',
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name ?? $item->product?->name,
                    'sku' => $item->sku ?? $item->product?->sku,
                    'quantity' => (int) $item->qty,
                    'price' => (float) $item->price,
                    'total' => (float) ($item->line_total ?? ($item->qty * $item->price)),
                ];
            }),
        ];
    }
}
