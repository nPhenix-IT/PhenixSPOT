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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    
    'moneyfusion' => [
        'api_url' => env('MONEYFUSION_API_URL'),
        'currency' => env('MONEYFUSION_CURRENCY', 'XOF'),
    ],
    
    'kingsmspro' => [
        'base_url' => env('KINGSMSPRO_BASE_URL', 'https://edok-api.kingsmspro.com/api/v1/sms/send/'),
        'api_key' => env('KINGSMSPRO_API_KEY'),
        'client_id' => env('KINGSMSPRO_CLIENT_ID'),
        'sender' => env('KINGSMSPRO_SENDER'),
        'dlr' => env('KINGSMSPRO_DLR', 'no'),
        'dlr_url' => env('KINGSMSPRO_DLR_URL'),
    ],

];
