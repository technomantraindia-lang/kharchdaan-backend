<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use LogicException;

class CashbackEligibility extends Model
{
    protected $table = 'cashback_eligibilities';

    public const STATUS_NOT_ELIGIBLE = 'not_eligible';

    public const STATUS_ELIGIBLE_AWAITING_COMPANY_PROFIT = 'eligible_awaiting_company_profit';

    public const STATUS_SELECTED_BY_ADMIN = 'selected_by_admin';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PAID = 'paid';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_REVERSED = 'reversed';

    public const STATUS_CANCELLED_DUE_TO_REFUND = 'cancelled_due_to_refund';

    public const STATUSES = [
        self::STATUS_NOT_ELIGIBLE,
        self::STATUS_ELIGIBLE_AWAITING_COMPANY_PROFIT,
        self::STATUS_SELECTED_BY_ADMIN,
        self::STATUS_APPROVED,
        self::STATUS_SCHEDULED,
        self::STATUS_PROCESSING,
        self::STATUS_PAID,
        self::STATUS_ON_HOLD,
        self::STATUS_REVERSED,
        self::STATUS_CANCELLED_DUE_TO_REFUND,
    ];

    protected $fillable = [
        'order_id',
        'member_id',
        'calculation_run_id',
        'rule_version_id',
        'idempotency_key',
        'final_eligible_amount',
        'original_eligible_amount',
        'current_eligible_amount',
        'maximum_cashback_amount',
        'original_cashback_amount',
        'refunded_amount',
        'eligibility_threshold',
        'formula_version',
        'status',
        'ineligibility_reason',
        'eligibility_date',
        'eligibility_snapshot',
        'selected_pool_id',
        'selected_by',
        'selected_at',
        'refund_status',
        'last_processing_error',
        'last_processed_at',
    ];

    protected function casts(): array
    {
        return [
            'final_eligible_amount' => 'decimal:18',
            'original_eligible_amount' => 'decimal:18',
            'current_eligible_amount' => 'decimal:18',
            'maximum_cashback_amount' => 'decimal:18',
            'original_cashback_amount' => 'decimal:18',
            'refunded_amount' => 'decimal:18',
            'eligibility_threshold' => 'decimal:18',
            'eligibility_date' => 'datetime',
            'eligibility_snapshot' => 'array',
            'selected_at' => 'datetime',
            'last_processed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $cashback): void {
            if ($cashback->status !== null && ! in_array($cashback->status, self::STATUSES, true)) {
                throw new InvalidArgumentException('Invalid cashback status.');
            }
        });

        static::updating(function (self $cashback): void {
            if ($cashback->getOriginal('status') !== self::STATUS_PAID) {
                return;
            }

            foreach ($cashback->getDirty() as $field => $value) {
                if (! in_array($field, ['status', 'ineligibility_reason', 'updated_at'], true)) {
                    throw new LogicException('Paid cashback financial values are immutable.');
                }
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function calculationRun(): BelongsTo
    {
        return $this->belongsTo(MlmCalculationRun::class, 'calculation_run_id');
    }

    public function ruleVersion(): BelongsTo
    {
        return $this->belongsTo(MlmCalculationRule::class, 'rule_version_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(CashbackAdjustment::class);
    }

    public function selectedPool(): BelongsTo
    {
        return $this->belongsTo(CashbackProfitPool::class, 'selected_pool_id');
    }

    public function selectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by');
    }

    public function payoutBatchItems(): HasMany
    {
        return $this->hasMany(CashbackPayoutBatchItem::class, 'cashback_eligibility_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(CashbackStatusHistory::class, 'cashback_eligibility_id');
    }

    public function actionHistories(): HasMany
    {
        return $this->hasMany(CashbackActionHistory::class, 'cashback_eligibility_id');
    }
}
