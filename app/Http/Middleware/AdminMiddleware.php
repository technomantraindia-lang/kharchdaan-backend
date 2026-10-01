<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            if ($request->is('super-admin*')) {
                return redirect('/super-admin/login');
            }
            if ($request->is('sub-admin*')) {
                return redirect('/sub-admin/login');
            }
            return redirect('/login');
        }

        $user = auth()->user();

        if (! $user->isAdmin()) {
            abort(403, 'Unauthorized access to administration portal.');
        }

        // Only redirect GET requests to ensure proper browser address bar display
        if ($request->isMethod('GET')) {
            // 1. Sub-Admins cannot access the /super-admin portal -> redirect to /sub-admin
            if ($request->is('super-admin*') && ! $user->isSuperAdmin()) {
                $path = preg_replace('#^super-admin/?#', '', $request->path());
                $target = $path ? '/sub-admin/' . $path : '/sub-admin/dashboard';
                $query = $request->getQueryString();
                if ($query) {
                    $target .= '?' . $query;
                }
                return redirect($target)->with('error', 'Access restricted: Redirected to your authorized Sub-Admin staff portal.');
            }

            // 2. Super Admins accessing /sub-admin portal -> redirect to /super-admin
            if ($request->is('sub-admin*') && $user->isSuperAdmin()) {
                $path = preg_replace('#^sub-admin/?#', '', $request->path());
                $target = $path ? '/super-admin/' . $path : '/super-admin/dashboard';
                $query = $request->getQueryString();
                if ($query) {
                    $target .= '?' . $query;
                }
                return redirect($target);
            }

            // 3. Any /admin/* route -> redirect to the designated /super-admin/* or /sub-admin/* URL
            if ($request->is('admin*')) {
                $prefix = $user->isSuperAdmin() ? 'super-admin' : 'sub-admin';
                $path = preg_replace('#^admin/?#', '', $request->path());
                $target = $path ? '/' . $prefix . '/' . $path : '/' . $prefix . '/dashboard';
                $query = $request->getQueryString();
                if ($query) {
                    $target .= '?' . $query;
                }
                return redirect($target);
            }
        } else {
            // For non-GET requests (POST, PUT, DELETE), enforce strict security check
            if ($request->is('super-admin*') && ! $user->isSuperAdmin()) {
                abort(403, 'Access restricted: Only Super Admins can execute Super Admin Master operations.');
            }
        }

        return $next($request);
    }
}
