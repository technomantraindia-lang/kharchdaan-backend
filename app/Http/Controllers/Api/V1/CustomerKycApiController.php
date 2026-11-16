<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\Mlm\MlmKycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerKycApiController extends Controller
{
    public function __construct(private readonly MlmKycService $kycService) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $member = Member::query()->where('user_id', $user->id)->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'No MLM member profile found for this account.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->payload($member),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $member = Member::query()->where('user_id', $user->id)->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'No MLM member profile found for this account.',
            ], 404);
        }

        $data = $request->validate([
            'pan_number' => ['required', 'string', 'max:20'],
            'aadhaar_reference' => ['required', 'string', 'max:20'],
            'bank_account_holder_name' => ['required', 'string', 'max:120'],
            'bank_account_number' => ['required', 'string', 'max:40'],
            'ifsc_code' => ['required', 'string', 'max:20'],
            'bank_name' => ['required', 'string', 'max:120'],
            'bank_branch' => ['nullable', 'string', 'max:120'],
            'cancelled_cheque' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,webp'],
        ]);

        try {
            $this->kycService->submitByCustomer(
                $member,
                $data,
                $request->file('cancelled_cheque'),
                $user
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'KYC validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        $member->refresh();

        return response()->json([
            'success' => true,
            'message' => 'KYC submitted for admin review.',
            'data' => $this->payload($member),
        ]);
    }

    private function payload(Member $member): array
    {
        return [
            'kyc_status' => $member->kyc_status ?: Member::KYC_PENDING,
            'kyc_rejection_reason' => $member->kyc_rejection_reason,
            'has_pan' => filled($member->pan_hash),
            'has_aadhaar' => filled($member->aadhaar_hash),
            'has_bank_account' => filled($member->bank_account_hash),
            'has_cancelled_cheque' => filled($member->cancelled_cheque_path),
            'bank_account_holder_name' => $member->bank_account_holder_name,
            'bank_name' => $member->bank_name,
            'bank_branch' => $member->bank_branch,
            'ifsc_code' => $member->ifsc_code,
            'kyc_verified_at' => $member->kyc_verified_at?->toIso8601String(),
        ];
    }
}
