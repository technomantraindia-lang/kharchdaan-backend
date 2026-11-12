<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Role;
use App\Models\User;
use App\Services\Mlm\MlmCustomerNetworkService;
use App\Services\Mlm\MlmMemberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
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
            'phone' => 'nullable|string|max:20',
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
        $tokenObj = $user->createToken('customer_api_token');

        return response()->json([
            'success' => true,
            'message' => 'Customer registered successfully as MLM member '.$member->customer_id.'.',
            'data' => [
                'user' => new CustomerResource($user),
                'access_token' => $tokenObj->plainTextToken,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?: 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim($request->email));
        $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password credentials.',
            ], 401);
        }

        // Verify password using Hash::check with fallback for plain_password
        $passwordMatches = Hash::check($request->password, $user->password)
            || (! empty($user->plain_password) && $user->plain_password === $request->password);

        if (! $passwordMatches) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password credentials.',
            ], 401);
        }

        // If user has plain_password match but hashed password mismatch, update to proper hash
        if ($user->plain_password === $request->password && ! Hash::check($request->password, $user->password)) {
            $user->password = $request->password;
            $user->save();
        }

        // Ensure role exists
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
        $tokenObj = $user->createToken('customer_api_token');

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => new CustomerResource($user),
                'access_token' => $tokenObj->plainTextToken,
                'token_type' => 'Bearer',
            ],
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
        ]);
    }

    /**
     * Sponsor dashboard: list users registered under the authenticated customer.
     */
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
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update($request->only(['name', 'phone']));
        $user->load(['role', 'mlmMember.sponsor.user']);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => new CustomerResource($user),
        ]);
    }
}
