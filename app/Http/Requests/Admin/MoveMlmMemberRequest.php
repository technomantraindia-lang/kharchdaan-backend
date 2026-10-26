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
        $user = $this->user();

        return (bool) (
            $user?->isSuperAdmin()
            || $user?->isAdmin()
            || $user?->hasPermission('mlm.move')
            || $user?->hasPermission('mlm.manage')
            || $user?->hasRole('Super Admin')
            || $user?->hasRole('super_admin')
        );
    }

    protected function prepareForValidation(): void
    {
        // If reason is empty or null, provide sensible default
        if (! $this->filled('reason') || trim((string) $this->input('reason')) === '') {
            $this->merge(['reason' => 'Administrative placement tree reorganization by Super Admin']);
        }

        // If effective_at is missing, or is ahead of server time due to client timezone/drift, default to server now()
        try {
            if (! $this->filled('effective_at') || Carbon::parse($this->input('effective_at'))->isFuture()) {
                $this->merge(['effective_at' => now()->toDateTimeString()]);
            }
        } catch (\Throwable) {
            $this->merge(['effective_at' => now()->toDateTimeString()]);
        }

        // Default move_mode to single (move only selected ID, keep blank select cell)
        if (! $this->filled('move_mode')) {
            $this->merge(['move_mode' => 'single']);
        }
    }

    public function rules(): array
    {
        return [
            'new_parent_id' => ['required', 'integer', 'exists:mlm_members,id'],
            'new_position' => ['required', Rule::in(Member::POSITIONS)],
            'move_mode' => ['nullable', 'string', Rule::in(['single', 'subtree'])],
            'reason' => ['nullable', 'string', 'max:2000'],
            'effective_at' => ['nullable'],
            'confirmed' => ['nullable'],
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
