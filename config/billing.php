<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Underpayment Detection Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the underpayment detection feature
    | in the AI Medical Billing system.
    |
    */

    'underpayment_threshold' => env('BILLING_UNDERPAYMENT_THRESHOLD', 10.0),

    /*
    |--------------------------------------------------------------------------
    | Denial Risk Threshold
    |--------------------------------------------------------------------------
    |
    | Threshold for denial risk scoring (0.0 to 1.0).
    | Claims with risk above this threshold will trigger alerts.
    |
    */
    'denial_risk_threshold' => env('BILLING_DENIAL_RISK_THRESHOLD', 0.7),

    /*
    |--------------------------------------------------------------------------
    | Alert Settings
    |--------------------------------------------------------------------------
    |
    | Settings for underpayment alerts and notifications.
    |
    */

    'alerts' => [
        'enabled' => env('BILLING_ALERTS_ENABLED', true),
        'auto_resolve_days' => env('BILLING_AUTO_RESOLVE_DAYS', 30),
    ],
];
