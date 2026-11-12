<?php

return [
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

    'instagram' => [
        'handle' => env('INSTAGRAM_HANDLE', 'alishe_nails'),
        'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
        'business_account_id' => env('INSTAGRAM_BUSINESS_ACCOUNT_ID'),
        // "Instagram API with Facebook Login" (Meta Developer app) uses
        // https://graph.facebook.com and requires business_account_id.
        // For an "Instagram API with Instagram Login" token instead, set
        // INSTAGRAM_API_BASE=https://graph.instagram.com (uses /me).
        'api_base' => env('INSTAGRAM_API_BASE', 'https://graph.facebook.com'),
        'graph_version' => env('INSTAGRAM_GRAPH_VERSION', 'v21.0'),
        // How many recent posts a sync pulls from the API / keeps in the DB.
        'fetch_limit' => env('INSTAGRAM_FETCH_LIMIT', 12),
        // How many posts the homepage gallery shows.
        'feed_size' => env('INSTAGRAM_FEED_SIZE', 8),
    ],

    'whatsapp' => [
        'number' => env('WHATSAPP_ORDER_NUMBER', '+92 3113925166'),
    ],

    'payment' => [
        'bank_name' => env('BANK_NAME', 'Meezan Bank'),
        'bank_account_title' => env('BANK_ACCOUNT_TITLE', 'Alishe Nails'),
        'bank_account_number' => env('BANK_ACCOUNT_NUMBER', '01020304050607'),
        'bank_iban' => env('BANK_IBAN', 'PK36MEZN0001020304050607'),
        'jazzcash_account_title' => env('JAZZCASH_ACCOUNT_TITLE', 'Alishba Naz'),
        'jazzcash_number' => env('JAZZCASH_NUMBER', '03412126680'),
        'jazzcash_iban' => env('JAZZCASH_IBAN', 'PK76JCMA1908923412126680'),
        'sadapay_account_title' => env('SADAPAY_ACCOUNT_TITLE', 'Alishba Naz'),
        'sadapay_number' => env('SADAPAY_NUMBER', '03113925166'),
        'sadapay_iban' => env('SADAPAY_IBAN', 'PK46SADA0000003113925166'),
        'easypaisa_account_title' => env('EASYPAISA_ACCOUNT_TITLE', 'Alishba Naz'),
        'easypaisa_number' => env('EASYPAISA_NUMBER', '03113925166'),
    ],

    // NOTE: admin authentication now uses real hashed-password Admin
    // accounts (see App\Models\Admin + AdminSeeder) via the 'admin' Auth
    // guard, not a flat .env password. This key only supplies where new-order
    // notifications are sent.
    'admin' => [
        'notification_email' => env('ADMIN_NOTIFICATION_EMAIL', 'owner@alishenails.com'),
    ],
];
