<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Referral Income Rate
    |--------------------------------------------------------------------------
    | Client confirmation: 100% referral.
    | Applied on each qualifying downline purchase (≥ ₹200) when the sponsor
    | is month-eligible, then rounded up to the next ₹10 (same cashback rule).
    | Payout remains subject to Sanstha profit / admin settlement.
    */
    'referral_rate' => env('MLM_REFERRAL_RATE', '1.00'),
];
