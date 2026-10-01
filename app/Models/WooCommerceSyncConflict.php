<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WooCommerceSyncConflict extends Model
{
    protected $table = 'woocommerce_sync_conflicts';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'woocommerce_id',
        'field_name',
        'laravel_value',
        'woocommerce_value',
        'status',
        'resolution',
        'resolved_by',
        'resolved_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
