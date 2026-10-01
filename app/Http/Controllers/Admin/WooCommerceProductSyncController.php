<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncProductToWooCommerce;
use App\Models\Product;
use App\Models\WooCommerceSyncLog;
use App\Services\WooCommerce\ProductSyncService;
use Illuminate\Http\Request;

class WooCommerceProductSyncController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
        }

        if ($request->filled('sync_status')) {
            $status = $request->sync_status;
            if ($status === 'unsynced') {
                $query->whereNull('woocommerce_id');
            } else {
                $query->where('woocommerce_sync_status', $status);
            }
        }

        $products = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => Product::count(),
            'synced' => Product::where('woocommerce_sync_status', 'synced')->count(),
            'pending' => Product::where('woocommerce_sync_status', 'pending')->orWhereNull('woocommerce_sync_status')->count(),
            'failed' => Product::where('woocommerce_sync_status', 'failed')->count(),
        ];

        return view('admin.woocommerce.products', compact('products', 'stats'));
    }

    public function syncSingle(Product $product, ProductSyncService $syncService)
    {
        // For single product manual trigger, dispatch job or sync immediately if configured
        SyncProductToWooCommerce::dispatch($product->id);

        return back()->with('success', "Dispatched WooCommerce synchronization job for product {$product->name}.");
    }

    public function bulkSync(Request $request, ProductSyncService $syncService)
    {
        $action = $request->input('action');
        $selectedIds = $request->input('selected_ids', []);

        if ($action === 'sync_selected') {
            if (empty($selectedIds)) {
                return back()->with('error', 'Please select at least one product.');
            }
            $result = $syncService->syncSelectedProducts($selectedIds);
            return back()->with('success', $result['message']);
        }

        if ($action === 'sync_unsynced') {
            $ids = Product::whereNull('woocommerce_id')->orWhere('woocommerce_sync_status', '!=', 'synced')->pluck('id')->toArray();
            if (empty($ids)) {
                return back()->with('info', 'All products are already synchronized.');
            }
            $result = $syncService->syncSelectedProducts($ids);
            return back()->with('success', $result['message']);
        }

        if ($action === 'retry_failed') {
            $ids = Product::where('woocommerce_sync_status', 'failed')->pluck('id')->toArray();
            if (empty($ids)) {
                return back()->with('info', 'No failed product synchronizations found.');
            }
            $result = $syncService->syncSelectedProducts($ids);
            return back()->with('success', $result['message']);
        }

        if ($action === 'sync_all') {
            $result = $syncService->syncAllProducts();
            return back()->with('success', $result['message']);
        }

        return back()->with('error', 'Invalid action selected.');
    }

    public function showError(Product $product)
    {
        $log = WooCommerceSyncLog::where('entity_type', 'product')
            ->where('entity_id', $product->id)
            ->where('status', 'failed')
            ->latest()
            ->first();

        return response()->json([
            'product_name' => $product->name,
            'error' => $log ? $log->error_message : 'No detailed log available for this failure.',
            'logged_at' => $log ? $log->created_at->format('Y-m-d H:i:s') : null,
        ]);
    }
}
