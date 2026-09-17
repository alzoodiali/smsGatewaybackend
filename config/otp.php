<?php

return [
    'length' => (int) env('OTP_LENGTH', 6),
    'expires_minutes' => (int) env('OTP_EXPIRES_MINUTES', 5),
    'max_verify_attempts' => (int) env('OTP_MAX_VERIFY_ATTEMPTS', 3),

    'rate_limit' => [
        'per_phone_max' => (int) env('OTP_RATE_PER_PHONE_MAX', 5),
        'per_phone_window_minutes' => (int) env('OTP_RATE_PER_PHONE_WINDOW', 15),
        'per_ip_max' => (int) env('OTP_RATE_PER_IP_MAX', 10),
        'per_ip_window_minutes' => (int) env('OTP_RATE_PER_IP_WINDOW', 15),
        'cooldown_seconds' => (int) env('OTP_COOLDOWN_SECONDS', 60),
    ],

    'gateway' => [
        'heartbeat_timeout_seconds' => (int) env('GATEWAY_HEARTBEAT_TIMEOUT', 120),
        'max_retry_attempts' => (int) env('GATEWAY_MAX_RETRY', 3),
        'stuck_job_timeout_minutes' => (int) env('GATEWAY_STUCK_JOB_TIMEOUT', 5),
        'poll_interval_seconds' => (int) env('GATEWAY_POLL_INTERVAL', 10),
    ],
];
