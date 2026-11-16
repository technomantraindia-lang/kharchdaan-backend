<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Services\Payments\RazorpayPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RazorpayPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.razorpay.key_id' => 'rzp_test_dummy_key',
            'services.razorpay.key_secret' => 'test_secret_value_123',
            'services.razorpay.webhook_secret' => '',
        ]);
    }

    public function test_cod_order_stays_unpaid_on_create(): void
    {
        $role = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);
        $user = User::create([
            'name' => 'COD Buyer',
            'email' => 'cod-buyer@example.com',
            'password' => 'password',
            'phone' => '9876500011',
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/orders', [
            'payment_method' => 'cod',
            'order_channel' => 'online',
            'special_instruction' => 'Call before delivery',
            'city' => 'Ahmedabad',
            'pincode' => '380001',
            'shipping_address' => 'Test Address, Ahmedabad, Gujarat - 380001',
            'items' => [
                ['name' => 'Test Product', 'price' => 200, 'qty' => 1],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.requires_payment', false);

        $this->assertDatabaseHas('orders', [
            'order_num' => $response->json('data.order_number'),
            'pay_status' => 'pending',
            'payment_method' => 'cod',
        ]);
    }

    public function test_verify_marks_order_paid_with_valid_signature(): void
    {
        $order = Order::create([
            'order_num' => 'T2611160001',
            'user_id' => null,
            'subtotal' => 200,
            'discount' => 0,
            'ship_charge' => 0,
            'total' => 200,
            'status' => 'pending',
            'pay_status' => 'pending',
            'payment_method' => 'razorpay',
            'txn_id' => 'order_test_rzp_001',
            'currency' => 'INR',
            'ship_addr' => 'Ahmedabad',
            'bill_addr' => 'Ahmedabad',
            'order_channel' => 'online',
            'special_instruction' => 'N/A',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 200,
            'method' => 'razorpay',
            'status' => 'pending',
            'txn_id' => 'order_test_rzp_001',
        ]);

        $paymentId = 'pay_test_001';
        $signature = hash_hmac(
            'sha256',
            'order_test_rzp_001|'.$paymentId,
            'test_secret_value_123'
        );

        $response = $this->postJson('/api/v1/payments/razorpay/verify', [
            'razorpay_order_id' => 'order_test_rzp_001',
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'pay_status' => 'paid',
            'txn_id' => $paymentId,
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'paid',
            'txn_id' => $paymentId,
        ]);
    }

    public function test_verify_rejects_invalid_signature(): void
    {
        Order::create([
            'order_num' => 'T2611160002',
            'subtotal' => 200,
            'discount' => 0,
            'ship_charge' => 0,
            'total' => 200,
            'status' => 'pending',
            'pay_status' => 'pending',
            'payment_method' => 'razorpay',
            'txn_id' => 'order_test_rzp_002',
            'currency' => 'INR',
            'ship_addr' => 'Ahmedabad',
            'bill_addr' => 'Ahmedabad',
            'order_channel' => 'online',
            'special_instruction' => 'N/A',
        ]);

        $this->postJson('/api/v1/payments/razorpay/verify', [
            'razorpay_order_id' => 'order_test_rzp_002',
            'razorpay_payment_id' => 'pay_bad',
            'razorpay_signature' => 'invalid',
        ])->assertStatus(422);
    }

    public function test_service_reports_configured_when_keys_present(): void
    {
        $this->assertTrue(app(RazorpayPaymentService::class)->isConfigured());
        config(['services.razorpay.key_secret' => '']);
        $this->assertFalse(app(RazorpayPaymentService::class)->isConfigured());
    }
}
