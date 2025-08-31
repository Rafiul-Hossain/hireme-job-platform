<?php

return [
    'currency' => 'BDT',
    'application_fee' => 100.00,

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'sslcommerz' => [
        'store_id' => env('SSLCOMMERZ_STORE_ID'),
        'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
        'sandbox_mode' => env('SSLCOMMERZ_SANDBOX_MODE', true),
    ],

    'webhook_tolerance' => 300, // 5 minutes in seconds
];
