<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\RefundService;
use App\Services\ReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentRefundAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Order $order;
    protected Category $category;
    protected RefundService $refundService;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);
        $this->user = User::create([
            'name' => 'Refund Test User',
            'email' => 'refundtest@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
        ]);

        $this->category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'status' => 'active',
        ]);

        $this->order = Order::create([
            'order_num' => 'ORD-REF-001',
            'user_id' => $this->user->id,
            'subtotal' => 1000.00,
            'total' => 1000.00,
            'status' => 'delivered',
            'pay_status' => 'paid',
        ]);

        $this->refundService = new RefundService();
    }

    /** 1. Test requesting refund within refundable limit succeeds */
    public function test_1_request_refund_valid_amount_succeeds(): void
    {
        $refund = $this->refundService->requestRefund($this->order, 400.00, 'Defective product');

        $this->assertInstanceOf(Refund::class, $refund);
        $this->assertEquals('pending', $refund->status);
        $this->assertEquals(400.00, $refund->amount);
    }

    /** 2. Test requesting refund exceeding total throws exception */
    public function test_2_request_refund_exceeding_total_fails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->refundService->requestRefund($this->order, 1500.00, 'Excessive refund request');
    }

    /** 3. Test cumulative pending refund requests exceeding order total fail */
    public function test_3_cumulative_pending_refunds_exceeding_total_fail(): void
    {
        // First pending refund of ₹600 on ₹1000 order
        $this->refundService->requestRefund($this->order, 600.00, 'First partial refund');

        // Second pending refund of ₹500 should fail because 600 + 500 = 1100 > 1000
        $this->expectException(InvalidArgumentException::class);
        $this->refundService->requestRefund($this->order, 500.00, 'Second excessive partial refund');
    }

    /** 4. Test approving refund updates order status and prevents re-approval */
    public function test_4_approve_refund_prevents_double_approval(): void
    {
        $refund = $this->refundService->requestRefund($this->order, 1000.00, 'Full refund');
        $result = $this->refundService->approveRefund($refund);

        $this->assertTrue($result);
        $this->assertEquals('approved', $refund->fresh()->status);
        $this->assertEquals('refunded', $this->order->fresh()->status);

        // Attempting to approve an already-approved refund should throw exception
        $this->expectException(InvalidArgumentException::class);
        $this->refundService->approveRefund($refund->fresh());
    }

    /** 5. Test ReturnService restocks ONLY when status is completed */
    public function test_5_return_service_restocks_only_on_completed_status(): void
    {
        $product = Product::create([
            'name' => 'Return Test Spice',
            'slug' => 'return-test-spice',
            'sku' => 'RET-SPICE-01',
            'category_id' => $this->category->id,
            'price' => 100.00,
            'stock_qty' => 10,
            'status' => 'active',
        ]);

        $returnService = new ReturnService(new InventoryService());
        $orderReturn = $returnService->createReturnRequest(
            $this->order,
            'Wrong size',
            [
                ['product_id' => $product->id, 'quantity' => 2, 'condition' => 'good'],
            ]
        );

        // Stock must still be 10 when status is 'requested' or 'received'
        $returnService->updateStatus($orderReturn, 'received');
        $this->assertEquals(10, $product->fresh()->stock_qty);

        // Stock should increase to 12 ONLY when completed
        $returnService->updateStatus($orderReturn->fresh(), 'completed');
        $this->assertEquals(12, $product->fresh()->stock_qty);
    }
}
