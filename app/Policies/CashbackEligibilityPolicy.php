<?php

namespace App\Policies;

use App\Models\CashbackEligibility;
use App\Models\User;

class CashbackEligibilityPolicy
{
    public function view(User $user, CashbackEligibility $cashback): bool
    {
        if ($user->isAdmin() && $user->hasPermission('cashback.view')) {
            return true;
        }

        return $user->isCustomer()
            && (int) $user->mlmMember?->id === (int) $cashback->member_id;
    }
}
