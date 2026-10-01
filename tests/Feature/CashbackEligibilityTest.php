<?php

namespace Tests\Feature;

use App\Jobs\ProcessMlmEligibleOrder;
use App\Models\CashbackEligibility;
use App\Models\Member;
use App\Models\MlmCalculationRule;
use App\Models\MlmCalculationRun;
use App\Models\MlmIncomeLedger;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use App\Services\Cashback\CashbackEligibilityService;
use App\Services\Cashback\CashbackRefundReconciliationService;
use App\Services\Mlm\MlmOrderIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class CashbackEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private Role $customerRole;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $this->customerRole = Role::create(['name' => 'Customer', 'guard_name' => 'web']);
        MlmCalculationRule::query()->where('version', 'v1.0')->update([
            'effective_from' => now()->subDays(30)->toDateString(),
        ]);
    }

    public function test_199_99_is_not_eligible(): void
    {
        [$order, $run] = $this->completedOrder('199.99', 'CB-199-99');
        $cashback = CashbackEligibility::where('order_id', $order->id)->firstOrFail();

        $this->assertSame(CashbackEligibility::STATUS_NOT_ELIGIBLE, $cashback->status);
        $this->assertSame('199.990000000000000000', (string) $cashback->final_eligible_amount);
        $this->assertSame('0.000000000000000000', (string) $cashback->maximum_cashback_amount);
        $this->assertStringContainsString('below', strtolower((string) $cashback->ineligibility_reason));
        $this->assertSame($run->id, $cashback->calculation_run_id);
    }

    public function test_exactly_200_is_eligible(): void
    {
        [$order] = $this->completedOrder('200.00', 'CB-200');
        $cashback = CashbackEligibility::where('order_id', $order->id)->firstOrFail();

        $this->assertSame(CashbackEligibility::STATUS_ELIGIBLE_AWAITING_COMPANY_PROFIT, $cashback->status);
        $this->assertSame('200.000000000000000000', (string) $cashback->maximum_cashback_amount);
    }

    public function test_500_is_eligible_for_a_maximum_500_cashback(): void
    {
        [$order] = $this->completedOrder('500.00', 'CB-500');
        $cashback = CashbackEligibility::where('order_id', $order->id)->firstOrFail();

        $this->assertSame('500.000000000000000000', (string) $cashback->final_eligible_amount);
        $this->assertSame('500.000000000000000000', (string) $cashback->maximum_cashback_amount);
        $this->assertSame(CashbackEligibility::STATUS_ELIGIBLE_AWAITING_COMPANY_PROFIT, $cashback->status);
    }

    public function test_duplicate_order_event_and_repeated_job_create_one_cashback_record(): void
    {
        [$order, $run] = $this->completedOrder('500.00', 'CB-DUPLICATE');
        $job = new ProcessMlmEligibleOrder($order->id);
        $job->handle(app(MlmOrderIntegrationService::class));
        $job->handle(app(MlmOrderIntegrationService::class));

        $this->assertSame(1, CashbackEligibility::where('order_id', $order->id)->count());
        $this->assertSame(1, CashbackEligibility::where('idempotency_key', hash('sha256', 'cashback|order:'.$order->id.'|calculation:'.$run->id))->count());
    }

    public function test_gst_is_ignored_and_cashback_does_not_add_pv_or_income(): void
    {
        [$order, $run] = $this->completedOrder('500.00', 'CB-GST', '100.00');
        $ledgerCount = MlmIncomeLedger::where('calculation_run_id', $run->id)->count();
        $runCount = MlmCalculationRun::count();

        $cashback = CashbackEligibility::where('order_id', $order->id)->firstOrFail();
        app(CashbackEligibilityService::class)->createEligibility($order, $run);

        $this->assertSame('500.000000000000000000', (string) $cashback->final_eligible_amount);
        $this->assertSame($ledgerCount, MlmIncomeLedger::where('calculation_run_id', $run->id)->count());
        $this->assertSame($runCount, MlmCalculationRun::count());
    }

    public function test_cancelled_order_is_not_payable_and_creates_immutable_refund_adjustment(): void
    {
        [$order] = $this->completedOrder('500.00', 'CB-REFUND');
        $order->update(['status' => 'cancelled']);
        app(MlmOrderIntegrationService::class)->process($order->fresh());
        $cashback = CashbackEligibility::where('order_id', $order->id)->firstOrFail();

        $this->assertSame(CashbackEligibility::STATUS_CANCELLED_DUE_TO_REFUND, $cashback->status);
        $this->assertNotSame(CashbackEligibility::STATUS_PAID, $cashback->status);
        $adjustment = $cashback->adjustments()->firstOrFail();
        $this->assertSame('-500.000000000000000000', (string) $adjustment->amount);

        $this->expectException(LogicException::class);
        $adjustment->update(['reason' => 'must remain immutable']);
    }

    public function test_partial_refund_reduces_unpaid_cashback_and_cancels_below_threshold(): void
    {
        [$order] = $this->completedOrder('500.00', 'CB-REFUND-PARTIAL');
        $refund = Refund::create([
            'order_id' => $order->id,
            'refund_num' => 'REF-CB-PARTIAL',
            'amount' => '301.00',
            'status' => 'approved',
            'processed_at' => now(),
        ]);

        $cashback = app(CashbackRefundReconciliationService::class)->processApprovedRefund($refund);

        $this->assertSame(CashbackEligibility::STATUS_CANCELLED_DUE_TO_REFUND, $cashback?->status);
        $this->assertSame('199.000000000000000000', (string) $cashback?->current_eligible_amount);
        $this->assertSame('0.000000000000000000', (string) $cashback?->maximum_cashback_amount);
        $this->assertSame(1, $cashback?->adjustments()->count());
    }

    public function test_paid_cashback_refund_creates_immutable_recovery_snapshot(): void
    {
        [$order] = $this->completedOrder('500.00', 'CB-REFUND-PAID');
        $cashback = CashbackEligibility::where('order_id', $order->id)->firstOrFail();
        $cashback->update(['status' => CashbackEligibility::STATUS_PAID]);
        $originalAmount = (string) $cashback->fresh()->maximum_cashback_amount;
        $refund = Refund::create([
            'order_id' => $order->id,
            'refund_num' => 'REF-CB-PAID',
            'amount' => '100.00',
            'status' => 'approved',
            'processed_at' => now(),
        ]);

        $service = app(CashbackRefundReconciliationService::class);
        $service->processApprovedRefund($refund);
        $service->processApprovedRefund($refund);
        $fresh = $cashback->fresh();

        $this->assertSame(CashbackEligibility::STATUS_PAID, $fresh->status);
        $this->assertSame($originalAmount, (string) $fresh->maximum_cashback_amount);
        $this->assertSame(1, $fresh->adjustments()->count());
        $adjustment = $fresh->adjustments()->firstOrFail();
        $this->assertSame('-100.000000000000000000', (string) $adjustment->amount);
        $this->assertSame('500.000000000000000000', $adjustment->adjustment_snapshot['original_cashback_amount']);
        $this->assertSame('100.000000000000000000', $adjustment->adjustment_snapshot['recovered_amount']);
        $this->assertSame('400.000000000000000000', $adjustment->adjustment_snapshot['remaining_recovery_amount']);
    }

    private function completedOrder(string $amount, string $reference, string $gst = '0.00'): array
    {
        $user = User::create([
            'name' => $reference,
            'email' => strtolower($reference).'@example.com',
            'password' => 'password',
            'phone' => '980'.str_pad((string) User::count(), 7, '0', STR_PAD_LEFT),
            'role_id' => $this->customerRole->id,
            'status' => 'active',
            'mlm_member_id' => $reference,
        ]);
        $member = Member::create(['user_id' => $user->id, 'status' => Member::STATUS_ACTIVE, 'joined_at' => now()]);
        $order = Order::withoutEvents(fn () => Order::create([
            'order_num' => $reference,
            'user_id' => $user->id,
            'subtotal' => $amount,
            'discount' => '0.00',
            'gst_amt' => $gst,
            'total' => bcadd($amount, $gst, 2),
            'status' => 'delivered',
            'pay_status' => 'paid',
        ]));
        Payment::create(['order_id' => $order->id, 'amount' => $order->total, 'method' => 'online', 'status' => 'paid']);
        $history = OrderStatusHistory::create(['order_id' => $order->id, 'status' => 'delivered']);
        $deliveredAt = now()->subDays(8);
        $history->forceFill(['created_at' => $deliveredAt, 'updated_at' => $deliveredAt])->saveQuietly();

        $order = $order->fresh();
        $run = app(MlmOrderIntegrationService::class)->process($order);

        $this->assertNotNull($run);
        $this->assertSame($member->id, $run->purchasing_member_id);

        return [$order, $run];
    }
}
