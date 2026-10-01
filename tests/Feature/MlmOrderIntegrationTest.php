<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MlmCalculationRule;
use App\Models\MlmCalculationRun;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use App\Services\Mlm\MlmOrderIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MlmOrderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $customerRole = Role::create(['name' => 'Customer', 'guard_name' => 'web']);
        $permissions = collect(['mlm.view', 'mlm.manage'])
            ->map(fn (string $name) => Permission::create(['name' => $name, 'guard_name' => 'web']));
        $adminRole->permissions()->sync($permissions->pluck('id')->all());
        $this->admin = User::create([
            'name' => 'Integration Admin',
            'email' => 'order-mlm-admin@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        Role::whereKey($customerRole->id)->firstOrFail();
        MlmCalculationRule::query()->where('version', 'v1.0')->update(['effective_from' => now()->subDays(30)->toDateString()]);
    }

    public function test_verified_delivered_order_uses_net_amount_and_is_idempotent(): void
    {
        [$root, $customer] = $this->memberChain(2);
        $order = $this->order($customer, 'ORD-MLM-001', '1000.00', now()->subDays(8));
        Refund::create(['order_id' => $order->id, 'refund_num' => 'REF-MLM-001', 'amount' => '150.00', 'status' => 'approved']);

        $first = app(MlmOrderIntegrationService::class)->process($order);
        $second = app(MlmOrderIntegrationService::class)->process($order->fresh());

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second?->id);
        $this->assertSame('850.000000000000000000', (string) $first->eligible_amount);
        $this->assertSame(1, MlmCalculationRun::where('order_id', $order->id)->count());
        $this->assertSame(3, $first->incomeLedgers()->where('income_type', '!=', 'reversal')->count());
        $this->assertSame(3, $first->incomeLedgers()->where('income_type', 'reversal')->count());
        $this->assertSame(Order::MLM_COMPLETED, $order->fresh()->mlm_processing_status);
        $this->assertNotEmpty($first->placement_path_snapshot);
        $this->assertSame($customer->mlmMember->id, $first->purchasing_member_id);
    }

    public function test_delivered_paid_orders_process_immediately_without_a_return_period(): void
    {
        [, $customer] = $this->memberChain(0);
        $eligible = $this->order($customer, 'ORD-MLM-002', '500.00', now()->subMinutes(2));
        $tooEarly = $this->order($customer, 'ORD-MLM-003', '500.00', now());
        $service = app(MlmOrderIntegrationService::class);

        $service->process($eligible);
        $service->process($tooEarly);

        $this->assertSame(Order::MLM_COMPLETED, $eligible->fresh()->mlm_processing_status);
        $this->assertSame(Order::MLM_COMPLETED, $tooEarly->fresh()->mlm_processing_status);
    }

    public function test_unpaid_delivered_order_does_not_calculate(): void
    {
        [, $customer] = $this->memberChain(0);
        $order = $this->order($customer, 'ORD-MLM-UNPAID', '500.00', now());
        $order->update(['pay_status' => 'pending']);
        Payment::where('order_id', $order->id)->update(['status' => 'pending']);

        $this->assertNull(app(MlmOrderIntegrationService::class)->process($order->fresh()));
        $this->assertSame(Order::MLM_WAITING_PAYMENT, $order->fresh()->mlm_processing_status);
        $this->assertSame(0, MlmCalculationRun::where('order_id', $order->id)->count());
    }

    public function test_cancellation_creates_immutable_reversal_entries(): void
    {
        [, $customer] = $this->memberChain(1);
        $order = $this->order($customer, 'ORD-MLM-004', '1000.00', now()->subDays(8));
        $service = app(MlmOrderIntegrationService::class);
        $run = $service->process($order);
        $originalCount = $run->incomeLedgers()->where('income_type', '!=', 'reversal')->count();

        $order->update(['status' => 'cancelled']);
        $service->process($order->fresh());

        $this->assertSame(Order::MLM_REVERSED, $order->fresh()->mlm_processing_status);
        $this->assertSame('reversed', $run->fresh()->status);
        $this->assertSame($originalCount, $run->incomeLedgers()->where('income_type', 'reversal')->count());
        $this->assertSame($originalCount, $run->incomeLedgers()->where('income_type', '!=', 'reversal')->where('status', 'calculated')->count());
        $this->assertDatabaseHas('mlm_calculation_audits', ['calculation_run_id' => $run->id, 'event' => 'order_calculation_reversed']);
    }

    public function test_partial_refund_after_calculation_creates_reversal_without_recalculation(): void
    {
        [, $customer] = $this->memberChain(1);
        $order = $this->order($customer, 'ORD-MLM-004B', '1000.00', now()->subDays(8));
        $service = app(MlmOrderIntegrationService::class);
        $run = $service->process($order);
        $originals = $run->incomeLedgers()->where('income_type', '!=', 'reversal')->get()->keyBy('id');

        $firstRefund = Refund::create(['order_id' => $order->id, 'refund_num' => 'REF-MLM-004B-1', 'amount' => '100.00', 'status' => 'approved', 'processed_at' => now()]);
        $service->process($order->fresh());
        $secondRefund = Refund::create(['order_id' => $order->id, 'refund_num' => 'REF-MLM-004B-2', 'amount' => '100.00', 'status' => 'approved', 'processed_at' => now()]);
        $service->process($order->fresh());
        $service->process($order->fresh());

        $this->assertSame('calculated', $run->fresh()->status);
        $this->assertSame($originals->count(), $run->incomeLedgers()->where('income_type', '!=', 'reversal')->count());
        $this->assertSame($originals->count() * 2, $run->incomeLedgers()->where('income_type', 'reversal')->count());
        $this->assertSame('-0.090000000000000000', (string) $run->incomeLedgers()->where('income_type', 'reversal')->where('level', 0)->firstOrFail()->calculated_amount);
        $this->assertSame($originals->count(), $run->incomeLedgers()->where('income_type', 'reversal')->where('source_refund_id', $firstRefund->id)->count());
        $this->assertSame($originals->count(), $run->incomeLedgers()->where('income_type', 'reversal')->where('source_refund_id', $secondRefund->id)->count());
    }

    public function test_full_refund_reverses_complete_calculation_without_editing_originals(): void
    {
        [, $customer] = $this->memberChain(1);
        $order = $this->order($customer, 'ORD-MLM-FULL', '1000.00', now());
        $service = app(MlmOrderIntegrationService::class);
        $run = $service->process($order);
        $originals = $run->incomeLedgers()->where('income_type', '!=', 'reversal')->get()->keyBy('id');
        Refund::create(['order_id' => $order->id, 'refund_num' => 'REF-MLM-FULL', 'amount' => '1000.00', 'status' => 'approved', 'processed_at' => now()]);

        $service->process($order->fresh());

        $this->assertSame('reversed', $run->fresh()->status);
        foreach ($originals as $original) {
            $this->assertSame('calculated', $original->fresh()->status);
            $reversal = $run->incomeLedgers()->where('reversal_of_id', $original->id)->where('source_refund_id', '!=', null)->firstOrFail();
            $this->assertSame(
                (string) $original->calculated_amount,
                (string) str_replace('-', '', (string) $reversal->calculated_amount)
            );
        }
    }

    public function test_failed_calculation_can_be_retried_without_duplicate_run(): void
    {
        [, $member] = $this->memberChain(20);
        $order = $this->order($member, 'ORD-MLM-005', '1000.00', now()->subDays(8));

        $service = app(MlmOrderIntegrationService::class);
        $failed = $service->process($order);

        $this->assertSame('failed', $failed?->status);
        $memberRecord = $member->mlmMember;
        $memberRecord->update(['placement_parent_id' => null, 'placement_position' => null]);
        $retried = $service->retry($order->fresh());

        $this->assertSame($failed?->id, $retried?->id);
        $this->assertSame(Order::MLM_COMPLETED, $order->fresh()->mlm_processing_status, $order->fresh()->mlm_error_message.' / '.($retried?->error_message ?? 'no run'));
        $this->assertSame(1, MlmCalculationRun::where('order_id', $order->id)->count());
    }

    public function test_reconciliation_is_permission_protected(): void
    {
        $customerRole = Role::where('name', 'Customer')->firstOrFail();
        $customer = User::create([
            'name' => 'Unauthorized Customer',
            'email' => 'order-mlm-customer@example.com',
            'password' => 'password',
            'role_id' => $customerRole->id,
            'status' => 'active',
        ]);

        $this->actingAs($customer)->get(route('admin.mlm.reconciliation.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.mlm.reconciliation.index'))->assertOk();
    }

    private function memberChain(int $depth): array
    {
        $role = Role::where('name', 'Customer')->firstOrFail();
        $rootUser = $this->customer($role, 'MLM-ORDER-ROOT', '9700000000');
        $root = Member::create(['user_id' => $rootUser->id, 'status' => Member::STATUS_ACTIVE, 'joined_at' => now()]);
        $current = $root;

        for ($level = 1; $level <= $depth; $level++) {
            $user = $this->customer($role, 'MLM-ORDER-'.$level, '97000000'.$level);
            $current = Member::create([
                'user_id' => $user->id,
                'placement_parent_id' => $current->id,
                'placement_position' => Member::POSITION_LEFT,
                'status' => Member::STATUS_ACTIVE,
                'joined_at' => now(),
            ]);
        }

        return [$root, $current->user];
    }

    private function customer(Role $role, string $memberId, string $phone): User
    {
        return User::create([
            'name' => $memberId,
            'email' => strtolower($memberId).'@example.com',
            'password' => 'password',
            'phone' => $phone,
            'role_id' => $role->id,
            'status' => 'active',
            'mlm_member_id' => $memberId,
        ]);
    }

    private function order(User $customer, string $orderNumber, string $subtotal, $deliveredAt): Order
    {
        $order = Order::withoutEvents(fn () => Order::create([
            'order_num' => $orderNumber,
            'user_id' => $customer->id,
            'subtotal' => $subtotal,
            'discount' => '0.00',
            'total' => $subtotal,
            'status' => 'delivered',
            'pay_status' => 'paid',
        ]));
        Payment::create(['order_id' => $order->id, 'amount' => $subtotal, 'method' => 'online', 'status' => 'paid']);
        $history = OrderStatusHistory::create(['order_id' => $order->id, 'status' => 'delivered']);
        $history->forceFill(['created_at' => $deliveredAt, 'updated_at' => $deliveredAt])->saveQuietly();

        return $order->fresh();
    }
}
