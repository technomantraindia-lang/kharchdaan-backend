<?php

namespace App\Services\WooCommerce;

use App\Models\Role;
use App\Models\User;
use App\Models\WooCommerceSyncLog;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class CustomerSyncService
{
    protected WooCommerceClient $client;

    public function __construct(WooCommerceClient $client)
    {
        $this->client = $client;
    }

    public function syncCustomerByWooCommerceId(int $wcCustomerId): ?User
    {
        if ($wcCustomerId <= 0) {
            return null; // Guest customer
        }

        if (! $this->client->isConfigured()) {
            return null;
        }

        try {
            $payload = $this->client->get("customers/{$wcCustomerId}");
            return $this->importCustomerPayload($payload);
        } catch (Throwable $e) {
            WooCommerceSyncLog::log(
                'customer',
                null,
                $wcCustomerId,
                'woocommerce_to_laravel',
                'sync',
                'failed',
                null,
                null,
                $e->getMessage()
            );

            return null;
        }
    }

    public function importCustomerPayload(array $payload): ?User
    {
        $wcCustomerId = (int) ($payload['id'] ?? 0);
        if ($wcCustomerId <= 0) {
            return null;
        }

        $email = trim(strtolower((string) ($payload['email'] ?? '')));
        if (empty($email)) {
            return null;
        }

        // 1. Try matching existing user by woocommerce_customer_id first
        $user = User::where('woocommerce_customer_id', $wcCustomerId)->first();

        // 2. Secondary match by email if not found by woocommerce_customer_id
        if (! $user) {
            $user = User::where('email', $email)->first();
        }

        // 3. SECURITY RULE: Never allow WooCommerce customer sync to overwrite Admin / Super Admin accounts!
        if ($user && $user->isAdmin()) {
            ActivityLogService::log(
                'security_warning',
                'woocommerce',
                "WooCommerce customer sync attempted to match Admin user ({$user->email}). Overwrite blocked."
            );
            WooCommerceSyncLog::log(
                'customer',
                $user->id,
                $wcCustomerId,
                'woocommerce_to_laravel',
                'sync',
                'failed',
                $payload,
                null,
                "Security Block: Cannot overwrite Admin account ({$user->email})."
            );
            return $user;
        }

        // Resolve Customer role strictly by NAME
        $customerRole = Role::where('name', 'Customer')->first();

        if (! $customerRole) {
            ActivityLogService::log(
                'sync_error',
                'woocommerce',
                "Customer role 'Customer' does not exist in system. Customer sync aborted for WC ID {$wcCustomerId} ({$email})."
            );
            WooCommerceSyncLog::log(
                'customer',
                null,
                $wcCustomerId,
                'woocommerce_to_laravel',
                'sync',
                'failed',
                $payload,
                null,
                "Configuration Error: 'Customer' role does not exist in database."
            );
            return null;
        }

        $roleId = $customerRole->id;

        $firstName = $payload['first_name'] ?? '';
        $lastName = $payload['last_name'] ?? '';
        $name = trim("{$firstName} {$lastName}");
        if (empty($name)) {
            $name = $payload['username'] ?? explode('@', $email)[0];
        }

        $phone = $payload['billing']['phone'] ?? ($payload['shipping']['phone'] ?? null);

        if ($user) {
            // Update existing customer details safely WITHOUT resetting password
            $user->update([
                'name' => $name,
                'phone' => $phone ?: $user->phone,
                'woocommerce_customer_id' => $wcCustomerId,
            ]);
            $action = 'update';
        } else {
            // Create new Customer record with random hashed password
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'phone' => $phone,
                'role_id' => $roleId,
                'status' => 'active',
                'woocommerce_customer_id' => $wcCustomerId,
            ]);
            $action = 'create';
        }

        WooCommerceSyncLog::log(
            'customer',
            $user->id,
            $wcCustomerId,
            'woocommerce_to_laravel',
            $action,
            'success',
            $payload,
            ['user_id' => $user->id]
        );

        return $user;
    }
}
