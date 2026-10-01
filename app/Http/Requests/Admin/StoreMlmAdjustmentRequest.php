<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMlmAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('mlm.manage');
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:mlm_members,id'],
            'eligible_amount' => ['required', 'regex:/^-?\d+(?:\.\d{1,18})?$/'],
            'pv' => ['required', 'regex:/^-?\d+(?:\.\d{1,18})?$/'],
            'rate' => ['required', 'regex:/^-?\d+(?:\.\d{1,18})?$/'],
            'calculated_amount' => ['required', 'regex:/^-?\d+(?:\.\d{1,18})?$/'],
            'level' => ['required', 'integer', 'between:0,19'],
            'transaction_reference' => ['required', 'string', 'max:150'],
            'transaction_date' => ['required', 'date'],
            'rule_version_id' => ['required', 'integer', 'exists:mlm_calculation_rules,id'],
        ];
    }
}
