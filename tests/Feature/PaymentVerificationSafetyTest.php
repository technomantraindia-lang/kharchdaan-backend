<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentVerificationSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $role = Role::where('name', 'Super Admin')->first();
        $this->admin = User::create([
            'name' => 'Admin Pay',
            'email' => 'adminpay@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    public function test_normal_order_update_does_not_silently_change_payment_status(): void
    {
        $user = User::first();
        $order = Order::create([
            'order_num' => 'ORD-PAY-001',
            'user_id' => $user->id,
            'subtotal' => 100,
            'total' => 100,
            'status' => 'pending',
            'pay_status' => 'pending',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 100,
            'method' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), [
            'status' => 'processing',
            'pay_status' => 'paid', // Passed in arbitrary payload
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals('pending', $order->pay_status); // Must remain pending via normal update
    }

    public function test_manual_payment_verification_action_succeeds_and_logs_activity(): void
    {
        $user = User::first();
        $order = Order::create([
            'order_num' => 'ORD-PAY-002',
            'user_id' => $user->id,
            'subtotal' => 100,
            'total' => 100,
            'status' => 'pending',
            'pay_status' => 'pending',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 100,
            'method' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.verifyPayment', $order), [
            'reason' => 'Verified customer bank transfer slip #TXN998877 in bank portal',
            'txn_id' => 'TXN998877',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals('paid', $order->pay_status);
        $this->assertEquals('paid', $order->payment->status);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'manual_payment_verification',
            'module' => 'payments',
        ]);
    }
}
