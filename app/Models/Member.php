<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use InvalidArgumentException;

class Member extends Model
{
    protected $table = 'mlm_members';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PENDING = 'pending';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_BLOCKED,
    ];

    public const KYC_PENDING = 'pending';

    public const KYC_UNDER_REVIEW = 'under_review';

    public const KYC_APPROVED = 'approved';

    public const KYC_REJECTED = 'rejected';

    public const KYC_STATUSES = [
        self::KYC_PENDING,
        self::KYC_UNDER_REVIEW,
        self::KYC_APPROVED,
        self::KYC_REJECTED,
    ];

    public const POSITION_LEFT = 'left';

    public const POSITION_MIDDLE = 'middle';

    public const POSITION_RIGHT = 'right';

    public const POSITIONS = [
        self::POSITION_LEFT,
        self::POSITION_MIDDLE,
        self::POSITION_RIGHT,
    ];

    protected $fillable = [
        'user_id',
        'sponsor_member_id',
        'placement_parent_id',
        'placement_position',
        'status',
        'joined_at',
        'profile_photo_path',
        'created_by',
        'sponsor_name_snapshot',
        'sponsor_relationship_status',
        'sponsor_assigned_at',
        'kyc_status',
        'pan_number',
        'pan_hash',
        'aadhaar_reference',
        'aadhaar_hash',
        'bank_account_holder_name',
        'bank_account_number',
        'bank_account_hash',
        'ifsc_code',
        'bank_name',
        'bank_branch',
        'cancelled_cheque_path',
        'kyc_rejection_reason',
        'kyc_verified_by',
        'kyc_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'sponsor_assigned_at' => 'datetime',
            'pan_number' => 'encrypted',
            'aadhaar_reference' => 'encrypted',
            'bank_account_number' => 'encrypted',
            'kyc_verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $member): void {
            if ($member->getKey() !== null && $member->sponsor_member_id === $member->getKey()) {
                throw new InvalidArgumentException('A member cannot sponsor themselves.');
            }

            if ($member->getKey() !== null && $member->placement_parent_id === $member->getKey()) {
                throw new InvalidArgumentException('A member cannot be their own placement parent.');
            }

            if ($member->status !== null && ! in_array($member->status, self::STATUSES, true)) {
                throw new InvalidArgumentException('Invalid MLM member status.');
            }

            if ($member->kyc_status !== null && ! in_array($member->kyc_status, self::KYC_STATUSES, true)) {
                throw new InvalidArgumentException('Invalid MLM KYC status.');
            }

            if ($member->placement_position !== null
                && ! in_array($member->placement_position, self::POSITIONS, true)) {
                throw new InvalidArgumentException('Invalid MLM placement position.');
            }

            if ($member->placement_parent_id === null && $member->placement_position !== null) {
                throw new InvalidArgumentException('A root member cannot have a placement position.');
            }

            if ($member->placement_parent_id !== null && $member->placement_position === null) {
                throw new InvalidArgumentException('A placed member must have a placement position.');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'sponsor_member_id');
    }

    public function sponsoredMembers(): HasMany
    {
        return $this->hasMany(self::class, 'sponsor_member_id');
    }

    public function placementParent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'placement_parent_id');
    }

    public function placementChildren(): HasMany
    {
        return $this->hasMany(self::class, 'placement_parent_id');
    }

    public function placementMovements(): HasMany
    {
        return $this->hasMany(PlacementMovement::class, 'member_id');
    }

    public function calculationRuns(): HasMany
    {
        return $this->hasMany(MlmCalculationRun::class, 'purchasing_member_id');
    }

    public function incomeLedgers(): HasMany
    {
        return $this->hasMany(MlmIncomeLedger::class, 'member_id');
    }

    public function cashbackEligibilities(): HasMany
    {
        return $this->hasMany(CashbackEligibility::class, 'member_id');
    }

    public function kycHistories(): HasMany
    {
        return $this->hasMany(MlmKycHistory::class, 'member_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function kycVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kyc_verified_by');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getCustomerIdAttribute(): ?string
    {
        return $this->user?->mlm_member_id;
    }

    public function getMobileAttribute(): ?string
    {
        return $this->user?->phone;
    }

    public function getMaskedPanAttribute(): ?string
    {
        return $this->maskValue($this->pan_number, 4);
    }

    public function getMaskedAadhaarAttribute(): ?string
    {
        return $this->maskValue($this->aadhaar_reference, 4);
    }

    public function getMaskedBankAccountAttribute(): ?string
    {
        return $this->maskValue($this->bank_account_number, 4);
    }

    private function maskValue(?string $value, int $visibleCharacters): ?string
    {
        if (! $value) {
            return null;
        }

        $visible = substr($value, -$visibleCharacters);

        return str_repeat('*', max(strlen($value) - $visibleCharacters, 0)).$visible;
    }
}
