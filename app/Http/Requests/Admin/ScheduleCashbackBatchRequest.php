<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleCashbackBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('cashback.schedule');
    }

    public function rules(): array
    {
        return [
            'scheduled_payment_date' => ['required', 'date'],
        ];
    }
}
