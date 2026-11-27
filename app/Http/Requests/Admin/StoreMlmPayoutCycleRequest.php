<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMlmPayoutCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('mlm.manage');
    }

    public function rules(): array
    {
        return [
            'period_start' => ['required', 'date'],
            'frequency' => ['nullable', 'in:weekly,monthly'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
