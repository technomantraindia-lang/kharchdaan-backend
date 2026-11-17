<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Show advanced / non-client menu items
    |--------------------------------------------------------------------------
    |
    | When false (default), the Super Admin / Sub-Admin sidebar and Quick Action
    | menus only show items mapped to the client's 38-point requirement sheet.
    | Routes and controllers for hidden tools remain available by direct URL.
    | Set ADMIN_SHOW_ADVANCED_MENU=true in the environment to show everything.
    |
    */
    'show_advanced_menu' => filter_var(
        env('ADMIN_SHOW_ADVANCED_MENU', false),
        FILTER_VALIDATE_BOOLEAN
    ),

];
