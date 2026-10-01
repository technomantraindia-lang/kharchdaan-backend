<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class MlmPayoutCycle extends Model
{
    protected $table = 'mlm_payout_cycles';

    public const STATUS_PENDING_CALCULATION = 'pending_calculation';

    public const STATUS_CALCULATING = self::STATUS_PENDING_CALCULATION;

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_REVERSED = 'reversed';

    public const STATUSES = [
        self::STATUS_PENDING_CALCULATION,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_APPROVED,
        self::STATUS_PROCESSING,
        self::STATUS_PAID,
        self::STATUS_FAILED,
        self::STATUS_ON_HOLD,
        self::STATUS_REVERSED,
    ];

    protected $fillable = [
        'period_start',
        'period_end',
        'cycle_reference',
        'status',
        'total_amount',
        'ledger_count',
        'calculation_date',
        'total_members',
        'gross_income',
        'adjustment_amount',
        'net_payable',
        'payment_reference',
        'payment_proof_path',
        'payment_date',
        'admin_note',
        'hold_reason',
        'created_by',
        'approved_by',
        'approved_at',
        'processed_by',
        'processed_at',
        'paid_by',
        'paid_at',
        'on_hold_by',
        'on_hold_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'calculation_date' => 'datetime',
            'total_amount' => 'decimal:18',
            'ledger_count' => 'integer',
            'total_members' => 'integer',
            'gross_income' => 'decimal:18',
            'adjustment_amount' => 'decimal:18',
            'net_payable' => 'decimal:18',
            'payment_date' => 'datetime',
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
            'paid_at' => 'datetime',
            'on_hold_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $cycle): void {
            if ($cycle->getOriginal('status') !== self::STATUS_PAID) {
                return;
            }

            foreach ($cycle->getDirty() as $field => $value) {
                if ($field !== 'updated_at') {
                    throw new LogicException('Paid MLM payout cycles are immutable.');
                }
            }
        });
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(MlmIncomeLedger::class, 'payout_cycle_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(MlmPayoutLine::class, 'payout_cycle_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function onHoldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'on_hold_by');
    }
}
