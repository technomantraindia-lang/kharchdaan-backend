<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerAuthApiController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?: 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Resolve Customer role
        $customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);

        $user = new User();
        $user->name = trim($request->name);
        $user->email = strtolower(trim($request->email));
        $user->password = $request->password; // Handled by Eloquent hashed cast or direct
        $user->plain_password = $request->password;
        $user->phone = $request->phone;
        $user->role_id = $customerRole->id;
        $user->status = 'active';
        $user->save();

        $tokenObj = $user->createToken('customer_api_token');

        return response()->json([
            'success' => true,
            'message' => 'Customer registered successfully.',
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
        return response()->json([
            'success' => true,
            'data' => new CustomerResource(auth()->user()),
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

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => new CustomerResource($user),
        ]);
    }
}
