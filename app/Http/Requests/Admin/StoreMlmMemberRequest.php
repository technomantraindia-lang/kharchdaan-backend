<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreMlmMemberRequest extends MlmMemberRequest
{
    public function authorize(): bool
    {
        return $this->ensureManagePermission()
            || (bool) $this->user()?->hasPermission('mlm.create');
    }

    public function rules(): array
    {
        return array_merge([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            // Accepted for compatibility with the previous form; the service
            // always generates the authoritative CYYMM0001 ID server-side.
            'member_id' => ['nullable', 'string', 'max:50', Rule::unique('users', 'mlm_member_id')],
            'joining_date' => ['nullable', 'date', 'before_or_equal:today'],
            'sponsor_customer_id' => ['nullable', 'string', 'max:50'],
            'root_member' => ['nullable', 'boolean'],
        ], $this->commonRules());
    }
}
