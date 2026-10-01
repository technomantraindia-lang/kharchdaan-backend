<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    protected $table = 'coupon_usages';

    protected $fillable = ['coupon_id', 'user_id', 'order_id'];
}
