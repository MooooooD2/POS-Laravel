<?php

return [
    'anomaly' => [
        // Alert when a single invoice exceeds this amount
        'invoice_amount_threshold' => env('ANOMALY_INVOICE_THRESHOLD', 50000),

        // Alert when a user exceeds this many requests per minute
        'requests_per_minute' => env('ANOMALY_REQUESTS_PER_MINUTE', 100),

        // Alert after this many consecutive failed login attempts
        'failed_logins_threshold' => env('ANOMALY_FAILED_LOGINS', 10),
    ],
];
