<?php

if (! function_exists('admin_route')) {
    function admin_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return \App\Helpers\AdminHelper::route($name, $parameters, $absolute);
    }
}

if (! function_exists('inr')) {
    /**
     * Format amounts for admin/customer screens with two decimal places.
     */
    function inr(mixed $amount): string
    {
        return '₹'.number_format((float) ($amount ?? 0), 2);
    }
}
