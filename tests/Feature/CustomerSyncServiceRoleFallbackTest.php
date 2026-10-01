<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\WooCommerceSyncLog;
use App\Services\WooCommerce\CustomerSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSyncServiceRoleFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_fails_safely_and_logs_when_customer_role_does_not_exist(): void
    {
        // Delete Customer role if present
        Role::where('name', 'Customer')->delete();

        $service = app(CustomerSyncService::class);
        $payload = [
            'id' => 999,
            'email' => 'syncnorole@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $result = $service->importCustomerPayload($payload);

        $this->assertNull($result);
        $this->assertDatabaseMissing('users', ['email' => 'syncnorole@example.com']);
        $this->assertDatabaseHas('woocommerce_sync_logs', [
            'entity_type' => 'customer',
            'status' => 'failed',
        ]);
    }
}
