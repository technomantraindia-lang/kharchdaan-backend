<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\WooCommerceSyncConflict;
use App\Services\ActivityLogService;
use App\Services\WooCommerce\InventorySyncService;
use App\Services\WooCommerce\ProductSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WooCommerceSyncConflictController extends Controller
{
    public function index(Request $request)
    {
        $query = WooCommerceSyncConflict::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'open');
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        $conflicts = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'open' => WooCommerceSyncConflict::where('status', 'open')->count(),
            'resolved' => WooCommerceSyncConflict::where('status', 'resolved')->count(),
            'ignored' => WooCommerceSyncConflict::where('status', 'ignored')->count(),
        ];

        return view('admin.woocommerce.conflicts.index', compact('conflicts', 'stats'));
    }

    public function resolve(
        Request $request,
        WooCommerceSyncConflict $conflict,
        ProductSyncService $productSync,
        InventorySyncService $invSync,
        \App\Services\InventoryService $inventoryService
    ) {
        $request->validate([
            'resolution' => 'required|in:use_laravel,use_woocommerce,ignore,manual',
        ]);

        $resolution = $request->resolution;

        if ($resolution === 'use_laravel') {
            if ($conflict->entity_type === 'product' && $conflict->entity_id) {
                $product = Product::find($conflict->entity_id);
                if ($product) {
                    \App\Jobs\SyncProductToWooCommerce::dispatch($product->id);
                }
            } elseif ($conflict->entity_type === 'inventory' && $conflict->entity_id) {
                $product = Product::find($conflict->entity_id);
                if ($product) {
                    $invSync->dispatchInventorySync($product);
                }
            }
        } elseif ($resolution === 'use_woocommerce') {
            if ($conflict->entity_type === 'inventory' && $conflict->entity_id) {
                $product = Product::find($conflict->entity_id);
                if ($product && is_numeric($conflict->woocommerce_value)) {
                    $inventoryService->adjustStock(
                        $product,
                        (int) $conflict->woocommerce_value,
                        "WooCommerce sync conflict resolution (Conflict #{$conflict->id})",
                        $conflict
                    );
                }
            }
        }

        $status = $resolution === 'ignore' ? 'ignored' : 'resolved';

        $conflict->update([
            'status' => $status,
            'resolution' => $resolution,
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);

        ActivityLogService::log(
            'conflict_resolved',
            'woocommerce',
            "Resolved sync conflict #{$conflict->id} using resolution '{$resolution}'"
        );

        return back()->with('success', "Sync conflict #{$conflict->id} updated successfully ({$resolution}).");
    }
}
