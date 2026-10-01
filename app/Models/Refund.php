<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Refund extends Model
{
    protected $fillable = [
        'order_id',
        'refund_num',
        'amount',
        'reason',
        'status',
        'gateway_refund_id',
        'processed_by',
        'processed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function cashbackAdjustments(): HasMany
    {
        return $this->hasMany(CashbackAdjustment::class, 'source_refund_id');
    }
}
