<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class MlmPayoutLine extends Model
{
    protected $table = 'mlm_payout_lines';

    protected $fillable = [
        'payout_cycle_id',
        'member_id',
        'gross_income',
        'adjustment_amount',
        'net_payable',
        'ledger_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'gross_income' => 'decimal:18',
            'adjustment_amount' => 'decimal:18',
            'net_payable' => 'decimal:18',
            'ledger_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $line): void {
            if ($line->getOriginal('status') !== MlmPayoutCycle::STATUS_PAID) {
                return;
            }

            foreach ($line->getDirty() as $field => $value) {
                if ($field !== 'updated_at') {
                    throw new LogicException('Paid MLM payout lines are immutable.');
                }
            }
        });
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(MlmPayoutCycle::class, 'payout_cycle_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
