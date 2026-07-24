<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trial length (days)
    |--------------------------------------------------------------------------
    */
    'trial_days' => (int) env('SUBSCRIPTION_TRIAL_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Paid plan period (days)
    |--------------------------------------------------------------------------
    */
    'period_days' => (int) env('SUBSCRIPTION_PERIOD_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Promo code (empty = promo disabled). Never commit a production secret.
    |--------------------------------------------------------------------------
    */
    'promo_code' => (string) env('SUBSCRIPTION_PROMO_CODE', ''),

    /*
    |--------------------------------------------------------------------------
    | Allow completing paid checkout without a payment provider (stub).
    | Must be false in production until a real PSP/webhook flow exists.
    | Defaults: true for local/testing, false otherwise.
    |--------------------------------------------------------------------------
    */
    'allow_stub_payments' => filter_var(
        env(
            'SUBSCRIPTION_ALLOW_STUB_PAYMENTS',
            in_array((string) env('APP_ENV', 'production'), ['local', 'testing'], true) ? 'true' : 'false'
        ),
        FILTER_VALIDATE_BOOLEAN
    ),
];
