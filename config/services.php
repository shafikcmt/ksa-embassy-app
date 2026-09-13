<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    /*
    | External lookups for the HR "Visa & Medical Auto-Fill" feature.
    | All optional — leave empty to keep the paste/parse fallback flow only.
    */
    'enjaz' => [
        'username' => env('ENJAZ_BUSINESS_USERNAME'),
        'password' => env('ENJAZ_BUSINESS_PASSWORD'),
    ],

    'wafid' => [
        'username' => env('WAFID_USERNAME'),
        'password' => env('WAFID_PASSWORD'),
    ],

];
