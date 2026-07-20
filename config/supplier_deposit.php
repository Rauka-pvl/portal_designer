<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Guarantee deposit amount (KZT, integer tiyn-free tenge units)
    |--------------------------------------------------------------------------
    */
    'amount' => (int) env('SUPPLIER_DEPOSIT_AMOUNT', 100000),

    'currency' => 'KZT',

    'payment_type' => 'supplier_guarantee_deposit',

    /*
    |--------------------------------------------------------------------------
    | How long a created/pending payment session stays reusable (minutes)
    |--------------------------------------------------------------------------
    */
    'session_ttl_minutes' => (int) env('SUPPLIER_DEPOSIT_SESSION_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Demo mode: no real payment provider. Confirmation goes through
    | server-side demo webhook simulation only.
    |--------------------------------------------------------------------------
    */
    'demo' => (bool) env('SUPPLIER_DEPOSIT_DEMO', true),

    'support_email' => env('SUPPLIER_DEPOSIT_SUPPORT_EMAIL', 'support@example.com'),
];
