<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class MlmIntegrationFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mlm_integration_fields_are_additive_and_have_safe_defaults(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'mlm_member_id'));
        $this->assertTrue(Schema::hasColumn('orders', 'mlm_processing_status'));
        $this->assertTrue(Schema::hasColumn('orders', 'mlm_eligible_amount'));
        $this->assertTrue(Schema::hasColumn('orders', 'mlm_pv_processing_status'));
        $this->assertTrue(Schema::hasColumn('orders', 'mlm_formula_version'));
        $this->assertTrue(Schema::hasColumn('orders', 'mlm_integration_reference'));
        $this->assertTrue(Schema::hasColumn('orders', 'mlm_reversal_status'));

        $role = Role::create(['name' => 'MLM Integration Test Customer', 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'MLM Integration Customer',
            'email' => 'mlm-integration@example.com',
            'password' => 'password',
            'phone' => '9000000001',
            'role_id' => $role->id,
            'status' => 'active',
            'mlm_member_id' => 'MLM-TEST-001',
        ]);

        $order = Order::create([
            'order_num' => 'MLM-TEST-ORDER-001',
            'user_id' => $user->id,
            'subtotal' => '1000.00',
            'total' => '1000.00',
            'mlm_eligible_amount' => '999.99',
        ]);

        $freshOrder = $order->fresh();

        $this->assertSame('not_started', $freshOrder->mlm_processing_status);
        $this->assertSame('not_started', $freshOrder->mlm_pv_processing_status);
        $this->assertSame('not_applicable', $freshOrder->mlm_reversal_status);
        $this->assertSame('999.99', (string) $freshOrder->mlm_eligible_amount);
    }

    public function test_mlm_member_id_and_integration_reference_are_unique_when_supplied(): void
    {
        $role = Role::create(['name' => 'MLM Unique Test Customer', 'guard_name' => 'web']);
        User::create([
            'name' => 'First MLM Customer',
            'email' => 'mlm-unique-1@example.com',
            'password' => 'password',
            'phone' => '9000000002',
            'role_id' => $role->id,
            'status' => 'active',
            'mlm_member_id' => 'MLM-UNIQUE-001',
        ]);

        $this->expectException(QueryException::class);
        User::create([
            'name' => 'Second MLM Customer',
            'email' => 'mlm-unique-2@example.com',
            'password' => 'password',
            'phone' => '9000000003',
            'role_id' => $role->id,
            'status' => 'active',
            'mlm_member_id' => 'MLM-UNIQUE-001',
        ]);
    }

    public function test_integration_reference_is_unique_when_supplied(): void
    {
        $role = Role::create(['name' => 'MLM Reference Test Customer', 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'Reference Customer',
            'email' => 'mlm-reference@example.com',
            'password' => 'password',
            'phone' => '9000000004',
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        Order::create([
            'order_num' => 'MLM-REFERENCE-ORDER-001',
            'user_id' => $user->id,
            'subtotal' => '100.00',
            'total' => '100.00',
            'mlm_integration_reference' => 'MLM-REF-001',
        ]);

        $this->expectException(QueryException::class);
        Order::create([
            'order_num' => 'MLM-REFERENCE-ORDER-002',
            'user_id' => $user->id,
            'subtotal' => '100.00',
            'total' => '100.00',
            'mlm_integration_reference' => 'MLM-REF-001',
        ]);
    }
}
