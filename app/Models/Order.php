<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_num', 'user_id', 'subtotal', 'discount', 'gst_amt', 'ship_charge', 'total',
        'coupon_id', 'ship_id', 'status', 'pay_status', 'tracking_num', 'courier', 'admin_note', 'bill_addr', 'ship_addr',
        'woocommerce_id', 'woocommerce_synced_at', 'currency', 'payment_method', 'txn_id',
        'seller_state', 'customer_state', 'cgst_amt', 'sgst_amt', 'igst_amt',
        'mlm_processing_status', 'mlm_eligible_amount', 'mlm_pv_processing_status',
        'mlm_formula_version', 'mlm_integration_reference', 'mlm_reversal_status',
        'mlm_error_message', 'mlm_processed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'gst_amt' => 'decimal:2',
        'ship_charge' => 'decimal:2',
        'total' => 'decimal:2',
        'mlm_eligible_amount' => 'decimal:2',
        'mlm_processed_at' => 'datetime',
    ];

    public const MLM_NOT_STARTED = 'not_started';

    public const MLM_WAITING_PAYMENT = 'waiting_payment';

    public const MLM_PROCESSING = 'processing';

    public const MLM_COMPLETED = 'completed';

    public const MLM_NOT_ELIGIBLE = 'not_eligible';

    public const MLM_FAILED = 'failed';

    public const MLM_REVERSED = 'reversed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function mlmCalculationRuns(): HasMany
    {
        return $this->hasMany(MlmCalculationRun::class);
    }

    public function cashbackEligibility(): HasOne
    {
        return $this->hasOne(CashbackEligibility::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'ship_id');
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'packed' => 'primary',
            'shipped' => 'primary',
            'delivered' => 'success',
            'cancelled', 'failed' => 'danger',
            'refunded' => 'secondary',
            default => 'secondary',
        };
    }

    public function payStatusBadgeClass(): string
    {
        return match ($this->pay_status) {
            'paid' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            'refunded' => 'secondary',
            default => 'secondary',
        };
    }

    public static function statusSteps(): array
    {
        return ['pending', 'processing', 'packed', 'shipped', 'delivered'];
    }

    public function statusStepIndex(): int
    {
        $steps = self::statusSteps();
        $idx = array_search($this->status, $steps);

        return $idx !== false ? $idx : -1;
    }
}
