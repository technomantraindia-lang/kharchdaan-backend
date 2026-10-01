<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated API access. Token required.',
            ], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken || ! $accessToken->tokenable) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired API token.',
            ], 401);
        }

        $user = $accessToken->tokenable;
        $user->withAccessToken($accessToken);

        auth()->setUser($user);
        $accessToken->update(['last_used_at' => now()]);

        return $next($request);
    }
}
