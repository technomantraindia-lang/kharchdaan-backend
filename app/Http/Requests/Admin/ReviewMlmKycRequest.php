<?php

namespace App\Http\Requests\Admin;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewMlmKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasPermission('mlm.manage')
            || $this->user()?->hasPermission('mlm.kyc.review'));
    }

    public function rules(): array
    {
        return [
            'kyc_status' => ['required', Rule::in(Member::KYC_STATUSES)],
            'kyc_rejection_reason' => ['nullable', 'string', 'max:2000', 'required_if:kyc_status,rejected'],
        ];
    }
}
