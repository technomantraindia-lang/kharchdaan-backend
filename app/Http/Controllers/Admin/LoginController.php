<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /**
     * Show the Admin / Sub-Admin login page.
     */
    public function showLoginForm(Request $request)
    {
        $portalType = 'unified';
        if ($request->is('super-admin*')) {
            $portalType = 'super-admin';
        } elseif ($request->is('sub-admin*')) {
            $portalType = 'sub-admin';
        }

        if (Auth::check() && Auth::user()->isAdmin()) {
            $user = Auth::user();

            // If visiting their own portal login while already authenticated, go directly to their dashboard
            if ($user->isSuperAdmin() && $portalType === 'super-admin') {
                return redirect()->route('super-admin.dashboard');
            }

            if (! $user->isSuperAdmin() && $portalType === 'sub-admin') {
                return redirect()->route('sub-admin.dashboard');
            }

            // If user explicitly visits the opposite portal login (e.g. Sub-Admin visits /super-admin/login),
            // log out previous session so they can authenticate as the requested role
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return view('admin.auth.login', compact('portalType'));
    }

    /**
     * Process Super Admin or Sub-Admin login.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = trim($validated['login']);
        $password = $validated['password'];

        $throttleKey = Str::transliterate(Str::lower($loginInput) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return redirect()
                ->back()
                ->withInput($request->only('login'))
                ->with('error', "Too many login attempts. Please try again in {$seconds} seconds.");
        }

        // Find user by email or staff_code
        $user = User::where('email', $loginInput)
            ->orWhere('staff_code', $loginInput)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return redirect()
                ->back()
                ->withInput($request->only('login'))
                ->with('error', 'Invalid Login ID or Password. Please check your credentials.');
        }

        // Check if account status is active
        if ($user->status !== 'active') {
            RateLimiter::hit($throttleKey, 60);

            return redirect()
                ->back()
                ->withInput($request->only('login'))
                ->with('error', "Your account is currently {$user->status}. Please contact the Super Admin.");
        }

        // Check if user is an Admin or Sub-Admin
        if (! $user->isAdmin()) {
            RateLimiter::hit($throttleKey, 60);

            return redirect()
                ->back()
                ->withInput($request->only('login'))
                ->with('error', 'Access denied. Only Super Admins and authorized Sub-Admins can access this portal.');
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        ActivityLogService::log('login', 'auth', "Staff member logged in ({$user->email} / {$user->role_name})", $user);

        // Explicitly route to Super Admin vs Sub-Admin URL
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.dashboard');
        }

        // Sub-Admin landing routes
        if ($user->hasPermission('dashboard.view')) {
            return redirect()->route('sub-admin.dashboard');
        } elseif ($user->hasPermission('products.view')) {
            return redirect()->route('sub-admin.products.index');
        } elseif ($user->hasPermission('orders.view')) {
            return redirect()->route('sub-admin.orders.index');
        } elseif ($user->hasPermission('mlm.view')) {
            return redirect()->route('sub-admin.mlm.members.index');
        } elseif ($user->hasPermission('cashback.view')) {
            return redirect()->route('sub-admin.cashback.index');
        } elseif ($user->hasPermission('customers.view')) {
            return redirect()->route('sub-admin.customers.index');
        }

        return redirect()->route('sub-admin.dashboard');
    }

    /**
     * Log the Admin / Sub-Admin out.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;

        if ($user) {
            ActivityLogService::log('logout', 'auth', "Staff member logged out ({$user->email})", $user);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($isSuperAdmin) {
            return redirect('/super-admin/login')->with('success', 'Super Admin logged out successfully.');
        }

        return redirect('/sub-admin/login')->with('success', 'Sub-Admin staff logged out successfully.');
    }
}