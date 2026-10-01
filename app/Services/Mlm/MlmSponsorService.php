<?php

namespace App\Services\Mlm;

use App\Models\Member;
use Illuminate\Validation\ValidationException;

class MlmSponsorService
{
    public function resolve(?string $customerId, ?Member $current = null, bool $rootSelected = false): ?Member
    {
        $customerId = trim((string) $customerId);

        if ($customerId === '') {
            if (! $rootSelected && Member::query()->exists()) {
                throw ValidationException::withMessages([
                    'sponsor_customer_id' => 'Select a sponsor or explicitly confirm root-member creation.',
                ]);
            }

            return null;
        }

        $sponsor = Member::query()
            ->with('user')
            ->whereHas('user', fn ($query) => $query->where('mlm_member_id', $customerId))
            ->first();

        if (! $sponsor) {
            throw ValidationException::withMessages([
                'sponsor_customer_id' => 'The selected sponsor ID does not belong to an existing member.',
            ]);
        }

        if ($current && $current->is($sponsor)) {
            throw ValidationException::withMessages([
                'sponsor_customer_id' => 'A member cannot sponsor themselves.',
            ]);
        }

        if ($sponsor->status === Member::STATUS_BLOCKED) {
            throw ValidationException::withMessages([
                'sponsor_customer_id' => 'A blocked member cannot be selected as a sponsor.',
            ]);
        }

        return $sponsor;
    }
}
