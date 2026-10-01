<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = trim($request->get('q', ''));

        if (empty($query)) {
            return response()->json([
                'success' => true,
                'query' => '',
                'results' => [
                    'orders' => [],
                    'products' => [],
                    'customers' => [],
                    'invoices' => [],
                ],
            ]);
        }

        $user = auth()->user();

        // 1. Orders Search
        $orders = [];
        if ($user && ($user->hasPermission('orders.view') || $user->hasRole('Super Admin'))) {
            $orders = Order::with('user')
                ->where(function ($q) use ($query) {
                    $q->where('order_num', 'like', "%{$query}%")
                        ->orWhere('woocommerce_id', 'like', "%{$query}%")
                        ->orWhere('tracking_num', 'like', "%{$query}%")
                        ->orWhereHas('user', function ($uq) use ($query) {
                            $uq->where('name', 'like', "%{$query}%")
                                ->orWhere('email', 'like', "%{$query}%")
                                ->orWhere('phone', 'like', "%{$query}%");
                        })
                        ->orWhereHas('items', function ($iq) use ($query) {
                            $iq->where('sku', 'like', "%{$query}%")
                                ->orWhere('product_name', 'like', "%{$query}%");
                        });
                })
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_num' => $order->order_num,
                        'woocommerce_id' => $order->woocommerce_id,
                        'customer_name' => $order->user?->name ?? 'Guest',
                        'status' => $order->status,
                        'total' => (float) $order->total,
                        'url' => route('admin.orders.show', $order->id),
                    ];
                });
        }

        // 2. Products Search
        $products = [];
        if ($user && ($user->hasPermission('products.view') || $user->hasRole('Super Admin'))) {
            $products = Product::where('name', 'like', "%{$query}%")
                ->orWhere('sku', 'like', "%{$query}%")
                ->orWhere('woocommerce_id', 'like', "%{$query}%")
                ->limit(10)
                ->get()
                ->map(function ($prod) {
                    return [
                        'id' => $prod->id,
                        'name' => $prod->name,
                        'sku' => $prod->sku,
                        'woocommerce_id' => $prod->woocommerce_id,
                        'price' => (float) $prod->price,
                        'stock_qty' => $prod->stock_qty,
                        'url' => route('admin.products.show', $prod->id),
                    ];
                });
        }

        // 3. Customers Search (Strictly check permission)
        $customers = [];
        if ($user && ($user->hasPermission('customers.view') || $user->hasPermission('users.view') || $user->hasRole('Super Admin'))) {
            $customers = User::where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('woocommerce_customer_id', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($cust) {
                return [
                    'id' => $cust->id,
                    'name' => $cust->name,
                    'email' => $cust->email,
                    'phone' => $cust->phone,
                    'role' => $cust->role?->name ?? 'User',
                    'url' => route('admin.customers.show', $cust->id),
                ];
            });
        }

        // 4. Invoices Search
        $invoices = [];
        if ($user && ($user->hasPermission('invoices.view') || $user->hasPermission('orders.view') || $user->hasRole('Super Admin'))) {
            $invoices = Invoice::with('order')
                ->where('inv_num', 'like', "%{$query}%")
                ->limit(10)
                ->get()
                ->map(function ($inv) {
                    return [
                        'id' => $inv->id,
                        'inv_num' => $inv->inv_num,
                        'order_num' => $inv->order?->order_num,
                        'url' => route('admin.invoices.show', $inv->id),
                    ];
                });
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'query' => $query,
                'results' => [
                    'orders' => $orders,
                    'products' => $products,
                    'customers' => $customers,
                    'invoices' => $invoices,
                ],
            ]);
        }

        return view('admin.search.results', [
            'query' => $query,
            'orders' => $orders,
            'products' => $products,
            'customers' => $customers,
            'invoices' => $invoices,
        ]);
    }
}
