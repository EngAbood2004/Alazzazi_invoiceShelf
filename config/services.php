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

    'whatsapp' => [
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v24.0'),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
        'webhook_url' => env('TWILIO_WEBHOOK_URL'),
        'status_callback_url' => env('TWILIO_STATUS_CALLBACK_URL'),
    ],

    'communications' => [
        'inbox_user_id' => env('COMMUNICATIONS_INBOX_USER_ID'),
    ],

    'gmail_imap' => [
        'enabled' => (bool) env('MAIL_IMAP_ENABLED', false),
        'host' => env('MAIL_IMAP_HOST', 'imap.gmail.com'),
        'port' => (int) env('MAIL_IMAP_PORT', 993),
        'mailbox' => env('MAIL_IMAP_MAILBOX', 'INBOX'),
        'username' => env('MAIL_IMAP_USERNAME', env('MAIL_USERNAME')),
        'password' => env('MAIL_IMAP_PASSWORD', env('MAIL_PASSWORD')),
    ],

];
