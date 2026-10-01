<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CashbackEligibility;
use App\Models\CashbackPayoutBatchItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class CashbackController extends Controller
{
    public function index(Request $request)
    {
        $member = $request->user()->mlmMember;
        abort_unless($member, 403);

        $cashbacks = CashbackEligibility::query()
            ->where('member_id', $member->id)
            ->with(['order', 'payoutBatchItems.batch'])
            ->latest('eligibility_date')
            ->paginate(15)
            ->withQueryString();

        return view('frontend.cashback.index', compact('cashbacks'));
    }

    public function show(Request $request, CashbackEligibility $cashback)
    {
        Gate::authorize('view', $cashback);

        return view('frontend.cashback.show', [
            'cashback' => $cashback->load(['order', 'payoutBatchItems.batch']),
        ]);
    }

    public function paymentProof(Request $request, CashbackEligibility $cashback)
    {
        Gate::authorize('view', $cashback);

        abort_unless($cashback->status === CashbackEligibility::STATUS_PAID, 404);
        $item = $cashback->payoutBatchItems()->with('batch')->first();
        $path = $item?->batch?->payment_proof_path;
        abort_unless($path, 404);

        return Storage::disk('local')->download($path, 'cashback-payment-proof');
    }
}
