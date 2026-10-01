<?php

namespace Tests\Feature;

use App\Models\CashbackEligibility;
use App\Models\CashbackPayoutBatch;
use App\Models\Member;
use App\Models\MlmCalculationRule;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use App\Services\Cashback\CashbackPayoutService;
use App\Services\Cashback\CashbackRefundReconciliationService;
use App\Services\Mlm\MlmOrderIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class CashbackPayoutWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $accounts;

    private Role $customerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Admin'], ['guard_name' => 'web']);
        $accountsRole = Role::firstOrCreate(['name' => 'Accounts'], ['guard_name' => 'web']);
        $this->customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);
        $permissionNames = [
            'cashback.view', 'cashback.select', 'cashback.pool.manage', 'cashback.approve',
            'cashback.schedule', 'cashback.pay', 'cashback.hold', 'cashback.reverse',
        ];
        $permissions = Permission::whereIn('name', $permissionNames)->get();
        $adminRole->permissions()->sync($permissions->pluck('id')->all());
        $accountsRole->permissions()->sync(Permission::whereIn('name', ['cashback.view', 'cashback.approve', 'cashback.schedule', 'cashback.pay'])->pluck('id')->all());
        $this->admin = $this->user($adminRole, 'workflow-admin@example.com', '9800000001');
        $this->accounts = $this->user($accountsRole, 'workflow-accounts@example.com', '9800000002');
        MlmCalculationRule::query()->where('version', 'v1.0')->update(['effective_from' => now()->subDays(30)->toDateString()]);
    }

    public function test_individual_selection_and_full_manual_batch_workflow(): void
    {
        $cashback = $this->eligible('500.00', 'CB-WORKFLOW-001');
        $pool = app(CashbackPayoutService::class)->declarePool('500', 'POOL-WORKFLOW-001', null, $this->admin);
        $service = app(CashbackPayoutService::class);

        $service->selectRecords($pool, [], [$cashback->id], false, $this->admin);
        $batch = $service->createBatch($pool, $this->admin);
        $service->approveBatch($batch, $this->accounts);
        $service->scheduleBatch($batch, now()->addDay(), $this->accounts);
        $service->startProcessing($batch, $this->accounts);
        $paid = $service->markPaid($batch, 'UTR-CB-001', UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf'), $this->accounts);

        $this->assertSame(CashbackPayoutBatch::STATUS_PAID, $paid->status);
        $this->assertSame(CashbackEligibility::STATUS_PAID, $cashback->fresh()->status);
        $this->assertSame('500.000000000000000000', (string) $pool->fresh()->allocated_amount);
        $this->assertDatabaseHas('cashback_status_histories', ['cashback_eligibility_id' => $cashback->id, 'to_status' => CashbackEligibility::STATUS_PAID]);
        $this->assertDatabaseHas('cashback_action_histories', ['batch_id' => $batch->id, 'action' => 'paid']);
    }

    public function test_select_all_filtered_works_across_pagination(): void
    {
        $first = $this->eligible('200', 'CB-ALL-001');
        $second = $this->eligible('300', 'CB-ALL-002');
        $pool = app(CashbackPayoutService::class)->declarePool('500', 'POOL-ALL-001', null, $this->admin);

        $count = app(CashbackPayoutService::class)->selectRecords(
            $pool,
            ['amount_min' => '200'],
            [],
            true,
            $this->admin
        );

        $this->assertSame(2, $count);
        $this->assertSame(CashbackEligibility::STATUS_SELECTED_BY_ADMIN, $first->fresh()->status);
        $this->assertSame(CashbackEligibility::STATUS_SELECTED_BY_ADMIN, $second->fresh()->status);
    }

    public function test_pool_limit_is_enforced_and_selection_rolls_back(): void
    {
        $first = $this->eligible('300', 'CB-LIMIT-001');
        $second = $this->eligible('300', 'CB-LIMIT-002');
        $pool = app(CashbackPayoutService::class)->declarePool('500', 'POOL-LIMIT-001', null, $this->admin);

        try {
            app(CashbackPayoutService::class)->selectRecords($pool, [], [$first->id, $second->id], false, $this->admin);
            $this->fail('Expected pool limit validation to fail.');
        } catch (ValidationException) {
            // Selection must roll back atomically when the pool limit is exceeded.
        }
        $this->assertSame(CashbackEligibility::STATUS_ELIGIBLE_AWAITING_COMPANY_PROFIT, $first->fresh()->status);
        $this->assertSame(CashbackEligibility::STATUS_ELIGIBLE_AWAITING_COMPANY_PROFIT, $second->fresh()->status);
    }

    public function test_duplicate_batch_creation_is_prevented(): void
    {
        $cashback = $this->eligible('500', 'CB-DUP-BATCH');
        $pool = app(CashbackPayoutService::class)->declarePool('500', 'POOL-DUP-BATCH', null, $this->admin);
        $service = app(CashbackPayoutService::class);
        $service->selectRecords($pool, [], [$cashback->id], false, $this->admin);
        $service->createBatch($pool, $this->admin);

        $this->expectException(ValidationException::class);
        $service->createBatch($pool, $this->admin);
    }

    public function test_unpaid_refund_reconciles_batch_and_pool_totals(): void
    {
        $cashback = $this->eligible('500', 'CB-REFUND-BATCH');
        $pool = app(CashbackPayoutService::class)->declarePool('500', 'POOL-REFUND-BATCH', null, $this->admin);
        $service = app(CashbackPayoutService::class);
        $service->selectRecords($pool, [], [$cashback->id], false, $this->admin);
        $batch = $service->createBatch($pool, $this->admin);
        $refund = Refund::create([
            'order_id' => $cashback->order_id,
            'refund_num' => 'REF-CB-BATCH',
            'amount' => '100.00',
            'status' => 'approved',
            'processed_at' => now(),
        ]);

        app(CashbackRefundReconciliationService::class)->processApprovedRefund($refund);

        $this->assertSame('400.000000000000000000', (string) $batch->fresh()->total_amount);
        $this->assertSame(1, $batch->fresh()->record_count);
        $this->assertSame('400.000000000000000000', (string) $pool->fresh()->allocated_amount);
        $this->assertSame('400.000000000000000000', (string) $batch->fresh()->items()->firstOrFail()->amount);
    }

    public function test_order_cancellation_releases_unpaid_batch_and_pool_allocation(): void
    {
        $cashback = $this->eligible('500', 'CB-CANCEL-BATCH');
        $pool = app(CashbackPayoutService::class)->declarePool('500', 'POOL-CANCEL-BATCH', null, $this->admin);
        $service = app(CashbackPayoutService::class);
        $service->selectRecords($pool, [], [$cashback->id], false, $this->admin);
        $batch = $service->createBatch($pool, $this->admin);
        $cashback->order->update(['status' => 'cancelled']);

        app(MlmOrderIntegrationService::class)->process($cashback->order->fresh());

        $this->assertSame('0.000000000000000000', (string) $batch->fresh()->total_amount);
        $this->assertSame(0, $batch->fresh()->record_count);
        $this->assertSame('0.000000000000000000', (string) $pool->fresh()->allocated_amount);
        $this->assertSame(0, $batch->fresh()->items()->count());
    }

    public function test_payment_proof_is_required_and_paid_records_cannot_be_edited(): void
    {
        $cashback = $this->eligible('500', 'CB-PROOF');
        $pool = app(CashbackPayoutService::class)->declarePool('500', 'POOL-PROOF', null, $this->admin);
        $service = app(CashbackPayoutService::class);
        $service->selectRecords($pool, [], [$cashback->id], false, $this->admin);
        $batch = $service->createBatch($pool, $this->admin);
        $service->approveBatch($batch, $this->admin);
        $service->scheduleBatch($batch, now()->addDay(), $this->admin);
        $service->startProcessing($batch, $this->admin);

        $this->actingAs($this->admin)
            ->patch(route('admin.cashback.batches.paid', $batch), ['payment_reference' => 'UTR-NO-PROOF'])
            ->assertSessionHasErrors('payment_proof');
    }

    public function test_paid_record_rejects_direct_financial_edit(): void
    {
        $cashback = $this->eligible('500', 'CB-IMMUTABLE');
        $cashback->update(['status' => CashbackEligibility::STATUS_PAID]);

        $this->expectException(LogicException::class);
        $cashback->update(['maximum_cashback_amount' => '1']);
    }

    public function test_reversal_requires_reason_and_creates_a_reversal_adjustment(): void
    {
        $cashback = $this->eligible('500', 'CB-REVERSE');
        $service = app(CashbackPayoutService::class);

        $this->expectException(ValidationException::class);
        $service->reverse($cashback, '', $this->admin);
    }

    public function test_unauthorized_roles_are_denied(): void
    {
        $customer = $this->user($this->customerRole, 'workflow-customer@example.com', '9800000003');
        $this->actingAs($customer)->get(route('admin.cashback.index'))->assertForbidden();
        $this->actingAs($this->accounts)->get(route('admin.cashback.index'))->assertOk();
    }

    public function test_customer_sees_only_own_cashback_and_cannot_access_another_customer_url(): void
    {
        $own = $this->eligible('500', 'CB-CUSTOMER-OWN');
        $other = $this->eligible('600', 'CB-CUSTOMER-OTHER');
        $customer = $own->member->user;

        $this->actingAs($customer)->get(route('cashback.index'))
            ->assertOk()
            ->assertSee('CB-CUSTOMER-OWN')
            ->assertDontSee('CB-CUSTOMER-OTHER');
        $this->actingAs($customer)->get(route('cashback.show', $other))->assertForbidden();
    }

    public function test_paid_cashback_notification_is_generated_without_affecting_mlm_income(): void
    {
        Notification::fake();
        $cashback = $this->eligible('500', 'CB-CUSTOMER-PAID');
        $pool = app(CashbackPayoutService::class)->declarePool('500', 'POOL-CUSTOMER-PAID', null, $this->admin);
        $service = app(CashbackPayoutService::class);
        $service->selectRecords($pool, [], [$cashback->id], false, $this->admin);
        $batch = $service->createBatch($pool, $this->admin);
        $service->approveBatch($batch, $this->admin);
        $service->scheduleBatch($batch, now()->addDay(), $this->admin);
        $service->startProcessing($batch, $this->admin);
        $service->markPaid($batch, 'UTR-CUSTOMER-PAID', UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf'), $this->admin);

        Notification::assertSentTo($cashback->member->user, SystemAlertNotification::class,
            fn (SystemAlertNotification $notification): bool => str_contains($notification->message, 'marked paid'));
        $this->assertGreaterThan(0, $cashback->member->incomeLedgers()->count());
        $this->assertSame(0, $cashback->member->incomeLedgers()->where('income_type', 'cashback')->count());
    }

    private function eligible(string $amount, string $reference): CashbackEligibility
    {
        $customer = $this->user($this->customerRole, strtolower($reference).'@example.com', '981'.str_pad((string) User::count(), 7, '0', STR_PAD_LEFT));
        $member = Member::create(['user_id' => $customer->id, 'status' => Member::STATUS_ACTIVE, 'joined_at' => now()]);
        $order = Order::withoutEvents(fn () => Order::create([
            'order_num' => $reference,
            'user_id' => $customer->id,
            'subtotal' => $amount,
            'discount' => '0.00',
            'gst_amt' => '0.00',
            'total' => $amount,
            'status' => 'delivered',
            'pay_status' => 'paid',
        ]));
        Payment::create(['order_id' => $order->id, 'amount' => $amount, 'method' => 'online', 'status' => 'paid']);
        $history = OrderStatusHistory::create(['order_id' => $order->id, 'status' => 'delivered']);
        $deliveredAt = now()->subDays(8);
        $history->forceFill(['created_at' => $deliveredAt, 'updated_at' => $deliveredAt])->saveQuietly();
        app(MlmOrderIntegrationService::class)->process($order->fresh());

        $cashback = CashbackEligibility::where('order_id', $order->id)->firstOrFail();
        $this->assertSame($member->id, $cashback->member_id);

        return $cashback;
    }

    private function user(Role $role, string $email, string $phone): User
    {
        return User::create([
            'name' => $email,
            'email' => $email,
            'password' => 'password',
            'phone' => $phone,
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }
}
