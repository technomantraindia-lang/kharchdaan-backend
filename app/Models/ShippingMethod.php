<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $table = 'shipping_methods';

    protected $fillable = ['name', 'charge', 'min_free_order', 'status'];

    protected $casts = [
        'charge' => 'decimal:2',
        'min_free_order' => 'decimal:2',
    ];
}
