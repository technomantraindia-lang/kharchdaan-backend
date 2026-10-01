<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RtoShipment;
use App\Services\RtoService;
use Illuminate\Http\Request;

class RtoController extends Controller
{
    public function index(Request $request)
    {
        $query = RtoShipment::with(['order.user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('rto_num', 'like', "%{$s}%")
                    ->orWhere('shipment_id', 'like', "%{$s}%")
                    ->orWhereHas('order', fn ($oq) => $oq->where('order_num', 'like', "%{$s}%"));
            });
        }

        $shipments = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => RtoShipment::count(),
            'initiated' => RtoShipment::where('status', 'rto_initiated')->count(),
            'received' => RtoShipment::where('status', 'rto_received')->count(),
            'restocked' => RtoShipment::where('status', 'rto_restocked')->count(),
        ];

        return view('admin.rto.index', compact('shipments', 'stats'));
    }

    public function create(Request $request)
    {
        $orders = Order::whereNotIn('status', ['cancelled'])->latest()->take(50)->get();
        return view('admin.rto.create', compact('orders'));
    }

    public function store(Request $request, RtoService $rtoService)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'reason' => 'nullable|string|max:255',
            'shipment_id' => 'nullable|string|max:100',
        ]);

        $order = Order::findOrFail($request->order_id);
        $rto = $rtoService->createRto($order, $request->reason, $request->shipment_id);

        return redirect()->route('admin.rto.index')->with('success', "RTO #{$rto->rto_num} initiated successfully.");
    }

    public function show(RtoShipment $rto)
    {
        $rto->load(['order.items.product', 'creator']);

        return view('admin.rto.show', compact('rto'));
    }

    public function updateStatus(Request $request, RtoShipment $rto, RtoService $rtoService)
    {
        $request->validate([
            'status' => 'required|in:rto_initiated,rto_in_transit,rto_received,rto_inspected,rto_restocked,rto_damaged,rto_closed',
            'damaged_qty' => 'nullable|integer|min:0',
        ]);

        try {
            $rtoService->updateRtoStatus($rto, $request->status, (int) ($request->damaged_qty ?? 0));
            return back()->with('success', "Updated RTO #{$rto->rto_num} status to {$request->status}.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
