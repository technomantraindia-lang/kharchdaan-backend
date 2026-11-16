<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Delivery\DeliveryAreaService;
use App\Services\Order\OrderTransactionIdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerOrderApiController extends Controller
{
    public function __construct(
        private readonly OrderTransactionIdGenerator $transactionIds,
        private readonly DeliveryAreaService $deliveryArea,
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

        return DB::transaction(function () use ($request, $user, $items, $shipAddrStr, $channel) {
            $orderNum = $this->transactionIds->next();

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

            $order = Order::create([
                'order_num' => $orderNum,
                'user_id' => $user ? $user->id : null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'ship_charge' => $effectiveShipCharge,
                'total' => $total,
                'status' => 'pending',
                'pay_status' => $request->input('payment_method') === 'cod' ? 'pending' : 'paid',
                'payment_method' => $request->input('payment_method', 'cod'),
                'ship_addr' => $shipAddrStr,
                'bill_addr' => $billAddrStr,
                'currency' => 'INR',
                'order_channel' => $channel,
                'special_instruction' => $request->input('special_instruction'),
                'offline_remark' => $request->input('offline_remark'),
                'offline_document_path' => $documentPath,
            ]);

            foreach ($orderItemsData as $oi) {
                $oi['order_id'] = $order->id;
                OrderItem::create($oi);
            }

            $order->load('items.product');

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data' => new OrderResource($order),
            ], 201);
        });
    }
}
