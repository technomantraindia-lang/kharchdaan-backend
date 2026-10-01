<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CashbackAdjustment extends Model
{
    protected $table = 'cashback_adjustments';

    public const TYPE_REFUND_RECOVERY = 'refund_recovery';

    public const TYPE_MANUAL_REVERSAL = 'manual_reversal';

    protected $fillable = [
        'cashback_eligibility_id',
        'order_id',
        'member_id',
        'adjustment_type',
        'amount',
        'idempotency_key',
        'reason',
        'adjustment_snapshot',
        'created_by',
        'source_refund_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:18',
            'adjustment_snapshot' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Cashback adjustment history is immutable.');
        });

        static::deleting(function (): never {
            throw new LogicException('Cashback adjustment history is immutable.');
        });
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sourceRefund(): BelongsTo
    {
        return $this->belongsTo(Refund::class, 'source_refund_id');
    }
}
