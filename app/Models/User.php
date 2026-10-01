<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'staff_code', 'password', 'plain_password', 'phone', 'role_id', 'status', 'woocommerce_customer_id', 'mlm_member_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, \App\Traits\HasApiTokens;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_has_permissions', 'user_id', 'permission_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function mlmMember(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function getRoleNameAttribute(): ?string
    {
        return $this->role?->name ?? Role::where('id', $this->role_id)->value('name');
    }

    public function isSuperAdmin(): bool
    {
        if (strtolower(trim((string) $this->status)) !== 'active') {
            return false;
        }

        $roleName = strtolower(trim((string) $this->role_name));

        return in_array($roleName, ['super admin', 'super_admin', 'superadmin', 'super administrator', 'super_administrator'], true)
            || ($this->role && in_array(strtolower(trim((string) $this->role->name)), ['super admin', 'super_admin', 'superadmin', 'super administrator', 'super_administrator'], true));
    }

    public function isAdmin(): bool
    {
        if (strtolower(trim((string) $this->status)) !== 'active') {
            return false;
        }

        $roleName = strtolower(trim((string) $this->role_name));

        return in_array($roleName, ['super admin', 'super_admin', 'superadmin', 'super administrator', 'super_administrator', 'admin', 'sub admin', 'sub_admin', 'subadmin', 'sub administrator', 'sub_administrator', 'store manager', 'accounts', 'manager', 'staff'], true)
            || ($this->role && ! in_array(strtolower(trim((string) $this->role->name)), ['customer'], true));
    }

    public function isCustomer(): bool
    {
        if (strtolower(trim((string) $this->status)) !== 'active') {
            return false;
        }

        $roleName = strtolower((string) $this->role_name);

        return $roleName === 'customer';
    }

    public function hasRole(string|array $roles): bool
    {
        if (strtolower(trim((string) $this->status)) !== 'active') {
            return false;
        }

        $roleName = $this->role?->name;
        if (! $roleName) {
            return false;
        }

        if (is_string($roles)) {
            return strcasecmp($roleName, $roles) === 0;
        }

        foreach ((array) $roles as $r) {
            if (strcasecmp($roleName, (string) $r) === 0) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->hasRole($roles);
    }

    public function hasPermission(string $permission): bool
    {
        if (strtolower(trim((string) $this->status)) !== 'active') {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        // Direct user permission check
        if ($this->relationLoaded('permissions')) {
            if ($this->permissions->contains('name', $permission)) {
                return true;
            }
        } else {
            if ($this->permissions()->where('name', $permission)->exists()) {
                return true;
            }
        }

        // Role-based permission check
        if ($this->role) {
            if ($this->role->relationLoaded('permissions')) {
                return $this->role->permissions->contains('name', $permission);
            }

            return $this->role->permissions()->where('name', $permission)->exists();
        }

        return false;
    }
}
