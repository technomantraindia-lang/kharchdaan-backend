<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerOrderApiController extends Controller
{
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

        return DB::transaction(function () use ($request, $user, $items) {
            $orderNum = 'BG-' . strtoupper(Str::random(6));

            $subtotal = 0;
            $orderItemsData = [];

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
                    'product_id' => (is_numeric($productId) ? (int)$productId : null),
                    'product_name' => $productName,
                    'sku' => $sku,
                    'qty' => $qty,
                    'price' => $price,
                    'line_total' => $lineTotal,
                ];
            }

            $discount = (float) ($request->input('discount', 0));
            $platformFee = (float) ($request->input('platform_fee', count($items) > 0 ? 5 : 0));
            $shippingCharge = (float) ($request->input('shipping_charge', 0));
            $effectiveShipCharge = $shippingCharge + $platformFee;
            $total = max(0, $subtotal - $discount + $effectiveShipCharge);

            $rawShipAddr = $request->input('shipping_address', 'Doorstep Delivery');
            $shipAddrStr = is_array($rawShipAddr) ? json_encode($rawShipAddr) : (string) $rawShipAddr;

            $rawBillAddr = $request->input('billing_address', $rawShipAddr);
            $billAddrStr = is_array($rawBillAddr) ? json_encode($rawBillAddr) : (string) $rawBillAddr;

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
