<?php

return [
    /*
    | Primary service area — Ahmedabad and surrounding pincodes.
    | Orders outside this list are blocked at checkout.
    */
    'allowed_cities' => [
        'ahmedabad',
        'ahmadabad',
        'gandhinagar',
    ],

    'allowed_pincodes' => [
        // Ahmedabad core / surrounding (extend as needed)
        '380001', '380002', '380004', '380005', '380006', '380007', '380008', '380009',
        '380013', '380014', '380015', '380016', '380018', '380019', '380021', '380022',
        '380023', '380024', '380026', '380027', '380028', '380050', '380051', '380052',
        '380054', '380055', '380058', '380059', '380060', '380061', '380063', '382210',
        '382330', '382340', '382345', '382350', '382405', '382415', '382421', '382424',
        '382440', '382443', '382445', '382449', '382450', '382470', '382475', '382480',
        '382481', '382721', '382725', '382870',
    ],

    'block_message' => 'Sorry, we currently deliver only in and around Ahmedabad. Your location is outside our delivery range, so this order cannot be placed.',
];
