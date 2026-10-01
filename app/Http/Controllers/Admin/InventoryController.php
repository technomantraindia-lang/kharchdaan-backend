<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')
            ->orderByRaw('CASE WHEN stock_qty <= low_stock_qty THEN 0 ELSE 1 END')
            ->orderBy('stock_qty');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
        }

        $products = $query->paginate(20)->withQueryString();
        $lowStockCount = Product::whereColumn('stock_qty', '<=', 'low_stock_qty')->count();
        $outOfStockCount = Product::where('stock_qty', 0)->count();
        $recentLogs = InventoryLog::with(['product', 'user'])->latest()->limit(10)->get();

        return view('admin.inventory.index', compact('products', 'lowStockCount', 'outOfStockCount', 'recentLogs'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'action' => 'required|in:add,reduce,set',
            'quantity' => 'required|integer|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        $inventoryService = app(\App\Services\InventoryService::class);
        $note = $data['note'] ?? 'Manual stock adjustment via Admin';

        try {
            match ($data['action']) {
                'add' => $inventoryService->increaseStock($product, (int) $data['quantity'], $note),
                'reduce' => $inventoryService->decreaseStock($product, (int) $data['quantity'], $note),
                'set' => $inventoryService->adjustStock($product, (int) $data['quantity'], $note),
            };
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.inventory.index')->with('success', "Stock updated successfully for {$product->name}.");
    }
}
