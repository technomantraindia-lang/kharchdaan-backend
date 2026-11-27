<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MlmCalculationRule;
use App\Models\MlmIncomeLedger;
use App\Models\MlmPayoutCycle;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Mlm\MlmCalculationService;
use App\Services\Mlm\MlmPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class MlmPayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private MlmCalculationRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $customerRole = Role::create(['name' => 'Customer', 'guard_name' => 'web']);
        $permissions = collect(['mlm.view', 'mlm.manage'])
            ->map(fn (string $name) => Permission::create(['name' => $name, 'guard_name' => 'web']));
        $adminRole->permissions()->sync($permissions->pluck('id')->all());

        $this->admin = User::create([
            'name' => 'Payout Admin',
            'email' => 'payout-admin@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        Role::whereKey($customerRole->id)->firstOrFail();
        $this->rule = MlmCalculationRule::where('version', 'v1.0')->firstOrFail();
    }

    public function test_duplicate_calculation_and_weekly_cycle_are_idempotent(): void
    {
        [, $purchasing] = $this->memberChain();
        $date = now()->subMinute();
        $service = app(MlmPayoutService::class);
        $emptyCycle = $service->createWeeklyCycle($date, $this->admin);
        $this->assertSame(MlmPayoutCycle::STATUS_PENDING_CALCULATION, $emptyCycle->status);

        $calculationService = app(MlmCalculationService::class);
        $firstRun = $calculationService->calculate($purchasing, '1000', 'PAYOUT-DUP-001', $date, $this->rule, $this->admin);
        $secondRun = $calculationService->calculate($purchasing, '1000', 'PAYOUT-DUP-001', $date, $this->rule, $this->admin);

        $this->assertSame($firstRun->id, $secondRun->id);

        $firstCycle = $service->createWeeklyCycle($date, $this->admin);
        $secondCycle = $service->createWeeklyCycle($date, $this->admin);

        $this->assertSame($firstCycle->id, $secondCycle->id);
        $this->assertDatabaseCount('mlm_payout_cycles', 1);
        $this->assertSame(MlmPayoutCycle::STATUS_PENDING_APPROVAL, $firstCycle->status);
        $this->assertSame(2, $firstCycle->ledger_count);
    }

    /**
     * Client rule (Point 38): idle position income is not paid when its appreciation assignment has expired.
     */
    public function test_idle_position_income_is_not_paid_when_appreciation_expired(): void
    {
        [, $purchasing] = $this->memberChain();
        $parent = $purchasing->placementParent;
        $this->fundPaidOrder($parent, '1000');
        $parent->update([
            'is_idle_slot' => true,
            'idle_slot_label' => 'Idle - Test',
            'appreciation_member_id' => $purchasing->id,
            'appreciation_expires_at' => now()->subDay(),
        ]);

        $date = now()->subMinute();
        app(MlmCalculationService::class)->calculate($purchasing, '1000', 'IDLE-PAY-EXPIRED', $date, $this->rule, $this->admin);

        $cycle = app(MlmPayoutService::class)->createWeeklyCycle($date, $this->admin);

        // Only the purchaser's own-purchase ledger is paid; the idle position's ledger stays unpaid.
        $this->assertSame(1, $cycle->ledger_count);
        $this->assertDatabaseHas('mlm_income_ledgers', [
            'member_id' => $parent->id,
            'payout_cycle_id' => null,
            'status' => MlmIncomeLedger::STATUS_CALCULATED,
        ]);
    }

    public function test_idle_position_income_routes_to_valid_appreciation_member(): void
    {
        [, $purchasing] = $this->memberChain();
        $parent = $purchasing->placementParent;
        $this->fundPaidOrder($parent, '1000');
        $parent->update([
            'is_idle_slot' => true,
            'idle_slot_label' => 'Idle - Test',
            'appreciation_member_id' => $purchasing->id,
            'appreciation_expires_at' => now()->addMonth(),
        ]);

        $date = now()->subMinute();
        app(MlmCalculationService::class)->calculate($purchasing, '1000', 'IDLE-PAY-VALID', $date, $this->rule, $this->admin);

        $cycle = app(MlmPayoutService::class)->createWeeklyCycle($date, $this->admin);

        // Own-purchase ledger plus the redirected idle-position ledger.
        $this->assertSame(2, $cycle->ledger_count);
    }

    private function fundPaidOrder(Member $member, string $amount): void
    {
        Order::create([
            'order_num' => 'ORD-PAYIDLE-'.$member->id,
            'user_id' => $member->user_id,
            'subtotal' => $amount,
            'total' => $amount,
            'status' => 'delivered',
            'pay_status' => 'paid',
        ]);
    }

    public function test_weekly_cycle_can_be_paid_and_failed_then_reprocessed(): void
    {
        [, $purchasing] = $this->memberChain();
        $date = now()->subMinute();
        app(MlmCalculationService::class)->calculate($purchasing, '1000', 'PAYOUT-STATUS-001', $date, $this->rule, $this->admin);
        $service = app(MlmPayoutService::class);
        $cycle = $service->createWeeklyCycle($date, $this->admin);

        $cycle = $service->approve($cycle, $this->admin);
        $this->assertSame(MlmPayoutCycle::STATUS_APPROVED, $cycle->status);
        $cycle = $service->startProcessing($cycle, $this->admin);
        $cycle = $service->markFailed($cycle, $this->admin, 'Bank file rejected');
        $this->assertSame(MlmPayoutCycle::STATUS_FAILED, $cycle->status);
        $this->assertSame(MlmIncomeLedger::STATUS_FAILED, MlmIncomeLedger::firstOrFail()->status);

        $cycle = $service->startProcessing($cycle, $this->admin);
        $cycle = $service->markPaid($cycle, $this->admin, 'UTR-001', now(), 'Paid manually');

        $this->assertSame(MlmPayoutCycle::STATUS_PAID, $cycle->status);
        $this->assertSame('UTR-001', $cycle->payment_reference);
        $this->assertSame(MlmIncomeLedger::STATUS_PAID, MlmIncomeLedger::firstOrFail()->status);
    }

    public function test_ledger_totals_and_reversal_entry_are_exact_and_linked(): void
    {
        [, $purchasing] = $this->memberChain();
        $date = now()->subMinute();
        app(MlmCalculationService::class)->calculate($purchasing, '1000', 'PAYOUT-REVERSAL-001', $date, $this->rule, $this->admin);
        $service = app(MlmPayoutService::class);
        $cycle = $service->createWeeklyCycle($date, $this->admin);
        $service->approve($cycle, $this->admin);
        $cycle = $service->startProcessing($cycle, $this->admin);
        $service->markPaid($cycle, $this->admin, 'UTR-002', now());

        $original = MlmIncomeLedger::where('income_type', MlmIncomeLedger::TYPE_LEVEL)->firstOrFail();
        $reversal = $service->reverse($original, $this->admin);

        $this->assertSame(MlmIncomeLedger::TYPE_REVERSAL, $reversal->income_type);
        $this->assertSame($original->id, $reversal->reversal_of_id);
        $this->assertSame('-0.900000000000000000', (string) $reversal->calculated_amount);
        $this->assertSame(MlmIncomeLedger::STATUS_REVERSED, $original->fresh()->status);
        $this->assertSame('1.800000000000000000', (string) $cycle->fresh()->total_amount);
    }

    public function test_historical_ledger_values_are_immutable(): void
    {
        [, $purchasing] = $this->memberChain();
        app(MlmCalculationService::class)->calculate($purchasing, '1000', 'PAYOUT-IMMUTABLE-001', now()->subMinute(), $this->rule, $this->admin);
        $ledger = MlmIncomeLedger::firstOrFail();

        $this->expectException(LogicException::class);
        $ledger->update(['calculated_amount' => '99.000000000000000000']);
    }

    public function test_payout_routes_are_permission_restricted(): void
    {
        $customerRole = Role::where('name', 'Customer')->firstOrFail();
        $customer = User::create([
            'name' => 'Payout Customer',
            'email' => 'payout-customer@example.com',
            'password' => 'password',
            'role_id' => $customerRole->id,
            'status' => 'active',
        ]);

        $this->actingAs($customer)
            ->get(admin_route('mlm.payouts.index'))
            ->assertForbidden();
    }

    public function test_manual_adjustment_is_idempotent_and_uses_adjustment_type(): void
    {
        [$member] = $this->memberChain();
        $service = app(MlmPayoutService::class);
        $first = $service->createAdjustment(
            $member,
            '0',
            '0',
            '0.20',
            '1.250000000000000000',
            0,
            'ADJUSTMENT-001',
            now(),
            $this->rule,
            $this->admin
        );
        $second = $service->createAdjustment(
            $member,
            '0',
            '0',
            '0.20',
            '1.250000000000000000',
            0,
            'ADJUSTMENT-001',
            now(),
            $this->rule,
            $this->admin
        );

        $this->assertSame($first->id, $second->id);
        $this->assertSame(MlmIncomeLedger::TYPE_ADJUSTMENT, $first->income_type);
        $this->assertDatabaseCount('mlm_income_ledgers', 1);
    }

    private function memberChain(): array
    {
        $role = Role::where('name', 'Customer')->firstOrFail();
        $rootUser = User::create([
            'name' => 'Payout Root',
            'email' => 'payout-root-'.uniqid().'@example.com',
            'password' => 'password',
            'phone' => '9600000001'.random_int(0, 8),
            'role_id' => $role->id,
            'status' => 'active',
            'mlm_member_id' => 'PAYOUT-ROOT-'.uniqid(),
        ]);
        $root = Member::create(['user_id' => $rootUser->id, 'status' => Member::STATUS_ACTIVE, 'joined_at' => now()]);
        $this->seedDsEligibilityOrder($rootUser, 'ROOT-'.uniqid());
        $purchasingUser = User::create([
            'name' => 'Payout Purchasing',
            'email' => 'payout-purchasing-'.uniqid().'@example.com',
            'password' => 'password',
            'phone' => '960000001'.random_int(10, 99),
            'role_id' => $role->id,
            'status' => 'active',
            'mlm_member_id' => 'PAYOUT-PURCHASING-'.uniqid(),
        ]);
        $purchasing = Member::create([
            'user_id' => $purchasingUser->id,
            'placement_parent_id' => $root->id,
            'placement_position' => Member::POSITION_LEFT,
            'status' => Member::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
        $this->seedDsEligibilityOrder($purchasingUser, 'BUY-'.uniqid());

        return [$root, $purchasing];
    }

    private function seedDsEligibilityOrder(User $user, string $suffix): void
    {
        Order::withoutEvents(fn () => Order::create([
            'order_num' => 'T-DS-'.$suffix,
            'user_id' => $user->id,
            'subtotal' => 1000,
            'discount' => 0,
            'ship_charge' => 0,
            'total' => 1000,
            'status' => 'delivered',
            'pay_status' => 'paid',
            'currency' => 'INR',
            'ship_addr' => 'Ahmedabad',
            'bill_addr' => 'Ahmedabad',
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }
}
