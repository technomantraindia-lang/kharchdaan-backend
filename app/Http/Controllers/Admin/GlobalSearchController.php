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

        $isSuperAdmin = $user && ($user->isSuperAdmin() || $user->hasRole('Super Admin') || $user->hasRole('super_admin') || $user->isAdmin());
        $type = strtolower(trim((string) $request->get('type', 'all')));

        // 1. MLM Members & Distributors Search
        $members = [];
        if ($type === 'all' || $type === 'members') {
            if ($isSuperAdmin || ($user && $user->hasPermission('mlm.view'))) {
                $members = \App\Models\Member::with(['user', 'sponsor.user'])
                    ->where(function ($mq) use ($query, $like, $lowerQuery) {
                        $mq->where('id', $query)
                            ->orWhereHas('user', function ($uq) use ($query, $like, $lowerQuery) {
                                $uq->where('mlm_member_id', $like, "%{$query}%")
                                    ->orWhere('name', $like, "%{$query}%")
                                    ->orWhere('phone', $like, "%{$query}%")
                                    ->orWhere('email', $like, "%{$query}%")
                                    ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lowerQuery}%"])
                                    ->orWhereRaw('LOWER(mlm_member_id) LIKE ?', ["%{$lowerQuery}%"])
                                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$lowerQuery}%"]);
                            });
                    })
                    ->orderBy('id')
                    ->limit(15)
                    ->get()
                    ->map(function ($mem) {
                        return [
                            'id' => $mem->id,
                            'customer_id' => $mem->customer_id ?? 'Distributor',
                            'name' => $mem->user?->name ?? 'Unnamed Member',
                            'phone' => $mem->user?->phone ?? '',
                            'email' => $mem->user?->email ?? '',
                            'status' => $mem->status ?? 'active',
                            'kyc_status' => $mem->kyc_status ?? 'pending',
                            'placement_position' => $mem->placement_position ?? 'root',
                            'sponsor_name' => $mem->sponsor?->user?->name ?? 'None',
                            'url' => admin_route('mlm.members.show', $mem->id),
                        ];
                    });
            }
        }

        // 2. Orders Search
        $orders = [];
        if ($type === 'all' || $type === 'orders') {
            if ($isSuperAdmin || ($user && $user->hasPermission('orders.view'))) {
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
                    ->limit(15)
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
        }

        // 3. Products Search
        $products = [];
        if ($type === 'all' || $type === 'products') {
            if ($isSuperAdmin || ($user && $user->hasPermission('products.view'))) {
                $products = Product::where(function ($pq) use ($query, $like, $lowerQuery) {
                        $pq->where('name', $like, "%{$query}%")
                            ->orWhere('sku', $like, "%{$query}%")
                            ->orWhere('woocommerce_id', $like, "%{$query}%")
                            ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lowerQuery}%"])
                            ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$lowerQuery}%"]);
                    })
                    ->limit(15)
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
        }

        // 4. Customers Search
        $customers = [];
        if ($type === 'all' || $type === 'customers') {
            if ($isSuperAdmin || ($user && ($user->hasPermission('customers.view') || $user->hasPermission('users.view')))) {
                $customers = User::where(function ($q) use ($query, $like, $lowerQuery) {
                    $q->where('name', $like, "%{$query}%")
                        ->orWhere('email', $like, "%{$query}%")
                        ->orWhere('phone', $like, "%{$query}%")
                        ->orWhere('woocommerce_customer_id', $like, "%{$query}%")
                        ->orWhere('mlm_member_id', $like, "%{$query}%")
                        ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lowerQuery}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$lowerQuery}%"]);
                })
                ->limit(15)
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
        }

        // 5. Invoices Search
        $invoices = [];
        if ($type === 'all' || $type === 'invoices') {
            if ($isSuperAdmin || ($user && ($user->hasPermission('invoices.view') || $user->hasPermission('orders.view')))) {
                $invoices = Invoice::with('order')
                    ->where('inv_num', $like, "%{$query}%")
                    ->orWhereRaw('LOWER(inv_num) LIKE ?', ["%{$lowerQuery}%"])
                    ->limit(15)
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
        }

        if ($request->wantsJson() || $request->ajax() || $request->has('type') || $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->header('Accept') === 'application/json') {
            return response()->json([
                'success' => true,
                'query' => $query,
                'type' => $type,
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
