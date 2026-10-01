<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncProductToWooCommerce;
use App\Models\Product;
use App\Models\WooCommerceSyncLog;
use App\Services\WooCommerce\OrderSyncService;
use App\Services\WooCommerce\ProductSyncService;
use Illuminate\Http\Request;

class WooCommerceSyncLogController extends Controller
{
    public function index(Request $request)
    {
        $query = WooCommerceSyncLog::query();

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('error_message', 'like', "%{$s}%")
                    ->orWhere('entity_id', 'like', "%{$s}%")
                    ->orWhere('woocommerce_id', 'like', "%{$s}%");
            });
        }

        $logs = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => WooCommerceSyncLog::count(),
            'success' => WooCommerceSyncLog::where('status', 'success')->count(),
            'failed' => WooCommerceSyncLog::where('status', 'failed')->count(),
            'pending' => WooCommerceSyncLog::whereIn('status', ['pending', 'processing'])->count(),
        ];

        return view('admin.woocommerce.sync_logs.index', compact('logs', 'stats'));
    }

    public function show(WooCommerceSyncLog $log)
    {
        return response()->json([
            'id' => $log->id,
            'entity_type' => $log->entity_type,
            'entity_id' => $log->entity_id,
            'woocommerce_id' => $log->woocommerce_id,
            'direction' => $log->direction,
            'action' => $log->action,
            'status' => $log->status,
            'request_payload' => $log->request_payload,
            'response_payload' => $log->response_payload,
            'error_message' => $log->error_message,
            'created_at' => $log->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    public function retry(WooCommerceSyncLog $log, ProductSyncService $productSync, OrderSyncService $orderSync)
    {
        if ($log->entity_type === 'product' && $log->entity_id) {
            SyncProductToWooCommerce::dispatch($log->entity_id);
            return back()->with('success', "Dispatched retry job for Product #{$log->entity_id}.");
        }

        if ($log->entity_type === 'order' && $log->woocommerce_id) {
            $orderSync->importOrderByWooCommerceId($log->woocommerce_id);
            return back()->with('success', "Retried order import for WooCommerce Order #{$log->woocommerce_id}.");
        }

        return back()->with('error', 'Automatic retry is not supported for this log type.');
    }
}
