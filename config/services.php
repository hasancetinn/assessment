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

    'webhook' => [
        'url' => env('WEBHOOK_URL', 'https://webhook.site/your-uuid-here'),
    ],

    'notification' => [
        'rate_limit_per_channel' => (int) env('RATE_LIMIT_PER_CHANNEL', 100),
        'rate_limit_window' => (int) env('RATE_LIMIT_WINDOW', 1),
        'max_retry_attempts' => (int) env('MAX_RETRY_ATTEMPTS', 3),
        'retry_backoff_seconds' => (int) env('RETRY_BACKOFF_SECONDS', 60),
        'batch_size_limit' => (int) env('BATCH_SIZE_LIMIT', 1000),
    ],
];
