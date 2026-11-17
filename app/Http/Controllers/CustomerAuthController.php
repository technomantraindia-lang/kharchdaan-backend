<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\CustomerLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CustomerAuthController extends Controller
{
    public function showRegister()
    {
        return view('customer.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $customerRoleId = Role::where('name', 'Customer')->value('id');

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->password = Hash::make($validated['password']);

        // Customer role
        $user->role_id = $customerRoleId;
        $user->status = 'active';
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('customer.account');
    }

    public function showLogin()
    {
        return view('customer.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = CustomerLogin::findCustomerByIdentifier($validated['login']);

        if ($user && CustomerLogin::passwordMatches($user, $validated['password'])) {
            if ($user->status !== 'active') {
                return redirect()
                    ->back()
                    ->withInput($request->only('login'))
                    ->with('error', 'Your account is not active. Please contact support.');
            }

            CustomerLogin::ensurePasswordHashed($user, $validated['password']);
            Auth::login($user, true);
            $request->session()->regenerate();

            if (! $user->terms_accepted_at) {
                return redirect()->route('terms.show');
            }

            return redirect()->route('customer.account');
        }

        return redirect()
            ->back()
            ->withInput($request->only('login'))
            ->with('error', 'Invalid Customer ID / Mobile / Email or password.');
    }

    public function account()
    {
        $user = Auth::user();

        if (! $user || ! $user->isCustomer()) {
            return redirect()->to(admin_route('dashboard'));
        }

        return view('customer.account', compact('user'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}