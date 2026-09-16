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

    'przelewy24' => [
        'merchant_id' => env('P24_MERCHANT_ID'),
        'pos_id' => env('P24_POS_ID', env('P24_MERCHANT_ID')),
        'api_key' => env('P24_API_KEY'),
        'crc' => env('P24_CRC'),
        'url' => env('P24_URL', 'https://sandbox.przelewy24.pl'),
        'amounts' => [7 => (int) env('P24_PREMIUM_7_AMOUNT', 0), 30 => (int) env('P24_PREMIUM_30_AMOUNT', 0)],
    ],

];
