<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use RuntimeException;
use Throwable;

class RazorpayPaymentService
{
    public function isConfigured(): bool
    {
        return filled(config('services.razorpay.key_id'))
            && filled(config('services.razorpay.key_secret'));
    }

    public function keyId(): string
    {
        return (string) config('services.razorpay.key_id');
    }

    public function api(): Api
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Razorpay is not configured. Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET.');
        }

        return new Api($this->keyId(), (string) config('services.razorpay.key_secret'));
    }

    /**
     * Create a Razorpay order for a local pending Order and persist payment row.
     *
     * @return array{razorpay_order_id: string, razorpay_key_id: string, amount: float, amount_paise: int, currency: string}
     */
    public function createOrderPayment(Order $order): array
    {
        $amountPaise = (int) round(((float) $order->total) * 100);
        if ($amountPaise < 100) {
            throw new RuntimeException('Order amount must be at least ₹1.00 for Razorpay.');
        }

        $razorpayOrder = $this->api()->order->create([
            'receipt' => (string) $order->order_num,
            'amount' => $amountPaise,
            'currency' => $order->currency ?: 'INR',
            'notes' => [
                'local_order_id' => (string) $order->id,
                'order_num' => (string) $order->order_num,
            ],
        ]);

        $razorpayOrderId = (string) ($razorpayOrder['id'] ?? '');
        if ($razorpayOrderId === '') {
            throw new RuntimeException('Razorpay did not return an order id.');
        }

        $order->update([
            'payment_method' => 'razorpay',
            'pay_status' => 'pending',
            'txn_id' => $razorpayOrderId,
        ]);

        Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'amount' => $order->total,
                'method' => 'razorpay',
                'status' => 'pending',
                'txn_id' => $razorpayOrderId,
                'resp' => json_encode([
                    'razorpay_order' => method_exists($razorpayOrder, 'toArray')
                        ? $razorpayOrder->toArray()
                        : (array) $razorpayOrder,
                    'created_at' => now()->toIso8601String(),
                ]),
            ]
        );

        return [
            'razorpay_order_id' => $razorpayOrderId,
            'razorpay_key_id' => $this->keyId(),
            'amount' => (float) $order->total,
            'amount_paise' => $amountPaise,
            'currency' => $order->currency ?: 'INR',
        ];
    }

    /**
     * Verify Checkout.js signature and mark the local order paid.
     *
     * @param  array{razorpay_order_id: string, razorpay_payment_id: string, razorpay_signature: string}  $payload
     */
    public function verifyAndMarkPaid(array $payload): Order
    {
        $orderId = (string) ($payload['razorpay_order_id'] ?? '');
        $paymentId = (string) ($payload['razorpay_payment_id'] ?? '');
        $signature = (string) ($payload['razorpay_signature'] ?? '');

        if ($orderId === '' || $paymentId === '' || $signature === '') {
            throw new RuntimeException('Missing Razorpay payment verification fields.');
        }

        $expected = hash_hmac(
            'sha256',
            $orderId.'|'.$paymentId,
            (string) config('services.razorpay.key_secret')
        );

        if (! hash_equals($expected, $signature)) {
            throw new RuntimeException('Invalid Razorpay payment signature.');
        }

        return $this->markOrderPaidByRazorpayIds($orderId, $paymentId, [
            'source' => 'checkout_verify',
            'razorpay_signature' => $signature,
        ]);
    }

    /**
     * Idempotently mark order paid from verified Razorpay order/payment ids.
     *
     * @param  array<string, mixed>  $meta
     */
    public function markOrderPaidByRazorpayIds(string $razorpayOrderId, string $razorpayPaymentId, array $meta = []): Order
    {
        return DB::transaction(function () use ($razorpayOrderId, $razorpayPaymentId, $meta) {
            $order = Order::query()
                ->where('txn_id', $razorpayOrderId)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                $payment = Payment::query()
                    ->where(function ($q) use ($razorpayOrderId, $razorpayPaymentId) {
                        $q->where('txn_id', $razorpayOrderId)
                            ->orWhere('txn_id', $razorpayPaymentId);
                    })
                    ->lockForUpdate()
                    ->first();
                if ($payment) {
                    $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->first();
                }
            }

            if (! $order) {
                throw new RuntimeException('No local order found for this Razorpay payment.');
            }

            if ($order->pay_status === 'paid') {
                return $order->fresh(['items.product', 'payment']);
            }

            $order->update([
                'pay_status' => 'paid',
                'payment_method' => 'razorpay',
                'txn_id' => $razorpayPaymentId,
            ]);

            Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'amount' => $order->total,
                    'method' => 'razorpay',
                    'status' => 'paid',
                    'txn_id' => $razorpayPaymentId,
                    'resp' => json_encode(array_merge($meta, [
                        'razorpay_order_id' => $razorpayOrderId,
                        'razorpay_payment_id' => $razorpayPaymentId,
                        'paid_at' => now()->toIso8601String(),
                    ])),
                ]
            );

            return $order->fresh(['items.product', 'payment']);
        });
    }

    /**
     * Handle Razorpay webhook payload (payment.captured / payment.failed).
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload, ?string $signatureHeader): void
    {
        $secret = (string) config('services.razorpay.webhook_secret');
        if ($secret !== '') {
            $raw = request()->getContent();
            $expected = hash_hmac('sha256', $raw, $secret);
            if (! is_string($signatureHeader) || ! hash_equals($expected, $signatureHeader)) {
                throw new RuntimeException('Invalid Razorpay webhook signature.');
            }
        }

        $event = (string) ($payload['event'] ?? '');
        $entity = data_get($payload, 'payload.payment.entity', []);
        $razorpayPaymentId = (string) ($entity['id'] ?? '');
        $razorpayOrderId = (string) ($entity['order_id'] ?? '');

        if ($razorpayOrderId === '' || $razorpayPaymentId === '') {
            Log::info('Razorpay webhook ignored: missing ids', ['event' => $event]);

            return;
        }

        try {
            if (in_array($event, ['payment.captured', 'order.paid'], true)) {
                $this->markOrderPaidByRazorpayIds($razorpayOrderId, $razorpayPaymentId, [
                    'source' => 'webhook',
                    'event' => $event,
                ]);
            }

            if ($event === 'payment.failed') {
                $order = Order::query()->where('txn_id', $razorpayOrderId)->first();
                if ($order && $order->pay_status === 'pending') {
                    $order->update(['pay_status' => 'failed']);
                    Payment::where('order_id', $order->id)->update([
                        'status' => 'failed',
                        'resp' => json_encode(['source' => 'webhook', 'event' => $event, 'entity' => $entity]),
                    ]);
                }
            }
        } catch (Throwable $e) {
            Log::error('Razorpay webhook processing failed', [
                'event' => $event,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
