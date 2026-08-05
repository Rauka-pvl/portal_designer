<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trial
    |--------------------------------------------------------------------------
    | Disabled while acquiring is not connected — activation is promo-only.
    */
    'trial_enabled' => filter_var(env('SUBSCRIPTION_TRIAL_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),
    'trial_days' => (int) env('SUBSCRIPTION_TRIAL_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Paid plan period (days) — regular billing cycle
    |--------------------------------------------------------------------------
    */
    'period_days' => (int) env('SUBSCRIPTION_PERIOD_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Real acquiring (Kaspi QR / card). Off until PSP is connected.
    |--------------------------------------------------------------------------
    */
    'payments_enabled' => filter_var(env('SUBSCRIPTION_PAYMENTS_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Promo code — 100% off for promo_period_days. Window: starts_at + valid_days.
    | Never commit a production secret.
    |--------------------------------------------------------------------------
    */
    'promo_code' => (string) env('SUBSCRIPTION_PROMO_CODE', ''),
    'promo_starts_at' => env('SUBSCRIPTION_PROMO_STARTS_AT'), // Y-m-d or ISO datetime
    'promo_valid_days' => (int) env('SUBSCRIPTION_PROMO_VALID_DAYS', 7),
    'promo_period_days' => (int) env('SUBSCRIPTION_PROMO_PERIOD_DAYS', 180), // 6 months

    /*
    |--------------------------------------------------------------------------
    | Allow completing paid checkout without a payment provider (stub).
    | Must be false in production until a real PSP/webhook flow exists.
    | Defaults: true for local/testing, false otherwise.
    | Ignored when payments_enabled is false (promo-only mode).
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
