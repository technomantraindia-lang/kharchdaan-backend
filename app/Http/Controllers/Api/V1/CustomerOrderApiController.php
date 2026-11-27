<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Services\Delivery\DeliveryAreaService;
use App\Services\Order\OrderTransactionIdGenerator;
use App\Services\OrderService;
use App\Services\Payments\RazorpayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Throwable;

class CustomerOrderApiController extends Controller
{
    public function __construct(
        private readonly OrderTransactionIdGenerator $transactionIds,
        private readonly DeliveryAreaService $deliveryArea,
        private readonly RazorpayPaymentService $razorpay,
        private readonly OrderService $orderService,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to view orders.',
            ], 401);
        }

        $orders = Order::with('items.product')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Customer orders fetched successfully.',
            'data' => OrderResource::collection($orders),
        ]);
    }

    public function show($id)
    {
        $user = auth()->user();
        $query = Order::with('items.product')->where('id', $id);

        if ($user) {
            $query->where('user_id', $user->id);
        }

        $order = $query->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or unauthorized.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order details fetched successfully.',
            'data' => new OrderResource($order),
        ]);
    }

    public function cancel(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Please login to cancel an order.'], 401);
        }

        $order = Order::query()->where('user_id', $user->id)->whereKey($id)->first();
        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        // Client rule: customer may cancel any time before dispatch (Pending / Processing / Packed).
        if (! in_array($order->status, ['pending', 'processing', 'packed'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This order has already been dispatched and can no longer be cancelled. Please use the return process.',
            ], 422);
        }

        try {
            $this->orderService->updateOrderStatus(
                $order,
                'cancelled',
                'Cancelled by customer from storefront account.'
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.',
            'data' => new OrderResource($order->fresh('items.product')),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (! $user && $request->bearerToken()) {
            $tokenRecord = \App\Models\PersonalAccessToken::findToken($request->bearerToken());
            if ($tokenRecord && $tokenRecord->tokenable) {
                $user = $tokenRecord->tokenable;
            }
        }

        $items = $request->input('items', []);
        if (empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty. Please add items to place an order.',
            ], 422);
        }

        $city = $request->input('city')
            ?? data_get($request->input('shipping_address'), 'city')
            ?? $request->input('shipping_city');
        $pincode = $request->input('pincode')
            ?? data_get($request->input('shipping_address'), 'pincode')
            ?? data_get($request->input('shipping_address'), 'zip')
            ?? $request->input('shipping_pincode');

        $rawShipAddr = $request->input('shipping_address', 'Doorstep Delivery');
        $shipAddrStr = is_array($rawShipAddr) ? json_encode($rawShipAddr) : (string) $rawShipAddr;

        if (! $this->deliveryArea->allows($city, $pincode, $shipAddrStr)) {
            return response()->json([
                'success' => false,
                'message' => $this->deliveryArea->blockMessage(),
                'errors' => ['delivery_area' => [$this->deliveryArea->blockMessage()]],
            ], 422);
        }

        $channel = $request->input('order_channel', 'online');
        if (! in_array($channel, ['online', 'offline'], true)) {
            $channel = 'online';
        }

        $validator = Validator::make($request->all(), [
            'special_instruction' => ['required', 'string', 'max:2000'],
            'offline_remark' => [$channel === 'offline' ? 'required' : 'nullable', 'string', 'max:2000'],
            'offline_document' => [$channel === 'offline' ? 'required' : 'nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,webp'],
            'offline_amount' => [$channel === 'offline' ? 'required' : 'nullable', 'numeric', 'min:1'],
        ], [
            'offline_remark.required' => 'Remark is mandatory for offline transactions.',
            'offline_document.required' => 'Document attachment is mandatory for offline transactions.',
            'offline_amount.required' => 'Please enter the offline bill amount before payment.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $requestedMethod = strtolower((string) $request->input('payment_method', 'razorpay'));
        $isCod = $requestedMethod === 'cod';
        // Map legacy UI methods (upi/cards/netbanking) to Razorpay Checkout.
        $usesRazorpay = ! $isCod;

        if ($usesRazorpay && ! $this->razorpay->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Online payment is temporarily unavailable. Please use Cash on Delivery or try again later.',
            ], 503);
        }

        try {
            $order = DB::transaction(function () use ($request, $user, $items, $shipAddrStr, $channel, $isCod) {
                $subtotal = 0;
                $orderItemsData = [];

                if ($channel === 'offline') {
                    $subtotal = (float) $request->input('offline_amount');
                    $orderItemsData[] = [
                        'product_id' => null,
                        'product_name' => 'Offline Purchase',
                        'sku' => 'OFFLINE',
                        'qty' => 1,
                        'price' => $subtotal,
                        'line_total' => $subtotal,
                    ];
                } else {
                    foreach ($items as $item) {
                        $qty = max(1, (int) ($item['quantity'] ?? $item['qty'] ?? 1));
                        $price = (float) ($item['price'] ?? 0);
                        $lineTotal = $price * $qty;
                        $subtotal += $lineTotal;

                        $productId = $item['product_id'] ?? $item['id'] ?? null;
                        $productName = $item['product_name'] ?? $item['name'] ?? 'Product Item';
                        $sku = $item['sku'] ?? null;

                        if ($productId && is_numeric($productId)) {
                            $prodModel = Product::find($productId);
                            if ($prodModel) {
                                $productName = $prodModel->name;
                                $sku = $sku ?: $prodModel->sku;
                            }
                        }

                        $orderItemsData[] = [
                            'product_id' => (is_numeric($productId) ? (int) $productId : null),
                            'product_name' => $productName,
                            'sku' => $sku,
                            'qty' => $qty,
                            'price' => $price,
                            'line_total' => $lineTotal,
                        ];
                    }
                }

                $discount = (float) ($request->input('discount', 0));
                $platformFee = (float) ($request->input('platform_fee', $channel === 'online' && count($items) > 0 ? 5 : 0));
                $shippingCharge = (float) ($request->input('shipping_charge', 0));
                $effectiveShipCharge = $shippingCharge + $platformFee;
                $total = max(0, $subtotal - $discount + $effectiveShipCharge);

                $rawBillAddr = $request->input('billing_address', $request->input('shipping_address'));
                $billAddrStr = is_array($rawBillAddr) ? json_encode($rawBillAddr) : (string) $rawBillAddr;

                $documentPath = null;
                if ($channel === 'offline' && $request->hasFile('offline_document')) {
                    $documentPath = $request->file('offline_document')->store('offline-orders', 'public');
                }

                // Reuse one unpaid checkout draft so payment retries / method changes
                // do not mint a new order number each time.
                $reuseOrder = null;
                if ($user) {
                    $reuseId = (int) $request->input('reuse_order_id', 0);
                    $openQuery = Order::query()
                        ->where('user_id', $user->id)
                        ->where('order_channel', $channel)
                        ->where('status', 'pending')
                        ->whereIn('pay_status', ['pending', 'failed']);

                    if ($reuseId > 0) {
                        $reuseOrder = (clone $openQuery)->whereKey($reuseId)->first();
                    }
                    if (! $reuseOrder) {
                        $reuseOrder = (clone $openQuery)->latest('id')->first();
                    }

                    // Cancel other open unpaid drafts for this user/channel.
                    $openQuery
                        ->when($reuseOrder, fn ($q) => $q->where('id', '!=', $reuseOrder->id))
                        ->orderBy('id')
                        ->get()
                        ->each(function (Order $stale) {
                            try {
                                $this->orderService->updateOrderStatus(
                                    $stale,
                                    'cancelled',
                                    'Superseded by a newer checkout / payment attempt.'
                                );
                            } catch (Throwable $e) {
                                report($e);
                            }
                        });
                }

                $payload = [
                    'user_id' => $user ? $user->id : null,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'ship_charge' => $effectiveShipCharge,
                    'total' => $total,
                    'status' => 'pending',
                    'pay_status' => 'pending',
                    'payment_method' => $isCod ? 'cod' : 'razorpay',
                    'ship_addr' => $shipAddrStr,
                    'bill_addr' => $billAddrStr,
                    'currency' => 'INR',
                    'order_channel' => $channel,
                    'special_instruction' => $request->input('special_instruction'),
                    'offline_remark' => $request->input('offline_remark'),
                ];
                if ($documentPath) {
                    $payload['offline_document_path'] = $documentPath;
                }

                if ($reuseOrder) {
                    $order = $reuseOrder;
                    $order->fill($payload)->save();
                    $order->items()->delete();
                    Payment::query()->where('order_id', $order->id)->delete();
                } else {
                    $order = Order::create(array_merge($payload, [
                        'order_num' => $this->transactionIds->next(),
                    ]));
                }

                foreach ($orderItemsData as $oi) {
                    $oi['order_id'] = $order->id;
                    OrderItem::create($oi);
                }

                if ($isCod) {
                    Payment::create([
                        'order_id' => $order->id,
                        'amount' => $order->total,
                        'method' => 'cod',
                        'status' => 'pending',
                    ]);
                }

                return [
                    'order' => $order->load('items.product'),
                    'reused' => (bool) $reuseOrder,
                ];
            });
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create order. Please try again.',
            ], 500);
        }

        $wasReused = (bool) ($order['reused'] ?? false);
        $order = $order['order'];

        $responseData = (new OrderResource($order))->resolve();
        $responseData['requires_payment'] = $usesRazorpay;
        $responseData['reused'] = $wasReused;

        if ($usesRazorpay) {
            try {
                $rzp = $this->razorpay->createOrderPayment($order->fresh());
                $responseData['razorpay'] = $rzp;
                $order->refresh();
                $responseData['payment_status'] = $order->pay_status;
                $responseData['payment_method'] = $order->payment_method;
            } catch (Throwable $e) {
                report($e);

                return response()->json([
                    'success' => false,
                    'message' => 'Order saved but payment could not be started: '.$e->getMessage(),
                    'data' => $responseData,
                ], 502);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $usesRazorpay
                ? ($wasReused
                    ? 'Continuing your unpaid order. Complete payment to confirm.'
                    : 'Order created. Complete payment to confirm.')
                : 'Order placed successfully (Cash on Delivery).',
            'data' => $responseData,
        ], $wasReused ? 200 : 201);
    }
}
