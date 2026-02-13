<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legitimacy System Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the legitimacy and governance system settings.
    |
    */

    // Secret key for HMAC generation (tamper-evident audit trail)
    'secret' => env('LEGITIMACY_SECRET', 'default_legitimacy_secret'),

    // API authentication
    'api_key' => env('LEGITIMACY_API_KEY', null),

    // JWT verification secret (for evidence verification)
    'jwt_secret' => env('LEGITIMACY_JWT_SECRET', null),

    // Required role for policy approval
    'required_role' => env('LEGITIMACY_REQUIRED_ROLE', 'policy.approve'),

    // Evidence settings
    'evidence' => [
        // Enable background verification
        'auto_verify' => env('LEGITIMACY_AUTO_VERIFY', true),

        // Verification timeout (seconds)
        'verification_timeout' => env('LEGITIMACY_VERIFICATION_TIMEOUT', 30),

        // Store raw evidence files (not just metadata)
        'store_raw_files' => env('LEGITIMACY_STORE_RAW', false),

        // Evidence storage path
        'storage_path' => storage_path('evidence'),
    ],

    // Persistence settings
    'persistence' => [
        // Fallback to file if database fails
        'file_fallback' => env('LEGITIMACY_FILE_FALLBACK', true),

        // Log file path
        'log_file' => storage_path('legitimacy.log'),

        // Verification queue file
        'queue_file' => storage_path('verification_queue.log'),
    ],

    // Idempotency settings
    'idempotency' => [
        // Enable idempotency protection
        'enabled' => env('LEGITIMACY_IDEMPOTENCY', true),

        // Cache duration (seconds)
        'ttl' => env('LEGITIMACY_IDEMPOTENCY_TTL', 86400), // 24 hours

        // Cache directory
        'cache_dir' => storage_path('idempotency'),
    ],

    // Audit settings
    'audit' => [
        // Enable audit event logging
        'enabled' => env('LEGITIMACY_AUDIT_LOG', true),

        // Audit log file
        'log_file' => storage_path('audit_events.log'),

        // Log IP addresses
        'log_ip' => env('LEGITIMACY_AUDIT_LOG_IP', true),
    ],

    // Quorum defaults
    'quorum' => [
        // Auto-approve if no participants specified
        'auto_quorum' => env('LEGITIMACY_AUTO_QUORUM', true),

        // Default threshold calculation
        'default_threshold' => 'majority', // 'majority', 'unanimous', 'any'
    ],
];