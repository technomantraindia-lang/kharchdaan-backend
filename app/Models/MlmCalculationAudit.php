<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MlmCalculationAudit extends Model
{
    protected $table = 'mlm_calculation_audits';

    protected $fillable = [
        'calculation_run_id',
        'event',
        'payload',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function calculationRun(): BelongsTo
    {
        return $this->belongsTo(MlmCalculationRun::class, 'calculation_run_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
