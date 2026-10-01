<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RtoShipment extends Model
{
    protected $table = 'rto_shipments';

    protected $fillable = [
        'rto_num',
        'order_id',
        'shipment_id',
        'reason',
        'status',
        'received_at',
        'inspected_at',
        'restocked_at',
        'damaged_qty',
        'restocked_qty',
        'created_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'received_at' => 'datetime',
            'inspected_at' => 'datetime',
            'restocked_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
