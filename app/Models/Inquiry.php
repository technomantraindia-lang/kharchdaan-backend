<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'product_id', 'msg', 'status', 'note'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
