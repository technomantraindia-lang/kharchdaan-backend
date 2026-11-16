<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerTermsAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isCustomer()) {
            return $next($request);
        }

        if ($user->terms_accepted_at) {
            return $next($request);
        }

        if ($request->routeIs('terms.*') || $request->routeIs('logout') || $request->routeIs('login*')) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Please accept Terms & Conditions before continuing.',
                'code' => 'terms_required',
                'terms_required' => true,
            ], 403);
        }

        return redirect()->route('terms.show');
    }
}
