<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashbackProfitPool extends Model
{
    protected $table = 'cashback_profit_pools';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'pool_reference',
        'approved_available_amount',
        'allocated_amount',
        'status',
        'approved_by',
        'approved_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'approved_available_amount' => 'decimal:18',
            'allocated_amount' => 'decimal:18',
            'approved_at' => 'datetime',
        ];
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(CashbackPayoutBatch::class, 'pool_id');
    }

    public function selectedCashbacks(): HasMany
    {
        return $this->hasMany(CashbackEligibility::class, 'selected_pool_id');
    }
}
