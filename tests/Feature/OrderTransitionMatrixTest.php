<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTransitionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $adminRole = Role::where('name', 'Admin')->first();
        $superAdminRole = Role::where('name', 'Super Admin')->first();

        $this->admin = User::create([
            'name' => 'Regular Admin',
            'email' => 'regadminorder@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadminorder@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $superAdminRole->id,
            'status' => 'active',
        ]);
    }

    public function test_order_status_valid_transitions_succeed(): void
    {
        $user = User::first();
        $order = Order::create([
            'order_num' => 'ORD-TRANS-001',
            'user_id' => $user->id,
            'subtotal' => 100,
            'total' => 100,
            'status' => 'pending',
            'pay_status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), [
            'status' => 'processing',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals('processing', $order->status);
    }

    public function test_order_status_invalid_transition_fails_for_regular_admin(): void
    {
        $user = User::first();
        $order = Order::create([
            'order_num' => 'ORD-TRANS-002',
            'user_id' => $user->id,
            'subtotal' => 100,
            'total' => 100,
            'status' => 'delivered',
            'pay_status' => 'paid',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), [
            'status' => 'pending',
        ]);

        $response->assertSessionHas('error');
        $order->refresh();
        $this->assertEquals('delivered', $order->status);
    }

    public function test_super_admin_override_succeeds_with_reason(): void
    {
        $user = User::first();
        $order = Order::create([
            'order_num' => 'ORD-TRANS-003',
            'user_id' => $user->id,
            'subtotal' => 100,
            'total' => 100,
            'status' => 'delivered',
            'pay_status' => 'paid',
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('admin.orders.update', $order), [
            'status' => 'pending',
            'override_reason' => 'Customer requested re-fulfillment due to wrong item sent.',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }
}
