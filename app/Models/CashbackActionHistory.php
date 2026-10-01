<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashbackActionHistory extends Model
{
    protected $table = 'cashback_action_histories';

    protected $fillable = [
        'cashback_eligibility_id',
        'batch_id',
        'action',
        'reason',
        'acted_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function cashbackEligibility(): BelongsTo
    {
        return $this->belongsTo(CashbackEligibility::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CashbackPayoutBatch::class, 'batch_id');
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
