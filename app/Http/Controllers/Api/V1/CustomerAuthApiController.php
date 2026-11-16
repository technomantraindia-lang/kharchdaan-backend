<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Role;
use App\Models\User;
use App\Services\Mlm\MlmCustomerNetworkService;
use App\Services\Mlm\MlmMemberService;
use App\Support\CustomerLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerAuthApiController extends Controller
{
    public function __construct(
        private readonly MlmMemberService $memberService,
        private readonly MlmCustomerNetworkService $networkService,
    ) {}

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')],
            'referral_code' => 'nullable|string|max:40',
            'sponsor_customer_id' => 'nullable|string|max:40',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?: 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $member = $this->memberService->registerFromStorefront([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'phone' => $request->input('phone'),
                'referral_code' => $request->input('referral_code') ?: $request->input('sponsor_customer_id'),
                'sponsor_customer_id' => $request->input('sponsor_customer_id') ?: $request->input('referral_code'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Registration failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = $member->user()->with(['role', 'mlmMember.sponsor.user'])->first();
        $tokenObj = $this->issueCustomerToken($user);

        return response()->json([
            'success' => true,
            'message' => 'Customer registered successfully as MLM member '.$member->customer_id.'.',
            'data' => [
                'user' => new CustomerResource($user),
                'access_token' => $tokenObj->plainTextToken,
                'token_type' => 'Bearer',
                'terms_required' => ! $user->terms_accepted_at,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?: 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $identifier = trim((string) ($request->input('login') ?: $request->input('email') ?: ''));
        if ($identifier === '') {
            return response()->json([
                'success' => false,
                'message' => 'Please enter Customer ID, Mobile, or Email.',
                'errors' => ['login' => ['Please enter Customer ID, Mobile, or Email.']],
            ], 422);
        }

        $user = CustomerLogin::findCustomerByIdentifier($identifier);

        if (! $user || ! CustomerLogin::passwordMatches($user, $request->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Customer ID / Mobile / Email or password.',
            ], 401);
        }

        CustomerLogin::ensurePasswordHashed($user, $request->password);

        if (! $user->role_id) {
            $customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);
            $user->role_id = $customerRole->id;
            $user->save();
        }

        if ($user->status && strtolower(trim($user->status)) !== 'active') {
            return response()->json([
                'success' => false,
                'message' => "Account is currently {$user->status}. Please contact support.",
            ], 403);
        }

        $user->load(['role', 'mlmMember.sponsor.user']);
        $tokenObj = $this->issueCustomerToken($user);

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => new CustomerResource($user),
                'access_token' => $tokenObj->plainTextToken,
                'token_type' => 'Bearer',
                'terms_required' => ! $user->terms_accepted_at,
            ],
        ]);
    }

    private function issueCustomerToken(User $user): object
    {
        $tokenObj = $user->createToken('customer_api_token');
        $minutes = (int) config('auth.api_token_expiration', 525600);
        if ($minutes > 0 && isset($tokenObj->accessToken)) {
            $tokenObj->accessToken->forceFill([
                'expires_at' => now()->addMinutes($minutes),
            ])->save();
        }

        return $tokenObj;
    }

    public function acceptTerms(Request $request)
    {
        $user = auth()->user();
        abort_unless($user && $user->isCustomer(), 403);

        $validator = Validator::make($request->all(), [
            'accept_terms' => ['accepted'],
        ], [
            'accept_terms.accepted' => 'You must accept the Terms & Conditions to continue.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! $user->terms_accepted_at) {
            $user->forceFill(['terms_accepted_at' => now()])->save();
        }

        $user->load(['role', 'mlmMember.sponsor.user']);

        return response()->json([
            'success' => true,
            'message' => 'Terms & Conditions accepted.',
            'data' => new CustomerResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $user = auth()->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully. Token revoked.',
        ]);
    }

    public function me(Request $request)
    {
        $user = auth()->user();
        $user?->load(['role', 'mlmMember.sponsor.user', 'mlmMember.sponsoredMembers']);

        return response()->json([
            'success' => true,
            'data' => new CustomerResource($user),
            'terms_required' => $user ? ! $user->terms_accepted_at : false,
        ]);
    }

    public function myReferrals(Request $request)
    {
        $user = auth()->user();

        if (! $user?->mlmMember) {
            return response()->json([
                'success' => false,
                'message' => 'No MLM membership is linked to this account yet.',
                'data' => [
                    'sponsor' => null,
                    'direct_referral_count' => 0,
                    'referrals' => [],
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->networkService->directReferrals($user),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Phone changes should remain admin-controlled when possible; still enforce uniqueness if sent.
        $data = $request->only(['name']);
        if ($request->filled('phone') && $request->input('phone') !== $user->phone) {
            return response()->json([
                'success' => false,
                'message' => 'Mobile number can only be changed by admin on request.',
                'errors' => ['phone' => ['Mobile number can only be changed by admin on request.']],
            ], 422);
        }

        $user->update($data);
        $user->load(['role', 'mlmMember.sponsor.user']);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => new CustomerResource($user),
        ]);
    }
}
