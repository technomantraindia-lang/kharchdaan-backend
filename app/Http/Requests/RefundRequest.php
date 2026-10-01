<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasPermission('payments.manage');
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|gt:0',
            'reason' => 'required|string|max:500',
        ];
    }
}
