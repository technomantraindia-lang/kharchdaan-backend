<?php

namespace Tests\Feature;

use App\Jobs\ImportWooCommerceOrder;
use App\Jobs\SyncWooCommerceCustomer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\WooCommerceSyncLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QueuedJobsAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Role $customerRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);
    }

    /** 1. Test job configuration attributes */
    public function test_1_job_retry_configuration_attributes(): void
    {
        $job = new ImportWooCommerceOrder(['id' => 12345]);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals([10, 30, 60], $job->backoff);
        $this->assertEquals(120, $job->timeout);
    }

    /** 2. Test ImportWooCommerceOrder job is idempotent on retries */
    public function test_2_import_woocommerce_order_job_is_idempotent(): void
    {
        $payload = [
            'id' => 998877,
            'number' => '998877',
            'status' => 'processing',
            'total' => '250.00',
            'currency' => 'INR',
            'line_items' => [
                ['id' => 1, 'name' => 'Test Item', 'quantity' => 1, 'price' => 250.00, 'total' => 250.00],
            ],
        ];

        // Execute job first time
        $job1 = new ImportWooCommerceOrder($payload);
        $job1->handle(app(\App\Services\WooCommerce\OrderSyncService::class));
        $this->assertEquals(1, Order::where('woocommerce_id', 998877)->count());

        // Retry exact same job second time
        $job2 = new ImportWooCommerceOrder($payload);
        $job2->handle(app(\App\Services\WooCommerce\OrderSyncService::class));

        // Order count must STILL be 1 (no duplicate order created)
        $this->assertEquals(1, Order::where('woocommerce_id', 998877)->count());
    }

    /** 3. Test SyncWooCommerceCustomer job is idempotent on retries */
    public function test_3_sync_woocommerce_customer_job_is_idempotent(): void
    {
        $payload = [
            'id' => 554433,
            'email' => 'retrycustomer@example.com',
            'first_name' => 'Retry',
            'last_name' => 'Customer',
        ];

        $job1 = new SyncWooCommerceCustomer($payload);
        $job1->handle(app(\App\Services\WooCommerce\CustomerSyncService::class));
        $this->assertEquals(1, User::where('woocommerce_customer_id', 554433)->count());

        $job2 = new SyncWooCommerceCustomer($payload);
        $job2->handle(app(\App\Services\WooCommerce\CustomerSyncService::class));

        $this->assertEquals(1, User::where('woocommerce_customer_id', 554433)->count());
    }

    /** 4. Test job failure sends alert notification and records log */
    public function test_4_job_failure_records_log_and_notifies_staff(): void
    {
        Notification::fake();

        $job = new ImportWooCommerceOrder(['id' => 888111]);
        $job->failed(new \RuntimeException('Connection timeout to WooCommerce'));

        $this->assertDatabaseHas('woocommerce_sync_logs', [
            'woocommerce_id' => 888111,
            'status' => 'failed',
        ]);
    }
}
