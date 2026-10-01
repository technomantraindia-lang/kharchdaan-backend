<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class CustomerOrderApiController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
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
        $order = Order::with('items.product')
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();

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
}
