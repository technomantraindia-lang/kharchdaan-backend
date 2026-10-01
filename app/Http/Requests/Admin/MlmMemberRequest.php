<?php

namespace App\Http\Requests\Admin;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class MlmMemberRequest extends FormRequest
{
    protected function commonRules(?int $ignoreUserId = null): array
    {
        $ignoreUserId ??= $this->input('user_id') ? (int) $this->input('user_id') : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'mobile' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($ignoreUserId),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($ignoreUserId),
            ],
            'status' => ['required', Rule::in(Member::STATUSES)],
            'kyc_status' => ['nullable', Rule::in(Member::KYC_STATUSES)],
            'pan_number' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/i'],
            'aadhaar_reference' => ['nullable', 'string', 'regex:/^[0-9]{12}$/'],
            'bank_account_holder_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:34', 'regex:/^[A-Za-z0-9\-\/]{6,34}$/'],
            'ifsc_code' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'kyc_rejection_reason' => ['nullable', 'string', 'max:2000', 'required_if:kyc_status,rejected'],
            'profile_photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'cancelled_cheque' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }

    protected function ensureManagePermission(): bool
    {
        return (bool) $this->user()?->hasPermission('mlm.manage');
    }
}
