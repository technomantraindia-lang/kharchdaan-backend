<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectCashbackRecordsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('cashback.select');
    }

    public function rules(): array
    {
        return [
            'pool_id' => ['required', 'integer', 'exists:cashback_profit_pools,id'],
            'cashback_ids' => ['nullable', 'array'],
            'cashback_ids.*' => ['integer', 'exists:cashback_eligibilities,id'],
            'select_all' => ['nullable', 'boolean'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'member_id' => ['nullable', 'integer', 'exists:mlm_members,id'],
            'order_num' => ['nullable', 'string', 'max:100'],
            'amount_min' => ['nullable', 'regex:/^\d+(?:\.\d{1,18})?$/'],
            'amount_max' => ['nullable', 'regex:/^\d+(?:\.\d{1,18})?$/'],
            'status' => ['nullable', Rule::in(\App\Models\CashbackEligibility::STATUSES)],
        ];
    }
}
