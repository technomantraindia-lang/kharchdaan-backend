<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashbackPayoutBatchItem extends Model
{
    protected $table = 'cashback_payout_batch_items';

    protected $fillable = [
        'batch_id',
        'cashback_eligibility_id',
        'order_id',
        'member_id',
        'amount',
        'record_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:18',
            'record_snapshot' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CashbackPayoutBatch::class, 'batch_id');
    }

    public function cashbackEligibility(): BelongsTo
    {
        return $this->belongsTo(CashbackEligibility::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
