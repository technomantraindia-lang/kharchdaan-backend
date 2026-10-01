<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderReturn;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = OrderReturn::with(['order', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('return_num', 'like', "%{$s}%")
                    ->orWhereHas('order', fn ($oq) => $oq->where('order_num', 'like', "%{$s}%"));
            });
        }

        $returns = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => OrderReturn::count(),
            'requested' => OrderReturn::where('status', 'requested')->count(),
            'inspected' => OrderReturn::where('status', 'inspected')->count(),
            'completed' => OrderReturn::where('status', 'completed')->count(),
        ];

        return view('admin.returns.index', compact('returns', 'stats'));
    }

    public function show(OrderReturn $return)
    {
        $return->load(['order', 'user', 'items.product']);

        return view('admin.returns.show', compact('return'));
    }

    public function updateStatus(Request $request, OrderReturn $return, ReturnService $returnService)
    {
        $request->validate([
            'status' => 'required|in:requested,approved,rejected,pickup_scheduled,received,inspected,completed',
            'admin_note' => 'nullable|string|max:500',
        ]);

        try {
            $returnService->updateStatus($return, $request->status, $request->admin_note);
            return back()->with('success', "Updated Return #{$return->return_num} status to {$request->status}.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
