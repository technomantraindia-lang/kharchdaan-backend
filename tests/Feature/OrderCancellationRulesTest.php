<?php

namespace Tests\Feature;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Client-approved cancellation rules:
 * - Customer / admin can cancel before dispatch (Pending, Processing, Packed).
 * - No cancellation after dispatch (Shipped).
 * - Paid orders cancelled before dispatch carry a refund-due note (5–7 working days).
 */
class OrderCancellationRulesTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(string $status, string $payStatus = 'pending'): Order
    {
        $this->seed();
        $user = User::first();

        return Order::create([
            'order_num' => 'ORD-CANCEL-'.strtoupper(uniqid()),
            'user_id' => $user->id,
            'subtotal' => 500,
            'total' => 500,
            'status' => $status,
            'pay_status' => $payStatus,
        ]);
    }

    public function test_cancel_allowed_before_dispatch(): void
    {
        $service = app(OrderService::class);

        foreach (['pending', 'processing', 'packed'] as $from) {
            $this->assertTrue($service->canTransition($from, 'cancelled'), "{$from} should be cancellable");
        }
    }

    public function test_cancel_not_allowed_after_dispatch(): void
    {
        $service = app(OrderService::class);

        $this->assertFalse($service->canTransition('shipped', 'cancelled'));
        $this->assertFalse($service->canTransition('delivered', 'cancelled'));
    }

    public function test_customer_can_cancel_flag_matches_rule(): void
    {
        $this->assertTrue((bool) (new OrderResource($this->makeOrder('packed')))->resolve()['can_cancel']);
        $this->assertFalse((bool) (new OrderResource($this->makeOrder('shipped')))->resolve()['can_cancel']);
    }

    public function test_paid_order_cancellation_records_refund_due_note(): void
    {
        $order = $this->makeOrder('packed', 'paid');

        app(OrderService::class)->updateOrderStatus($order, 'cancelled', 'Cancelled by admin.');

        $history = OrderStatusHistory::where('order_id', $order->id)->where('status', 'cancelled')->first();
        $this->assertNotNull($history);
        $this->assertStringContainsString('REFUND DUE', $history->note);
    }

    public function test_unpaid_order_cancellation_has_no_refund_note(): void
    {
        $order = $this->makeOrder('processing', 'pending');

        app(OrderService::class)->updateOrderStatus($order, 'cancelled', 'Cancelled by customer.');

        $history = OrderStatusHistory::where('order_id', $order->id)->where('status', 'cancelled')->first();
        $this->assertNotNull($history);
        $this->assertStringNotContainsString('REFUND DUE', $history->note);
    }
}
