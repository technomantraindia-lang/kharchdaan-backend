<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Mlm\MlmOrderIntegrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MlmReconciliationController extends Controller
{
    public function __construct(private readonly MlmOrderIntegrationService $integrationService) {}

    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['user.mlmMember', 'payment', 'refunds', 'cashbackEligibility', 'mlmCalculationRuns.ruleVersion'])
            ->when($request->filled('status'), fn ($query) => $query->where('mlm_processing_status', $request->input('status')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('date_to')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->input('search'));
                $query->where(function ($nested) use ($search): void {
                    $nested->where('order_num', 'like', "%{$search}%")
                        ->orWhere('mlm_integration_reference', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('mlm_member_id', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.mlm.reconciliation.index', [
            'orders' => $orders,
            'statuses' => [
                Order::MLM_NOT_STARTED,
                Order::MLM_WAITING_PAYMENT,
                Order::MLM_PROCESSING,
                Order::MLM_COMPLETED,
                Order::MLM_NOT_ELIGIBLE,
                Order::MLM_FAILED,
                Order::MLM_REVERSED,
            ],
        ]);
    }

    public function retry(Order $order): RedirectResponse
    {
        $this->integrationService->retry($order);

        return back()->with('success', "MLM processing retried for order {$order->order_num}.");
    }
}
