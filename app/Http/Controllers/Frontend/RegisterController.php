<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * Show the customer registration form.
     */
    public function showRegistrationForm()
    {
        return view('frontend.auth.register');
    }

    /**
     * Register a new customer.
     */
    public function register(Request $request)
    {
        // Validate the registration form.
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
            ],
            'password' => [
                'required',
                'string',
                'min:6',
                'confirmed',
            ],
        ]);

        $customerRoleId = \App\Models\Role::where('name', 'Customer')->value('id');

        // Create the new customer in the users table.
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role_id' => $customerRoleId,
            'status' => 'active',
        ]);

        // Automatically log in the new customer.
        Auth::login($user);

        // Create a fresh secure login session.
        $request->session()->regenerate();

        return redirect()
            ->route('home')
            ->with(
                'success',
                'Registration successful! Welcome to MadhavFood.'
            );
    }
}