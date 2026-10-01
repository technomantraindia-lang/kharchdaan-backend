<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MlmCalculationRule extends Model
{
    protected $table = 'mlm_calculation_rules';

    protected $fillable = [
        'version',
        'name',
        'high_pv_rate',
        'low_pv_rate',
        'pv_divisor',
        'income_rate',
        'high_level_start',
        'high_level_end',
        'low_level_start',
        'low_level_end',
        'status',
        'effective_from',
        'effective_to',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'high_level_start' => 'integer',
            'high_level_end' => 'integer',
            'low_level_start' => 'integer',
            'low_level_end' => 'integer',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function calculationRuns(): HasMany
    {
        return $this->hasMany(MlmCalculationRun::class, 'rule_version_id');
    }

    public function incomeLedgers(): HasMany
    {
        return $this->hasMany(MlmIncomeLedger::class, 'rule_version_id');
    }
}
