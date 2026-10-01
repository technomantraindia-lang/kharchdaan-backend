<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Show customer login form.
     */
    public function showLoginForm()
    {
        return view('frontend.auth.login');
    }

    /**
     * Handle customer login post request.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($validated)) {
            $user = Auth::user();
            if ($user && $user->role && $user->role->name === 'Customer' && $user->status === 'active') {
                $request->session()->regenerate();
                return redirect()->route('home')->with('success', 'Logged in successfully!');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Handle customer logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Logged out successfully!');
    }
}
