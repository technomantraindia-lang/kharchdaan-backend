<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MlmCalculationRun extends Model
{
    protected $table = 'mlm_calculation_runs';

    protected $fillable = [
        'purchasing_member_id',
        'order_id',
        'rule_version_id',
        'source_transaction_reference',
        'idempotency_key',
        'transaction_date',
        'eligible_amount',
        'total_pv',
        'total_income',
        'status',
        'placement_path_snapshot',
        'calculated_by',
        'processing_started_at',
        'processed_at',
        'processing_time_ms',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'eligible_amount' => 'decimal:18',
            'total_pv' => 'decimal:18',
            'total_income' => 'decimal:18',
            'processing_started_at' => 'datetime',
            'processed_at' => 'datetime',
            'processing_time_ms' => 'integer',
            'placement_path_snapshot' => 'array',
        ];
    }

    public function purchasingMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'purchasing_member_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function ruleVersion(): BelongsTo
    {
        return $this->belongsTo(MlmCalculationRule::class, 'rule_version_id');
    }

    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function incomeLedgers(): HasMany
    {
        return $this->hasMany(MlmIncomeLedger::class, 'calculation_run_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(MlmCalculationAudit::class, 'calculation_run_id');
    }

    public function cashbackEligibility(): HasOne
    {
        return $this->hasOne(CashbackEligibility::class, 'calculation_run_id');
    }
}
