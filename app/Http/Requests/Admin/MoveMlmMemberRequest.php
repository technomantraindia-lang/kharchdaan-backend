<?php

namespace App\Http\Requests\Admin;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveMlmMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasPermission('mlm.move')
            || $this->user()?->hasPermission('mlm.manage'));
    }

    public function rules(): array
    {
        return [
            'new_parent_id' => ['required', 'integer', 'exists:mlm_members,id'],
            'new_position' => ['required', Rule::in(Member::POSITIONS)],
            'reason' => ['required', 'string', 'max:2000'],
            'effective_at' => ['required', 'date', 'before_or_equal:now'],
            'confirmed' => ['nullable', 'boolean'],
        ];
    }
}
