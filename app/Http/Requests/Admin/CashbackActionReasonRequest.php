<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CashbackActionReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasPermission('cashback.hold')
            || $this->user()?->hasPermission('cashback.reverse'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
