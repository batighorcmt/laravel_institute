<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Send SMS immediately (synchronous)
    |--------------------------------------------------------------------------
    |
    | When true, SMS chunks are processed during the HTTP request instead of
    | being queued. This ensures SMS delivery and log creation even when a
    | queue worker (php artisan queue:work) is not running.
    |
    | Set to false in production when a persistent queue worker is configured.
    |
    */
    'send_immediately' => filter_var(env('SMS_SEND_IMMEDIATELY', env('SMS_SEND_SYNC', true)), FILTER_VALIDATE_BOOLEAN),

    'chunk_size' => (int) env('SMS_CHUNK_SIZE', 50),

    'batch_delay_sec' => (int) env('SMS_BATCH_DELAY_SEC', 5),

    'retry_failed_delay_sec' => (int) env('SMS_RETRY_FAILED_DELAY_SEC', 300),

    'max_retry_attempts' => (int) env('SMS_MAX_RETRY_ATTEMPTS', 3),

    'per_message_usleep' => (int) env('SMS_PER_MESSAGE_USLEEP', 1_000_000),

];
