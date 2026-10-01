<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;

class AdminHelper
{
    /**
     * Get the dynamic route URL based on current portal (super-admin vs sub-admin).
     */
    public static function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        // Strip legacy 'admin.' or portal prefixes if passed in name
        $cleanName = preg_replace('#^(admin|super-admin|sub-admin)\.#', '', $name);

        $prefix = self::getRoutePrefix();
        $targetRouteName = $prefix . '.' . $cleanName;

        if (Route::has($targetRouteName)) {
            return route($targetRouteName, $parameters, $absolute);
        }

        if (Route::has('super-admin.' . $cleanName)) {
            return route('super-admin.' . $cleanName, $parameters, $absolute);
        }

        if (Route::has('sub-admin.' . $cleanName)) {
            return route('sub-admin.' . $cleanName, $parameters, $absolute);
        }

        return url($prefix . '/' . ltrim(str_replace('.', '/', $cleanName), '/'));
    }

    /**
     * Determine whether current request/user is in Super Admin mode or Sub-Admin mode.
     */
    public static function getRoutePrefix(): string
    {
        if (request()->is('super-admin*')) {
            return 'super-admin';
        }

        if (request()->is('sub-admin*')) {
            return 'sub-admin';
        }

        if (auth()->check()) {
            return auth()->user()->isSuperAdmin() ? 'super-admin' : 'sub-admin';
        }

        return 'super-admin';
    }

    /**
     * Check if currently viewing Super Admin portal.
     */
    public static function isSuperAdminPortal(): bool
    {
        if (request()->is('super-admin*')) {
            return true;
        }

        if (auth()->check() && auth()->user()->isSuperAdmin() && ! request()->is('sub-admin*')) {
            return true;
        }

        return false;
    }
}
