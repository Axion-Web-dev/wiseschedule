<?php

return [

    'auth' => [
        
        'rate_limits' => [
            'login' => [
                'attempts' => env('AUTH_LOGIN_ATTEMPTS', 5),
                'decay_minutes' => env('AUTH_LOGIN_DECAY', 15), // 15 minutes
                'penalty_minutes' => env('AUTH_LOGIN_PENALTY', 60), // 1 hour
            ],
            'register' => [
                'attempts' => env('AUTH_REGISTER_ATTEMPTS', 3),
                'decay_minutes' => env('AUTH_REGISTER_DECAY', 30), // 30 minutes
                'penalty_minutes' => env('AUTH_REGISTER_PENALTY', 120), // 2 hours
            ],
            'password_reset' => [
                'attempts' => env('AUTH_PASSWORD_ATTEMPTS', 3),
                'decay_minutes' => env('AUTH_PASSWORD_DECAY', 60), // 1 hour
                'penalty_minutes' => env('AUTH_PASSWORD_PENALTY', 240), // 4 hours
            ],
        ],

        'session' => [
            'timeout_minutes' => env('SESSION_TIMEOUT_MINUTES', 120), // 2 hours
            'warning_minutes' => env('SESSION_WARNING_MINUTES', 10), // 10 minutes warning
            'extend_on_activity' => env('SESSION_EXTEND_ON_ACTIVITY', true),
        ],

        'password' => [
            'min_length' => env('PASSWORD_MIN_LENGTH', 8),
            'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
            'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
            'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
            'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
            'prevent_common_passwords' => env('PASSWORD_PREVENT_COMMON', true),
        ],

        'email' => [
            'block_temp_domains' => env('BLOCK_TEMP_EMAIL_DOMAINS', true),
            'require_verification' => env('REQUIRE_EMAIL_VERIFICATION', true),
            'verification_expire_minutes' => env('EMAIL_VERIFICATION_EXPIRE', 60), // 1 hour
        ],

        'suspicious_activity' => [
            'max_failed_attempts' => env('MAX_FAILED_ATTEMPTS', 5),
            'blacklist_duration_hours' => env('BLACKLIST_DURATION_HOURS', 24),
            'track_user_agent' => env('TRACK_USER_AGENT', true),
            'track_ip_changes' => env('TRACK_IP_CHANGES', true),
        ],

        'headers' => [
            'x_content_type_options' => 'nosniff',
            'x_frame_options' => 'DENY',
            'x_xss_protection' => '1; mode=block',
            'referrer_policy' => 'strict-origin-when-cross-origin',
            'strict_transport_security' => 'max-age=31536000; includeSubDomains',
        ],

        'bot_detection' => [
            'enabled' => env('BOT_DETECTION_ENABLED', true),
            'patterns' => [
                'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
                'python', 'java', 'perl', 'ruby', 'php', 'node'
            ],
            'allow_admin_bots' => env('ALLOW_ADMIN_BOTS', false),
        ],

        'temp_email_domains' => [
            '10minutemail.com', 'tempmail.org', 'guerrillamail.com',
            'mailinator.com', 'yopmail.com', 'maildrop.cc',
            'temp-mail.org', 'throwaway.email', 'tempmail.org',
            '20minutemail.com', 'mailinator.org', 'yopmail.net',
            'guerrillamail.net', 'tempmail.net', '10minutemail.net',
            'maildrop.info', 'yopmail.fr', 'tempmail.fr',
            'guerrillamail.info', 'tempmail.info', 'mailtemp.com'
        ],

        'common_passwords' => [
            'password', '123456', 'password123', 'admin', 'qwerty',
            'letmein', 'welcome', 'monkey', '1234567890', 'password1',
            'abc123', '111111', '123456789', 'iloveyou', 'adobe123',
            '123123', 'sunshine', 'princess', 'azerty', 'trustno1',
            '000000', 'login', '1234', '12345', 'dragon', 'master'
        ],
    ],

    'logging' => [
        'log_failed_attempts' => env('LOG_FAILED_ATTEMPTS', true),
        'log_suspicious_activity' => env('LOG_SUSPICIOUS_ACTIVITY', true),
        'log_blacklist_events' => env('LOG_BLACKLIST_EVENTS', true),
        'retention_days' => env('SECURITY_LOG_RETENTION_DAYS', 30),
    ],
];