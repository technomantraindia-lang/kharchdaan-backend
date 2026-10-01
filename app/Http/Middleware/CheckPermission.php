<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/admin/login');
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user->hasPermission($permission)) {
            return $next($request);
        }

        abort(403, 'Unauthorized action.');
    }
}
