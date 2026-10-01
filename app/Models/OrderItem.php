<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'var_id', 'qty', 'price', 'gst_pct',
        'product_name', 'sku', 'discount', 'line_total',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'gst_pct' => 'decimal:2',
    ];

    public $timestamps = false;

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'var_id');
    }
}
