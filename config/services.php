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
        // Business-initiated messages (reminders) outside the 24h window need an approved template.
        'reminder_template' => env('WHATSAPP_REMINDER_TEMPLATE'),
        'reminder_template_language' => env('WHATSAPP_REMINDER_TEMPLATE_LANGUAGE', 'ar'),
    ],

    'gotenberg' => [
        'url' => env('GOTENBERG_URL'),
        'timeout' => env('GOTENBERG_TIMEOUT', 30),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'timeout' => env('OPENAI_TIMEOUT', 30),
    ],

    'lahza' => [
        'secret_key' => env('LAHZA_SECRET_KEY'),
        'public_key' => env('LAHZA_PUBLIC_KEY'),
        'base_url' => env('LAHZA_BASE_URL', 'https://api.lahza.io'),
        'currency' => env('LAHZA_CURRENCY', 'ILS'),
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
