<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PlacementMovement extends Model
{
    protected $table = 'mlm_placement_movements';

    protected $fillable = [
        'member_id',
        'old_parent_id',
        'new_parent_id',
        'old_position',
        'new_position',
        'reason',
        'moved_by',
        'effective_at',
        'affected_subtree_count',
        'status',
        'approved_at',
        'old_path_snapshot',
        'new_path_snapshot',
        'old_depth',
        'new_depth',
    ];

    protected function casts(): array
    {
        return [
            'effective_at' => 'datetime',
            'affected_subtree_count' => 'integer',
            'approved_at' => 'datetime',
            'old_path_snapshot' => 'array',
            'new_path_snapshot' => 'array',
            'old_depth' => 'integer',
            'new_depth' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Placement movement history is immutable.');
        });

        static::deleting(function (): void {
            throw new LogicException('Placement movement history is immutable.');
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function oldParent(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'old_parent_id');
    }

    public function newParent(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'new_parent_id');
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }
}
