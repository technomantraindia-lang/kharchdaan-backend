<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashbackPayoutBatch extends Model
{
    protected $table = 'cashback_payout_batches';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'pool_id',
        'batch_reference',
        'total_amount',
        'record_count',
        'status',
        'scheduled_payment_date',
        'payment_reference',
        'payment_proof_path',
        'admin_note',
        'created_by',
        'approved_by',
        'scheduled_by',
        'processed_by',
        'paid_by',
        'approved_at',
        'scheduled_at',
        'processed_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:18',
            'record_count' => 'integer',
            'scheduled_payment_date' => 'datetime',
            'approved_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'processed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(CashbackProfitPool::class, 'pool_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CashbackPayoutBatchItem::class, 'batch_id');
    }

    public function actionHistories(): HasMany
    {
        return $this->hasMany(CashbackActionHistory::class, 'batch_id');
    }
}
