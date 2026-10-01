<?php

namespace App\Services\WooCommerce;

class OrderStatusMapper
{
    /**
     * Map WooCommerce status to Laravel internal order status.
     */
    public static function toLaravelStatus(string $wcStatus, ?string $currentLaravelStatus = null): string
    {
        $cleanWcStatus = strtolower(trim(str_replace('wc-', '', $wcStatus)));

        // Preserve internal operational workflow states if WooCommerce status is still 'processing'
        if ($currentLaravelStatus && in_array($currentLaravelStatus, ['packed', 'shipped'], true) && $cleanWcStatus === 'processing') {
            return $currentLaravelStatus;
        }

        return match ($cleanWcStatus) {
            'pending', 'on-hold' => 'pending',
            'processing' => 'processing',
            'completed' => 'delivered',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'failed' => 'failed',
            default => 'pending',
        };
    }

    /**
     * Map Laravel internal status to WooCommerce status.
     */
    public static function toWooCommerceStatus(string $laravelStatus): string
    {
        $cleanStatus = strtolower(trim($laravelStatus));

        return match ($cleanStatus) {
            'pending' => 'pending',
            'processing', 'packed', 'shipped' => 'processing',
            'delivered', 'completed' => 'completed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'failed' => 'failed',
            default => 'processing',
        };
    }

    /**
     * Map WooCommerce payment status string to Laravel pay_status.
     */
    public static function toLaravelPayStatus(string $wcStatus, bool $isPaid = false): string
    {
        $cleanWcStatus = strtolower(trim(str_replace('wc-', '', $wcStatus)));

        if ($isPaid || in_array($cleanWcStatus, ['processing', 'completed'], true)) {
            return 'paid';
        }

        return match ($cleanWcStatus) {
            'refunded' => 'refunded',
            'failed' => 'failed',
            default => 'pending',
        };
    }
}
