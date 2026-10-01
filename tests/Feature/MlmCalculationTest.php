<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MlmCalculationRule;
use App\Models\MlmIncomeLedger;
use App\Models\Permission;
use App\Models\PlacementMovement;
use App\Models\Role;
use App\Models\User;
use App\Services\Mlm\MlmCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MlmCalculationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private MlmCalculationRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'Customer', 'guard_name' => 'web']);
        $permissions = collect(['mlm.view', 'mlm.manage'])
            ->map(fn (string $name) => Permission::create(['name' => $name, 'guard_name' => 'web']));
        $adminRole->permissions()->sync($permissions->pluck('id')->all());
        $this->admin = User::create([
            'name' => 'Calculation Admin',
            'email' => 'calculation-admin@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        $this->rule = MlmCalculationRule::where('version', 'v1.0')->firstOrFail();
    }

    public function test_full_level_chain_uses_exact_high_and_low_formulas(): void
    {
        [$root, $purchasing] = $this->chain(19);
        $service = app(MlmCalculationService::class);

        $preview = $service->preview($purchasing, '1000.00', 'MANUAL-1000', now(), $this->rule);
        $lines = collect($preview['lines'])->keyBy('level');

        $this->assertCount(20, $preview['lines']);
        $this->assertSame('4.500000000000000000', $lines[0]['pv']);
        $this->assertSame('0.900000000000000000', $lines[0]['calculated_amount']);
        $this->assertSame('0.250000000000000000', $lines[8]['pv']);
        $this->assertSame('0.050000000000000000', $lines[8]['calculated_amount']);
        $this->assertSame('7.800000000000000000', $preview['total_income']);
        $this->assertSame($root->customer_id, $preview['placement_path'][0]['customer_id']);
    }

    public function test_requested_amounts_keep_decimal_formula_outputs(): void
    {
        [, $purchasing] = $this->chain(8);
        $service = app(MlmCalculationService::class);

        foreach (['200', '500', '1000', '2000', '3000', '5000'] as $amount) {
            $preview = $service->preview($purchasing, $amount, 'AMOUNT-'.$amount, now(), $this->rule);
            $lines = collect($preview['lines'])->keyBy('level');
            $this->assertSame(MlmCalculationService::class, get_class($service));
            $this->assertSame(
                bcmul($amount, '13.5', 18) === '0.000000000000000000' ? '0' : bcdiv(bcmul($amount, '13.5', 18), '3000', 18),
                $lines[0]['pv']
            );
            $this->assertSame(
                bcdiv(bcmul($amount, '0.75', 18), '3000', 18),
                $lines[8]['pv'] ?? null
            );
        }
    }

    public function test_calculation_persists_ledger_audit_and_is_idempotent(): void
    {
        [, $purchasing] = $this->chain(3);
        $service = app(MlmCalculationService::class);
        $date = now()->subMinute();

        $first = $service->calculate($purchasing, '1000', 'IDEMPOTENT-001', $date, $this->rule, $this->admin);
        $second = $service->calculate($purchasing, '1000', 'IDEMPOTENT-001', $date, $this->rule, $this->admin);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('mlm_calculation_runs', 1);
        $this->assertSame(4, MlmIncomeLedger::where('calculation_run_id', $first->id)->count());
        $this->assertDatabaseHas('mlm_calculation_audits', [
            'calculation_run_id' => $first->id,
            'event' => 'calculated',
        ]);
        $this->assertSame($this->rule->id, MlmIncomeLedger::firstOrFail()->rule_version_id);
    }

    public function test_manual_admin_screen_previews_and_saves_without_order_integration(): void
    {
        [, $purchasing] = $this->chain(1);
        $data = [
            'purchasing_member_id' => $purchasing->id,
            'eligible_amount' => '1000.00',
            'transaction_reference' => 'MANUAL-SCREEN-001',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'rule_version_id' => $this->rule->id,
        ];

        $this->actingAs($this->admin)
            ->get(route('admin.mlm.calculations.index'))
            ->assertOk()
            ->assertSee('Manual test transaction');

        $this->actingAs($this->admin)
            ->post(route('admin.mlm.calculations.preview'), $data)
            ->assertOk()
            ->assertSee('4.5000');

        $this->actingAs($this->admin)
            ->post(route('admin.mlm.calculations.store'), $data + ['confirmed' => 1])
            ->assertRedirect(route('admin.mlm.calculations.index'));
    }

    public function test_historical_transaction_uses_old_placement_path_snapshot(): void
    {
        $oldRoot = $this->member('CALC-OLD-ROOT', 'Old Root', '9500000010');
        $newRoot = $this->member('CALC-NEW-ROOT', 'New Root', '9500000011');
        $purchasing = $this->member('CALC-PURCHASING', 'Purchasing', '9500000012', null, $oldRoot, Member::POSITION_LEFT);
        $effectiveAt = now()->subHour();
        $purchasing->update([
            'placement_parent_id' => $newRoot->id,
            'placement_position' => Member::POSITION_RIGHT,
        ]);
        PlacementMovement::create([
            'member_id' => $purchasing->id,
            'old_parent_id' => $oldRoot->id,
            'new_parent_id' => $newRoot->id,
            'old_position' => Member::POSITION_LEFT,
            'new_position' => Member::POSITION_RIGHT,
            'reason' => 'Historical calculation fixture',
            'moved_by' => $this->admin->id,
            'effective_at' => $effectiveAt,
            'approved_at' => $effectiveAt,
            'status' => 'approved',
            'old_path_snapshot' => [
                ['id' => $oldRoot->id, 'customer_id' => $oldRoot->customer_id, 'name' => 'Old Root', 'level' => 0],
                ['id' => $purchasing->id, 'customer_id' => $purchasing->customer_id, 'name' => 'Purchasing', 'level' => 1],
            ],
            'new_path_snapshot' => [
                ['id' => $newRoot->id, 'customer_id' => $newRoot->customer_id, 'name' => 'New Root', 'level' => 0],
                ['id' => $purchasing->id, 'customer_id' => $purchasing->customer_id, 'name' => 'Purchasing', 'level' => 1],
            ],
            'old_depth' => 1,
            'new_depth' => 1,
        ]);

        $preview = app(MlmCalculationService::class)->preview(
            $purchasing,
            '1000',
            'HISTORICAL-001',
            $effectiveAt->copy()->subMinute(),
            $this->rule
        );

        $this->assertSame($oldRoot->customer_id, $preview['placement_path'][0]['customer_id']);
        $this->assertSame($oldRoot->id, $preview['lines'][1]['member_id']);
    }

    public function test_sponsor_tree_does_not_determine_level_income(): void
    {
        $sponsor = $this->member('CALC-SPONSOR', 'Sponsor', '9500000020');
        $placementParent = $this->member('CALC-PLACEMENT', 'Placement Parent', '9500000021');
        $purchasing = $this->member('CALC-PURCHASE', 'Purchase', '9500000022', $sponsor, $placementParent, Member::POSITION_MIDDLE);

        $preview = app(MlmCalculationService::class)->preview($purchasing, '1000', 'SEPARATE-TREES-001', now(), $this->rule);

        $this->assertSame($placementParent->id, $preview['lines'][1]['member_id']);
        $this->assertNotSame($sponsor->id, $preview['lines'][1]['member_id']);
    }

    private function chain(int $depth): array
    {
        $root = $this->member('CALC-ROOT-0', 'Calculation Root', '9500000000');
        $current = $root;
        for ($level = 1; $level <= $depth; $level++) {
            $current = $this->member(
                'CALC-CHAIN-'.$level,
                'Chain '.$level,
                '950000'.str_pad((string) $level, 4, '0', STR_PAD_LEFT),
                null,
                $current,
                Member::POSITION_LEFT
            );
        }

        return [$root, $current];
    }

    private function member(
        string $memberId,
        string $name,
        string $phone,
        ?Member $sponsor = null,
        ?Member $parent = null,
        ?string $position = null
    ): Member {
        $role = Role::where('name', 'Customer')->firstOrFail();
        $user = User::create([
            'name' => $name,
            'email' => strtolower($memberId).'@example.com',
            'password' => 'password',
            'phone' => $phone,
            'role_id' => $role->id,
            'status' => 'active',
            'mlm_member_id' => $memberId,
        ]);

        return Member::create([
            'user_id' => $user->id,
            'sponsor_member_id' => $sponsor?->id,
            'placement_parent_id' => $parent?->id,
            'placement_position' => $position,
            'status' => Member::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
    }
}
