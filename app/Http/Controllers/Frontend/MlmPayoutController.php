<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\MlmPayoutCycle;
use App\Models\MlmPayoutLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MlmPayoutController extends Controller
{
    public function index(Request $request): View
    {
        $member = $request->user()->mlmMember;
        abort_unless($request->user()->isCustomer() && $member, 403);

        $payouts = MlmPayoutLine::query()
            ->where('member_id', $member->id)
            ->with(['cycle'])
            ->whereHas('cycle')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('frontend.mlm.payouts.index', compact('payouts'));
    }

    public function paymentProof(Request $request, MlmPayoutCycle $cycle)
    {
        $member = $request->user()->mlmMember;
        abort_unless($request->user()->isCustomer() && $member, 403);
        abort_unless($cycle->status === MlmPayoutCycle::STATUS_PAID, 404);
        abort_unless($cycle->lines()->where('member_id', $member->id)->exists(), 404);
        abort_unless($cycle->payment_proof_path && Storage::disk('local')->exists($cycle->payment_proof_path), 404);

        return Storage::disk('local')->download($cycle->payment_proof_path, 'mlm-payout-payment-proof');
    }
}
