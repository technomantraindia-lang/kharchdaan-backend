<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MlmTreeTemporaryPromotionTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Role $customerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Super Administrator', 'guard_name' => 'web']);
        $this->customerRole = Role::create(['name' => 'Customer', 'guard_name' => 'web']);
        $viewPermission = Permission::create(['name' => 'mlm.view', 'guard_name' => 'web']);
        $managePermission = Permission::create(['name' => 'mlm.manage', 'guard_name' => 'web']);
        $adminRole->permissions()->sync([$viewPermission->id, $managePermission->id]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin Master',
            'email' => 'superadmin@example.com',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_assign_temporary_promotion_with_days(): void
    {
        Carbon::setTestNow('2026-10-23 10:00:00');

        $root = $this->createMember('MEM-ROOT', 'Root Member', '9000000001');
        $idleSlot = $this->createMember('MEM-IDLE-1', 'Idle 1 (Appreciation Slot)', '9000000002', $root, $root, Member::POSITION_MIDDLE);
        $idleSlot->update(['is_idle_slot' => true, 'idle_slot_label' => 'Idle - 1']);
        $candidate = $this->createMember('MEM-CANDIDATE', 'Ramesh Patel', '9000000003', $root, $root, Member::POSITION_LEFT);

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('super-admin.mlm.tree.assign-appreciation', $idleSlot), [
                'beneficiary' => $candidate->id,
                'duration_type' => 'days',
                'duration_days' => 15,
                'note' => 'Top performer reward for Diwali campaign',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $idleSlot->refresh();
        $this->assertEquals($candidate->id, $idleSlot->appreciation_member_id);
        $this->assertNotNull($idleSlot->appreciation_assigned_at);
        $this->assertNotNull($idleSlot->appreciation_expires_at);
        $this->assertEquals('2026-11-07 23:59:59', $idleSlot->appreciation_expires_at->toDateTimeString());
        $this->assertEquals('Top performer reward for Diwali campaign', $idleSlot->appreciation_note);
        $this->assertTrue($idleSlot->isAppreciationActive());
        $this->assertFalse($idleSlot->isAppreciationExpired());
    }

    public function test_dual_placement_is_reflected_in_tree_children_payload(): void
    {
        Carbon::setTestNow('2026-10-23 10:00:00');

        $root = $this->createMember('MEM-ROOT', 'Root Member', '9000000001');
        $idleSlot = $this->createMember('MEM-IDLE-1', 'Idle 1 (Appreciation Slot)', '9000000002', $root, $root, Member::POSITION_MIDDLE);
        $idleSlot->update(['is_idle_slot' => true, 'idle_slot_label' => 'Idle - 1']);
        $candidate = $this->createMember('MEM-CANDIDATE', 'Ramesh Patel', '9000000003', $root, $root, Member::POSITION_LEFT);

        // Assign candidate to idle slot for 30 days
        $idleSlot->update([
            'appreciation_member_id' => $candidate->id,
            'appreciation_assigned_at' => now(),
            'appreciation_expires_at' => now()->addDays(30)->endOfDay(),
            'appreciation_note' => 'Q4 Performance Promotion',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('super-admin.mlm.tree.children', $root));

        $response->assertOk();
        $data = $response->json();

        // 1. Check Candidate at Left Position (Original Location)
        $leftSlot = collect($data['slots'])->firstWhere('position', Member::POSITION_LEFT);
        $this->assertNotNull($leftSlot);
        $this->assertEquals($candidate->customer_id, $leftSlot['member']['customer_id']);
        $this->assertTrue($leftSlot['member']['is_dual_placed']);
        $this->assertCount(1, $leftSlot['member']['promotions_held']);
        $this->assertEquals($idleSlot->id, $leftSlot['member']['promotions_held'][0]['slot_id']);
        $this->assertEquals('Idle - 1', $leftSlot['member']['promotions_held'][0]['slot_label']);
        $this->assertEquals(31, $leftSlot['member']['promotions_held'][0]['days_remaining']);

        // 2. Check Promoted Member at Middle Position (Idle Location)
        $middleSlot = collect($data['slots'])->firstWhere('position', Member::POSITION_MIDDLE);
        $this->assertNotNull($middleSlot);
        $this->assertTrue($middleSlot['member']['is_promoted_acting']);
        $this->assertEquals($candidate->user->name, $middleSlot['member']['name']);
        $this->assertEquals($candidate->customer_id, $middleSlot['member']['customer_id']);
        $this->assertNotNull($middleSlot['member']['promoted_info']);
        $this->assertEquals($candidate->id, $middleSlot['member']['promoted_info']['id']);
        $this->assertEquals('Q4 Performance Promotion', $middleSlot['member']['promoted_info']['note']);
        $this->assertEquals(31, $middleSlot['member']['promoted_info']['days_remaining']);
    }

    public function test_indefinite_promotion_has_no_expiry_and_remains_active(): void
    {
        $root = $this->createMember('MEM-ROOT', 'Root Member', '9000000001');
        $idleSlot = $this->createMember('MEM-IDLE-2', 'Idle 2 (Appreciation Slot)', '9000000002', $root, $root, Member::POSITION_RIGHT);
        $idleSlot->update(['is_idle_slot' => true, 'idle_slot_label' => 'Idle - 2']);
        $candidate = $this->createMember('MEM-CANDIDATE-2', 'Suresh Kumar', '9000000003', $root, $root, Member::POSITION_LEFT);

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('super-admin.mlm.tree.assign-appreciation', $idleSlot), [
                'beneficiary' => $candidate->id,
                'duration_type' => 'indefinite',
                'note' => 'Permanent champion assignment',
            ]);

        $response->assertOk();

        $idleSlot->refresh();
        $this->assertNull($idleSlot->appreciation_expires_at);
        $this->assertTrue($idleSlot->isAppreciationActive());
        $this->assertFalse($idleSlot->isAppreciationExpired());
    }

    public function test_expired_promotion_is_marked_expired_and_can_be_unassigned(): void
    {
        Carbon::setTestNow('2026-10-23 10:00:00');

        $root = $this->createMember('MEM-ROOT', 'Root Member', '9000000001');
        $idleSlot = $this->createMember('MEM-IDLE-3', 'Idle 3 (Appreciation Slot)', '9000000002', $root, $root, Member::POSITION_LEFT);
        $idleSlot->update(['is_idle_slot' => true, 'idle_slot_label' => 'Idle - 3']);
        $candidate = $this->createMember('MEM-CANDIDATE-3', 'Pooja Verma', '9000000003', $root, $root, Member::POSITION_MIDDLE);

        // Set up an expired promotion (ended yesterday)
        $idleSlot->update([
            'appreciation_member_id' => $candidate->id,
            'appreciation_assigned_at' => now()->subDays(10),
            'appreciation_expires_at' => now()->subDay(),
            'appreciation_note' => 'Past promotion',
        ]);

        $this->assertFalse($idleSlot->isAppreciationActive());
        $this->assertTrue($idleSlot->isAppreciationExpired());

        // Unassign promotion
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('super-admin.mlm.tree.unassign-appreciation', $idleSlot));

        $response->assertOk();

        $idleSlot->refresh();
        $this->assertNull($idleSlot->appreciation_member_id);
        $this->assertNull($idleSlot->appreciation_expires_at);
        $this->assertNull($idleSlot->appreciation_assigned_at);
        $this->assertNull($idleSlot->appreciation_note);
    }

    public function test_search_promotable_members_endpoint(): void
    {
        $root = $this->createMember('MEM-ROOT', 'Root Member', '9000000001');
        $candidate = $this->createMember('MEM-FINDME', 'Alok Sharma', '9888877777', $root, $root, Member::POSITION_LEFT);

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('super-admin.mlm.tree.search-promotable', ['q' => 'Alok']));

        $response->assertOk()
            ->assertJsonPath('data.0.id', $candidate->id)
            ->assertJsonPath('data.0.name', 'Alok Sharma')
            ->assertJsonPath('data.0.customer_id', $candidate->customer_id);
    }

    public function test_payout_service_routes_idle_slot_commission_only_when_promotion_is_active(): void
    {
        Carbon::setTestNow('2026-10-23 10:00:00');

        $root = $this->createMember('MEM-ROOT', 'Root Member', '9000000001');
        $idleSlot = $this->createMember('MEM-IDLE-4', 'Idle 4 (Appreciation Slot)', '9000000002', $root, $root, Member::POSITION_LEFT);
        $idleSlot->update(['is_idle_slot' => true, 'idle_slot_label' => 'Idle - 4']);
        $candidate = $this->createMember('MEM-CANDIDATE-4', 'Kavita Joshi', '9000000003', $root, $root, Member::POSITION_MIDDLE);

        $rule = \App\Models\MlmCalculationRule::firstOrCreate(
            ['version' => 'v1.0'],
            [
                'is_active' => true,
                'pv_ratio' => '0.500000000000000000',
                'own_purchase_pv_percent' => '0.100000000000000000',
                'referral_pv_percent' => '0.200000000000000000',
                'distribution_levels' => 19,
                'level_percentages' => array_fill(1, 19, '0.010000000000000000'),
            ]
        );

        $run = \App\Models\MlmCalculationRun::create([
            'purchasing_member_id' => $root->id,
            'rule_version_id' => $rule->id,
            'source_transaction_reference' => 'TXN-001',
            'idempotency_key' => 'IDEM-001',
            'transaction_date' => now(),
            'eligible_amount' => '1000.000000000000000000',
            'total_pv' => '500.000000000000000000',
            'total_income' => '800.000000000000000000',
            'status' => 'calculated',
        ]);

        // 1. When Active: Route to Beneficiary
        $idleSlot->update([
            'appreciation_member_id' => $candidate->id,
            'appreciation_assigned_at' => now(),
            'appreciation_expires_at' => now()->addDays(7),
        ]);

        $ledger1 = \App\Models\MlmIncomeLedger::create([
            'calculation_run_id' => $run->id,
            'member_id' => $idleSlot->id,
            'purchasing_member_id' => $root->id,
            'rule_version_id' => $rule->id,
            'source_transaction_reference' => 'TXN-001',
            'transaction_date' => now(),
            'level' => 1,
            'eligible_amount' => '1000.000000000000000000',
            'pv_rate' => '0.500000000000000000',
            'rate' => '0.500000000000000000',
            'pv' => '500.000000000000000000',
            'income_type' => \App\Models\MlmIncomeLedger::TYPE_REFERRAL,
            'calculated_amount' => '500.000000000000000000',
            'net_amount' => '500.000000000000000000',
            'status' => \App\Models\MlmIncomeLedger::STATUS_CALCULATED,
        ]);

        $payoutService = app(\App\Services\Mlm\MlmPayoutService::class);
        $cycle1 = $payoutService->createWeeklyCycle(now(), $this->superAdmin);

        // Candidate should receive the payout line routed from the idle slot
        $candidateLine = \App\Models\MlmPayoutLine::where('payout_cycle_id', $cycle1->id)
            ->where('member_id', $candidate->id)
            ->first();

        $this->assertNotNull($candidateLine);
        $this->assertEquals('500.000000000000000000', (string) $candidateLine->gross_income);

        // 2. When Expired: Do NOT Route to Expired Beneficiary
        $idleSlot->update([
            'appreciation_expires_at' => now()->subMinute(),
        ]);

        $run2 = \App\Models\MlmCalculationRun::create([
            'purchasing_member_id' => $root->id,
            'rule_version_id' => $rule->id,
            'source_transaction_reference' => 'TXN-002',
            'idempotency_key' => 'IDEM-002',
            'transaction_date' => now()->addWeek(),
            'eligible_amount' => '1000.000000000000000000',
            'total_pv' => '500.000000000000000000',
            'total_income' => '300.000000000000000000',
            'status' => 'calculated',
        ]);

        $ledger2 = \App\Models\MlmIncomeLedger::create([
            'calculation_run_id' => $run2->id,
            'member_id' => $idleSlot->id,
            'purchasing_member_id' => $root->id,
            'rule_version_id' => $rule->id,
            'source_transaction_reference' => 'TXN-002',
            'transaction_date' => now()->addWeek(),
            'level' => 1,
            'eligible_amount' => '1000.000000000000000000',
            'pv_rate' => '0.500000000000000000',
            'rate' => '0.300000000000000000',
            'pv' => '500.000000000000000000',
            'income_type' => \App\Models\MlmIncomeLedger::TYPE_REFERRAL,
            'calculated_amount' => '300.000000000000000000',
            'net_amount' => '300.000000000000000000',
            'status' => \App\Models\MlmIncomeLedger::STATUS_CALCULATED,
        ]);

        $cycle2 = $payoutService->createWeeklyCycle(now()->addWeek(), $this->superAdmin);

        // The candidate should NOT get the line for this cycle; it stays assigned to the idle slot
        $candidateLine2 = \App\Models\MlmPayoutLine::where('payout_cycle_id', $cycle2->id)
            ->where('member_id', $candidate->id)
            ->first();
        $this->assertNull($candidateLine2);

        $idleSlotLine = \App\Models\MlmPayoutLine::where('payout_cycle_id', $cycle2->id)
            ->where('member_id', $idleSlot->id)
            ->first();
        $this->assertNotNull($idleSlotLine);
        $this->assertEquals('300.000000000000000000', (string) $idleSlotLine->gross_income);
    }

    private function createMember(
        string $memberId,
        string $name,
        string $phone,
        ?Member $sponsor = null,
        ?Member $parent = null,
        ?string $position = null
    ): Member {
        $user = User::create([
            'name' => $name,
            'email' => strtolower($memberId).'@example.com',
            'password' => 'password',
            'phone' => $phone,
            'role_id' => $this->customerRole->id,
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
