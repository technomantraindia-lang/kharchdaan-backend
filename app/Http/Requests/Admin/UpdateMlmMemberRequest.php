<?php

namespace App\Http\Requests\Admin;

use App\Models\Member;

class UpdateMlmMemberRequest extends MlmMemberRequest
{
    public function authorize(): bool
    {
        return $this->ensureManagePermission()
            || (bool) $this->user()?->hasPermission('mlm.edit');
    }

    public function rules(): array
    {
        $member = $this->route('member');
        $ignoreUserId = $member instanceof Member ? $member->user_id : null;

        return array_merge([
            'mobile_change_reason' => ['nullable', 'string', 'max:1000'],
        ], $this->commonRules($ignoreUserId));
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $member = $this->route('member');

            if (! $member instanceof Member || ! $member->user) {
                return;
            }

            if (trim((string) $member->user->phone) !== trim((string) $this->input('mobile'))
                && trim((string) $this->input('mobile_change_reason')) === '') {
                $validator->errors()->add(
                    'mobile_change_reason',
                    'A reason is required when changing the mobile number.'
                );
            }
        });
    }
}
