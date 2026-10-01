<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MarkMlmPayoutPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('mlm.manage');
    }

    public function rules(): array
    {
        return [
            'payment_reference' => ['required', 'string', 'max:150'],
            'payment_date' => ['required', 'date'],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
