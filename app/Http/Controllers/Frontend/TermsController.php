<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TermsController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('frontend.auth.terms', [
            'user' => $user,
            'alreadyAccepted' => (bool) $user?->terms_accepted_at,
        ]);
    }

    public function accept(Request $request)
    {
        $request->validate([
            'accept_terms' => ['accepted'],
        ], [
            'accept_terms.accepted' => 'You must tick and accept the Terms & Conditions to continue.',
        ]);

        $user = $request->user();
        abort_unless($user && $user->isCustomer(), 403);

        if (! $user->terms_accepted_at) {
            $user->forceFill(['terms_accepted_at' => now()])->save();
        }

        return redirect()
            ->route('account')
            ->with('success', 'Thank you. Terms & Conditions accepted.');
    }
}
