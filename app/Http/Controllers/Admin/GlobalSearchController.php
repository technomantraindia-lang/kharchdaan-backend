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
                    'members' => [],
                    'orders' => [],
                    'products' => [],
                    'customers' => [],
                    'invoices' => [],
                ],
            ]);
        }

        $user = auth()->user();
        $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
        $like = $isPgsql ? 'ilike' : 'like';
        $lowerQuery = strtolower($query);

        // 1. MLM Members & Distributors Search
        $members = [];
        if ($user && ($user->hasPermission('mlm.view') || $user->hasRole('Super Admin'))) {
            $members = \App\Models\Member::with('user')
                ->whereHas('user', function ($uq) use ($query, $like, $lowerQuery) {
                    $uq->where('mlm_member_id', $like, "%{$query}%")
                        ->orWhere('name', $like, "%{$query}%")
                        ->orWhere('phone', $like, "%{$query}%")
                        ->orWhere('email', $like, "%{$query}%")
                        ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lowerQuery}%"])
                        ->orWhereRaw('LOWER(mlm_member_id) LIKE ?', ["%{$lowerQuery}%"]);
                })
                ->limit(10)
                ->get()
                ->map(function ($mem) {
                    return [
                        'id' => $mem->id,
                        'customer_id' => $mem->customer_id ?? 'Distributor',
                        'name' => $mem->user?->name ?? 'Unnamed Member',
                        'phone' => $mem->user?->phone ?? '',
                        'status' => $mem->status,
                        'url' => admin_route('mlm.members.show', $mem->id),
                    ];
                });
        }

        // 2. Orders Search
        $orders = [];
        if ($user && ($user->hasPermission('orders.view') || $user->hasRole('Super Admin'))) {
            $orders = Order::with('user')
                ->where(function ($q) use ($query, $like, $lowerQuery) {
                    $q->where('order_num', $like, "%{$query}%")
                        ->orWhere('woocommerce_id', $like, "%{$query}%")
                        ->orWhere('tracking_num', $like, "%{$query}%")
                        ->orWhereRaw('LOWER(order_num) LIKE ?', ["%{$lowerQuery}%"])
                        ->orWhereHas('user', function ($uq) use ($query, $like, $lowerQuery) {
                            $uq->where('name', $like, "%{$query}%")
                                ->orWhere('email', $like, "%{$query}%")
                                ->orWhere('phone', $like, "%{$query}%")
                                ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lowerQuery}%"]);
                        })
                        ->orWhereHas('items', function ($iq) use ($query, $like, $lowerQuery) {
                            $iq->where('sku', $like, "%{$query}%")
                                ->orWhere('product_name', $like, "%{$query}%")
                                ->orWhereRaw('LOWER(product_name) LIKE ?', ["%{$lowerQuery}%"]);
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
                        'url' => admin_route('orders.show', $order->id),
                    ];
                });
        }

        // 3. Products Search
        $products = [];
        if ($user && ($user->hasPermission('products.view') || $user->hasRole('Super Admin'))) {
            $products = Product::where('name', $like, "%{$query}%")
                ->orWhere('sku', $like, "%{$query}%")
                ->orWhere('woocommerce_id', $like, "%{$query}%")
                ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lowerQuery}%"])
                ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$lowerQuery}%"])
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
                        'url' => admin_route('products.show', $prod->id),
                    ];
                });
        }

        // 4. Customers Search
        $customers = [];
        if ($user && ($user->hasPermission('customers.view') || $user->hasPermission('users.view') || $user->hasRole('Super Admin'))) {
            $customers = User::where(function ($q) use ($query, $like, $lowerQuery) {
                $q->where('name', $like, "%{$query}%")
                    ->orWhere('email', $like, "%{$query}%")
                    ->orWhere('phone', $like, "%{$query}%")
                    ->orWhere('woocommerce_customer_id', $like, "%{$query}%")
                    ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lowerQuery}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$lowerQuery}%"]);
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
                    'url' => admin_route('customers.show', $cust->id),
                ];
            });
        }

        // 5. Invoices Search
        $invoices = [];
        if ($user && ($user->hasPermission('invoices.view') || $user->hasPermission('orders.view') || $user->hasRole('Super Admin'))) {
            $invoices = Invoice::with('order')
                ->where('inv_num', $like, "%{$query}%")
                ->orWhereRaw('LOWER(inv_num) LIKE ?', ["%{$lowerQuery}%"])
                ->limit(10)
                ->get()
                ->map(function ($inv) {
                    return [
                        'id' => $inv->id,
                        'inv_num' => $inv->inv_num,
                        'order_num' => $inv->order?->order_num,
                        'url' => admin_route('invoices.show', $inv->id),
                    ];
                });
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'query' => $query,
                'results' => [
                    'members' => $members,
                    'orders' => $orders,
                    'products' => $products,
                    'customers' => $customers,
                    'invoices' => $invoices,
                ],
            ]);
        }

        return view('admin.search.results', [
            'query' => $query,
            'members' => $members,
            'orders' => $orders,
            'products' => $products,
            'customers' => $customers,
            'invoices' => $invoices,
        ]);
    }
}
