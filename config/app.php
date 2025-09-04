<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'MSP'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    'invite' => [
        'expiration_days' => env('INVITE_EXPIRATION_DAYS', 14),
    ],

    'otp' => [
        'expiration_minutes' => env('OTP_EXPIRATION_MINUTES', 60),
        'secret_pin' => env('OTP_SECRET_PIN'),
        'disable' => env('DISABLE_OTP', false),
    ],

    'password_min_length' => 13,

    'image_repository' => env('IMAGE_REPOSITORY', 'images'),

    // Dialog Config, used for displaying dialog messages
    'dialog_config' => [
        'account_exist' => [
            'title' => 'Add New User',
            'message' => 'This email address is already used by another account.',
        ],
        'invalid_login' => [
            'title' => 'Invalid Login',
            'message' => 'Invalid username/password.',
        ],
        'invalid_email_forgot_password' => [
            'title' => 'Forgot Password',
            'message' => "We can't find a user with that email address.",
        ],
        'email_sent_forgot_password' => [
            'title' => 'Email sent',
            'message' => "A password reset link has been sent to the associated email. Please check your inbox.",
        ],
        'otp' => [
            'title' => 'Verification Code',
            'invalid' => [
                'message' => 'OTP is Invalid.',
            ],
            'resend' => [
                'message1' => 'Security code has been resent to your email.',
                'message2' => 'We’ve sent a security code to your email. Please enter it below to continue.',
            ],

            'expired' => [
                'message' => 'OTP has expired.',
            ],
            'verified' => [
                'message' => 'OTP verified successfully.',
            ],
        ],
        'invite' => [
            'user' => [
                'title' => 'Invite new User',
                'message' => 'Are you sure you want to invite',
            ],
            'sent' => [
                'title' => 'Invitation Sent',
                'message' => 'Invitation sent successfully to',
            ],
            'check_status' => [
                'title' => 'Invite new User',
                'message' => 'This user is already active. Click OK to refresh the page to see the updated status.',
            ],
        ],
        'impersonate' => [
            'title' => 'Impersonate User',
            'message' => 'Are you sure you want to log out and log back in as',
        ],
        'download' => [
            'options' => [
                'title' => 'Download Options',
                'sub_title' => 'Select your preferred download options below, then click Next to continue.',
                'resolution_selection' => 'Photo resolution',
                'folder_format_selection' => 'Folder structure',
                'filename_format_selection' => 'File name options',
            ],
            'request' => [
                'title' => 'Confirm Download',
                'confirm' => 'Are you sure you want to download',
                'number_of' => 'images',
                'success' => 'Success! If downloading multiple images, you will receive an email with a secure link to download your photos. This may take several minutes to process.'
            ]
        ],
        'photography' => [
            'no_jobs' => [
                'franchise_level' => 'Please go to the Configure tab to enable Jobs & Folders for viewing in the portal',
                'school_level' => 'Your MSP Photography representative will notify you when your Digital Photos become available on the portal.',
            ]
        ],
        'configuration' => [
            'reload' => [
                'title' => 'Configuration Updated',
                'message' => 'To reflect recent configuration changes, the page will be refreshed.',
            ]
        ],
    ]
];
