<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class MlmIncomeLedger extends Model
{
    protected $table = 'mlm_income_ledgers';

    public const TYPE_OWN_PURCHASE = 'own_purchase';

    public const TYPE_REFERRAL = 'referral';

    public const TYPE_LEVEL = 'level_income';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_REVERSAL = 'reversal';

    public const TYPES = [
        self::TYPE_OWN_PURCHASE,
        self::TYPE_REFERRAL,
        self::TYPE_LEVEL,
        self::TYPE_ADJUSTMENT,
        self::TYPE_REVERSAL,
    ];

    public const STATUS_CALCULATED = 'calculated';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'calculation_run_id',
        'member_id',
        'purchasing_member_id',
        'rule_version_id',
        'source_transaction_reference',
        'transaction_date',
        'income_type',
        'level',
        'eligible_amount',
        'pv_rate',
        'rate',
        'pv',
        'calculated_amount',
        'status',
        'placement_path_snapshot',
        'payout_cycle_id',
        'reversal_of_id',
        'source_refund_id',
        'reversal_event_key',
        'ledger_entry_key',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'level' => 'integer',
            'placement_path_snapshot' => 'array',
            'eligible_amount' => 'decimal:18',
            'pv_rate' => 'decimal:18',
            'rate' => 'decimal:18',
            'pv' => 'decimal:18',
            'calculated_amount' => 'decimal:18',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $ledger): void {
            foreach ([
                'calculation_run_id',
                'member_id',
                'purchasing_member_id',
                'rule_version_id',
                'source_transaction_reference',
                'transaction_date',
                'income_type',
                'level',
                'eligible_amount',
                'pv_rate',
                'rate',
                'pv',
                'calculated_amount',
                'placement_path_snapshot',
                'reversal_of_id',
                'source_refund_id',
                'reversal_event_key',
                'ledger_entry_key',
            ] as $field) {
                if ($ledger->isDirty($field)) {
                    throw new LogicException('Historical income ledger values are immutable.');
                }
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Income ledger history is immutable.');
        });
    }

    public function calculationRun(): BelongsTo
    {
        return $this->belongsTo(MlmCalculationRun::class, 'calculation_run_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function purchasingMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'purchasing_member_id');
    }

    public function ruleVersion(): BelongsTo
    {
        return $this->belongsTo(MlmCalculationRule::class, 'rule_version_id');
    }

    public function payoutCycle(): BelongsTo
    {
        return $this->belongsTo(MlmPayoutCycle::class, 'payout_cycle_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function sourceRefund(): BelongsTo
    {
        return $this->belongsTo(Refund::class, 'source_refund_id');
    }
}
