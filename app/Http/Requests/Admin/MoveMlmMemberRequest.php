<?php

namespace App\Http\Requests\Admin;

use App\Models\Member;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MoveMlmMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasPermission('mlm.move')
            || $this->user()?->hasPermission('mlm.manage'));
    }

    protected function prepareForValidation(): void
    {
        // If effective_at is missing, or is ahead of server time due to client timezone/drift, default to server now()
        if (! $this->filled('effective_at') || Carbon::parse($this->input('effective_at'))->isFuture()) {
            $this->merge(['effective_at' => now()->toDateTimeString()]);
        }
    }

    public function rules(): array
    {
        return [
            'new_parent_id' => ['required', 'integer', 'exists:mlm_members,id'],
            'new_position' => ['required', Rule::in(Member::POSITIONS)],
            'reason' => ['required', 'string', 'max:2000'],
            'effective_at' => ['nullable', 'date'],
            'confirmed' => ['nullable', 'boolean'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson() || $this->wantsJson() || $this->ajax() || $this->isJson()) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()->toArray(),
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}
