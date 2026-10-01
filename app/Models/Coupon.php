<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = ['code', 'type', 'value', 'min_order', 'max_discount', 'usage_limit', 'per_user_limit', 'start_date', 'end_date', 'status', 'woocommerce_id'];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        if ($this->start_date && $this->start_date > now()->toDateString()) {
            return false;
        }
        if ($this->end_date && $this->end_date < now()->toDateString()) {
            return false;
        }

        return true;
    }

    public function canUse($userId): bool
    {
        if (! $this->isValid()) {
            return false;
        }
        if ($this->usage_limit && $this->usages()->count() >= $this->usage_limit) {
            return false;
        }
        if ($this->per_user_limit) {
            $userUsage = $this->usages()->where('user_id', $userId)->count();
            if ($userUsage >= $this->per_user_limit) {
                return false;
            }
        }

        return true;
    }
}
