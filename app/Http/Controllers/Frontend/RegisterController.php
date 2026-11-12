<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Mlm\MlmMemberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function __construct(
        private readonly MlmMemberService $memberService,
    ) {}

    /**
     * Show the customer registration form.
     */
    public function showRegistrationForm()
    {
        return view('frontend.auth.register');
    }

    /**
     * Register a new customer as a real MLM member (aligned with API storefront flow).
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'referral_code' => ['nullable', 'string', 'max:40'],
            'sponsor_customer_id' => ['nullable', 'string', 'max:40'],
        ]);

        try {
            $member = $this->memberService->registerFromStorefront([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'referral_code' => $validated['referral_code'] ?? $validated['sponsor_customer_id'] ?? null,
                'sponsor_customer_id' => $validated['sponsor_customer_id'] ?? $validated['referral_code'] ?? null,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        Auth::login($member->user);
        $request->session()->regenerate();

        return redirect()
            ->route('home')
            ->with(
                'success',
                'Registration successful! Your Member ID is '.$member->customer_id.'. Welcome to KharchDaan.'
            );
    }
}
