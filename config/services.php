<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'vpic' => [
        'base_url' => env('VPIC_BASE_URL', 'https://vpic.nhtsa.dot.gov/api'),
        'timeout' => env('VPIC_TIMEOUT', 5),
        'cache_ttl' => env('VPIC_CACHE_TTL', 60 * 60 * 24),
    ],

    // OAuth2 client credentials from your project's Integrations page at
    // https://developers.paysera.com. Left blank, checkout falls back to
    // the pre-payment "order received" receipt — see
    // App\Services\Payments\PayseraCheckoutService::isConfigured().
    'paysera' => [
        'client_id' => env('PAYSERA_CLIENT_ID'),
        'client_secret' => env('PAYSERA_CLIENT_SECRET'),
    ],

];
