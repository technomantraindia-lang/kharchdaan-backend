<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashbackProfitPoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('cashback.pool.manage');
    }

    public function rules(): array
    {
        return [
            'pool_reference' => ['required', 'string', 'max:100', 'unique:cashback_profit_pools,pool_reference'],
            'approved_available_amount' => ['required', 'regex:/^\d+(?:\.\d{1,18})?$/'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
