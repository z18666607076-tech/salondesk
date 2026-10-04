<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Billing driver
    |--------------------------------------------------------------------------
    |
    | "fake" writes Cashier subscription rows locally so the app and its tests
    | run without Stripe keys. "stripe" uses Laravel Cashier checkout and
    | requires STRIPE_SECRET plus price ids.
    |
    */

    'driver' => env('BILLING_DRIVER', 'fake'),

    'prices' => [
        'basic' => env('STRIPE_PRICE_BASIC', 'price_fake_basic'),
        'pro' => env('STRIPE_PRICE_PRO', 'price_fake_pro'),
    ],

];
