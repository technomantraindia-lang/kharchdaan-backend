<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\Payments\RazorpayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class RazorpayPaymentController extends Controller
{
    public function __construct(private readonly RazorpayPaymentService $razorpay) {}

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        try {
            $order = $this->razorpay->verifyAndMarkPaid($data);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Payment verification failed.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment verified successfully.',
            'data' => new OrderResource($order),
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        try {
            $this->razorpay->handleWebhook(
                $request->all(),
                $request->header('X-Razorpay-Signature')
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Webhook rejected.',
            ], 400);
        }

        return response()->json(['success' => true]);
    }
}
