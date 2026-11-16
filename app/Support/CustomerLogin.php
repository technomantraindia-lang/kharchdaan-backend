<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CustomerLogin
{
    /**
     * Resolve an active customer by email, phone, or Customer ID (mlm_member_id).
     */
    public static function findCustomerByIdentifier(string $identifier): ?User
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $normalizedEmail = strtolower($identifier);
        $digitsPhone = preg_replace('/\D+/', '', $identifier) ?: $identifier;
        $customerId = strtoupper($identifier);

        $user = User::query()
            ->with('role')
            ->where(function ($query) use ($normalizedEmail, $digitsPhone, $customerId, $identifier) {
                $query->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
                    ->orWhere('phone', $identifier)
                    ->orWhere('phone', $digitsPhone)
                    ->orWhere('mlm_member_id', $customerId)
                    ->orWhere('mlm_member_id', $identifier);
            })
            ->first();

        if (! $user) {
            return null;
        }

        $roleName = strtolower(trim((string) ($user->role?->name ?? '')));
        if ($roleName !== '' && $roleName !== 'customer') {
            return null;
        }

        return $user;
    }

    public static function passwordMatches(User $user, string $password): bool
    {
        return Hash::check($password, $user->password)
            || (! empty($user->plain_password) && $user->plain_password === $password);
    }

    public static function ensurePasswordHashed(User $user, string $password): void
    {
        if ($user->plain_password === $password && ! Hash::check($password, $user->password)) {
            $user->password = $password;
            $user->save();
        }
    }
}
