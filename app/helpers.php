<?php

if (! function_exists('admin_route')) {
    function admin_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return \App\Helpers\AdminHelper::route($name, $parameters, $absolute);
    }
}
