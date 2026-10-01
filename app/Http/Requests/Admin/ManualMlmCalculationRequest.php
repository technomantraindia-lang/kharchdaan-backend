<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ManualMlmCalculationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('mlm.manage');
    }

    public function rules(): array
    {
        return [
            'purchasing_member_id' => ['required', 'integer', 'exists:mlm_members,id'],
            'eligible_amount' => ['required', 'string', 'regex:/^\d+(?:\.\d{1,18})?$/'],
            'transaction_reference' => ['required', 'string', 'max:150'],
            'transaction_date' => ['required', 'date'],
            'rule_version_id' => ['required', 'integer', 'exists:mlm_calculation_rules,id'],
            'confirmed' => ['nullable', 'boolean'],
        ];
    }
}
