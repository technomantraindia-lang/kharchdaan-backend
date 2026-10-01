<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashbackStatusHistory extends Model
{
    protected $table = 'cashback_status_histories';

    protected $fillable = [
        'cashback_eligibility_id',
        'batch_id',
        'from_status',
        'to_status',
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
