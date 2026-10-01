<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product', 'payment'])->withCount('items')->latest();

        // 1. Order Number / WooCommerce ID / Tracking / Search
        if ($request->filled('order_num')) {
            $query->where('order_num', 'like', '%' . trim($request->order_num) . '%');
        }
        if ($request->filled('woocommerce_id')) {
            $query->where('woocommerce_id', trim($request->woocommerce_id));
        }
        if ($request->filled('tracking_num')) {
            $query->where('tracking_num', 'like', '%' . trim($request->tracking_num) . '%');
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('order_num', 'like', "%{$search}%")
                    ->orWhere('woocommerce_id', 'like', "%{$search}%")
                    ->orWhere('tracking_num', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        // 2. Customer Name / Email / Phone
        if ($request->filled('customer_name') || $request->filled('email') || $request->filled('phone')) {
            $query->whereHas('user', function ($uq) use ($request) {
                if ($request->filled('customer_name')) {
                    $uq->where('name', 'like', '%' . trim($request->customer_name) . '%');
                }
                if ($request->filled('email')) {
                    $uq->where('email', 'like', '%' . trim($request->email) . '%');
                }
                if ($request->filled('phone')) {
                    $uq->where('phone', 'like', '%' . trim($request->phone) . '%');
                }
            });
        }

        // 3. Status & Payment Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('pay_status')) {
            $query->where('pay_status', $request->pay_status);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('courier')) {
            $query->where('courier', 'like', '%' . trim($request->courier) . '%');
        }

        // 4. Date Range Filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 5. Amount Range Filters
        if ($request->filled('min_amount')) {
            $query->where('total', '>=', (float) $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $query->where('total', '<=', (float) $request->max_amount);
        }

        // 6. Product / SKU Filters
        if ($request->filled('product') || $request->filled('sku')) {
            $query->whereHas('items', function ($iq) use ($request) {
                if ($request->filled('product')) {
                    $iq->where('product_name', 'like', '%' . trim($request->product) . '%')
                       ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', '%' . trim($request->product) . '%'));
                }
                if ($request->filled('sku')) {
                    $iq->where('sku', 'like', '%' . trim($request->sku) . '%')
                       ->orWhereHas('product', fn ($pq) => $pq->where('sku', 'like', '%' . trim($request->sku) . '%'));
                }
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => \App\Http\Resources\OrderResource::collection($orders),
                'pagination' => [
                    'total' => $orders->total(),
                    'current_page' => $orders->currentPage(),
                ],
            ]);
        }

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load([
            'user',
            'items.product.images',
            'payment',
            'invoice',
            'statusHistory' => fn ($q) => $q->latest(),
            'coupon',
            'shippingMethod',
        ]);

        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $request, Order $order, \App\Services\OrderService $orderService)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,processing,packed,shipped,delivered,cancelled,failed,refunded',
            'tracking_num' => 'nullable|string|max:255',
            'courier' => 'nullable|string|max:255',
            'admin_note' => 'nullable|string',
            'status_note' => 'nullable|string|max:500',
            'override_reason' => 'nullable|string|max:500',
        ]);

        $order->update($request->only(['tracking_num', 'courier', 'admin_note']));

        $authUser = auth()->user();
        $isSuperAdminOverride = $authUser && $authUser->isSuperAdmin() && $request->filled('override_reason');
        $overrideReason = $request->input('override_reason');

        try {
            $orderService->updateOrderStatus(
                $order,
                $data['status'],
                $data['status_note'] ?? null,
                $isSuperAdminOverride,
                $overrideReason
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order updated successfully.');
    }

    public function verifyPaymentManually(Request $request, Order $order, \App\Services\OrderService $orderService)
    {
        $request->validate([
            'reason' => 'required|string|min:5|max:500',
            'txn_id' => 'nullable|string|max:255',
        ]);

        $orderService->markPaymentVerifiedManually(
            $order,
            $request->input('reason'),
            $request->input('txn_id')
        );

        return redirect()->back()->with('success', 'Payment marked as verified manually.');
    }

    public function bulkAction(Request $request, \App\Services\OrderService $orderService)
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id',
            'action' => 'required|string|in:change_status,export',
            'target_status' => 'required_if:action,change_status|string|in:processing,packed,shipped,delivered,cancelled,failed,refunded',
        ]);

        $orderIds = $request->order_ids;
        $action = $request->action;
        $targetStatus = $request->target_status;

        $updatedCount = 0;
        $skipped = [];

        foreach ($orderIds as $id) {
            $order = Order::find($id);
            if (! $order) {
                $skipped[] = ['id' => $id, 'reason' => 'Order not found'];
                continue;
            }

            if ($action === 'change_status') {
                try {
                    $orderService->updateOrderStatus($order, $targetStatus, 'Bulk status update', false, null, 'bulk_update_order_status');
                    $updatedCount++;
                } catch (\InvalidArgumentException $e) {
                    $skipped[] = [
                        'id' => $order->id,
                        'order_num' => $order->order_num,
                        'reason' => $e->getMessage(),
                    ];
                }
            }
        }

        $message = "{$updatedCount} orders updated successfully.";
        if (count($skipped) > 0) {
            $message .= " " . count($skipped) . " orders skipped due to invalid state transitions.";
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'updated_count' => $updatedCount,
                'skipped_count' => count($skipped),
                'skipped' => $skipped,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}
