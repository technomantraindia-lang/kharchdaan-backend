<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\CustomerLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('frontend.auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = CustomerLogin::findCustomerByIdentifier($validated['login']);

        if ($user && CustomerLogin::passwordMatches($user, $validated['password'])) {
            if (strtolower(trim((string) $user->status)) !== 'active') {
                return back()->withErrors([
                    'login' => 'Your account is not active. Please contact support.',
                ])->onlyInput('login');
            }

            CustomerLogin::ensurePasswordHashed($user, $validated['password']);
            Auth::login($user, true);
            $request->session()->regenerate();

            if (! $user->terms_accepted_at) {
                return redirect()->route('terms.show');
            }

            return redirect()->route('home')->with('success', 'Logged in successfully!');
        }

        return back()->withErrors([
            'login' => 'Invalid Customer ID / Mobile / Email or password.',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Logged out successfully!');
    }
}
