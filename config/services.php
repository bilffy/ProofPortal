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

    'bpsync' => [
        'url' => env('BPSYNC_URL'),
        'verify_ssl' => env('BPSYNC_VERIFY_SSL', true),
    ],
    
    'exportImageLocation' => env('EXPORTIMAGELOCATION'),
    'image_upload_url' => env('IMAGE_UPLOAD_URL'),
    'image_upload_key' => env('IMAGE_UPLOAD_KEY'),
    'proofing_cache_prefix' => env('PROOFING_CACHE_PREFIX', 'proofing_cache'),
    'proofing_cache_disk' => env('PROOFING_CACHE_DISK', 'proofing_cache'),
    // php artisan config:clear
    // mkdir -p storage/app/proofing_cache
    // chmod -R 775 storage/app/proofing_cache
];
