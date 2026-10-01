<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_id', 'var_id', 'qty', 'price', 'sale_price'];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'var_id');
    }

    public function getLineTotal(): float
    {
        $price = $this->sale_price ?? $this->price;

        return $price * $this->qty;
    }
}
