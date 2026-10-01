<?php

namespace App\Services\Mlm;

use App\Models\Member;
use App\Models\MlmKycHistory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class MlmKycService
{
    public function save(Member $member, array $data, ?UploadedFile $cancelledCheque, User $admin): void
    {
        $oldStatus = $member->kyc_status ?: Member::KYC_PENDING;
        $oldChequePath = $member->cancelled_cheque_path;
        $attributes = [];

        $this->applyEncryptedValue($member, $data, 'pan_number', 'pan_hash', $attributes, '/^[A-Z]{5}[0-9]{4}[A-Z]$/i', 'PAN');
        $this->applyEncryptedValue($member, $data, 'aadhaar_reference', 'aadhaar_hash', $attributes, '/^[0-9]{12}$/', 'Aadhaar reference');
        $this->applyEncryptedValue($member, $data, 'bank_account_number', 'bank_account_hash', $attributes, '/^[A-Za-z0-9\-\/]{6,34}$/', 'Bank account number');

        foreach ([
            'bank_account_holder_name',
            'ifsc_code',
            'bank_name',
            'bank_branch',
        ] as $field) {
            if (array_key_exists($field, $data) && trim((string) $data[$field]) !== '') {
                $attributes[$field] = $field === 'ifsc_code'
                    ? strtoupper(trim((string) $data[$field]))
                    : trim((string) $data[$field]);
            }
        }

        if (array_key_exists('kyc_status', $data) && $data['kyc_status']) {
            $attributes['kyc_status'] = $data['kyc_status'];
        }

        $newStatus = $attributes['kyc_status'] ?? $oldStatus;
        $attributes['kyc_rejection_reason'] = $newStatus === Member::KYC_REJECTED
            ? ($data['kyc_rejection_reason'] ?? $member->kyc_rejection_reason)
            : null;

        if ($newStatus === Member::KYC_APPROVED) {
            $attributes['kyc_verified_by'] = $admin->id;
            $attributes['kyc_verified_at'] = now();
        } elseif ($newStatus !== $oldStatus || $newStatus !== Member::KYC_APPROVED) {
            $attributes['kyc_verified_by'] = null;
            $attributes['kyc_verified_at'] = null;
        }

        if ($cancelledCheque) {
            $attributes['cancelled_cheque_path'] = $cancelledCheque->store('mlm/kyc', 'local');
        }

        $member->fill($attributes);
        $member->save();

        $newChequePath = $member->cancelled_cheque_path;
        $changed = $oldStatus !== $newStatus
            || $oldChequePath !== $newChequePath
            || $this->sensitiveOrBankDataChanged($attributes);

        if ($changed) {
            MlmKycHistory::create([
                'member_id' => $member->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'rejection_reason' => $member->kyc_rejection_reason,
                'cancelled_cheque_path' => $oldChequePath,
                'changed_by' => $admin->id,
                'changed_at' => now(),
            ]);
        }
    }

    public function review(Member $member, string $status, ?string $reason, User $admin): void
    {
        if ($status === Member::KYC_REJECTED && trim((string) $reason) === '') {
            throw ValidationException::withMessages([
                'kyc_rejection_reason' => 'A rejection reason is required.',
            ]);
        }

        $this->save($member, [
            'kyc_status' => $status,
            'kyc_rejection_reason' => $reason,
        ], null, $admin);
    }

    private function applyEncryptedValue(
        Member $member,
        array $data,
        string $field,
        string $hashField,
        array &$attributes,
        string $pattern,
        string $label
    ): void {
        if (! array_key_exists($field, $data) || trim((string) $data[$field]) === '') {
            return;
        }

        $value = trim((string) $data[$field]);

        if (! preg_match($pattern, $value)) {
            throw ValidationException::withMessages([
                $field => "The {$label} format is invalid.",
            ]);
        }

        $normalized = strtoupper($value);
        $hash = hash_hmac('sha256', $normalized, (string) config('app.key'));
        $duplicate = Member::query()
            ->where($hashField, $hash)
            ->whereKeyNot($member->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                $field => "This {$label} is already assigned to another member.",
            ]);
        }

        $attributes[$field] = $normalized;
        $attributes[$hashField] = $hash;
    }

    private function sensitiveOrBankDataChanged(array $attributes): bool
    {
        return array_key_exists('pan_number', $attributes)
            || array_key_exists('aadhaar_reference', $attributes)
            || array_key_exists('bank_account_number', $attributes)
            || array_key_exists('bank_account_holder_name', $attributes)
            || array_key_exists('ifsc_code', $attributes)
            || array_key_exists('bank_name', $attributes)
            || array_key_exists('bank_branch', $attributes);
    }
}
