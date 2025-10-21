<?php
return [
    // ========================================
    // AUTHENTICATION SETTINGS
    // ========================================
    'auth_enabled' => true,
    'auth_method' => 'basic', // or 'apikey', 'jwt', 'oauth'
    'api_keys' => ['changeme123'],
    'basic_users' => [
        'admin' => 'secret',
        'user' => 'userpass'
    ],
    'jwt_secret' => 'YourSuperSecretKeyChangeMe',
    'jwt_issuer' => 'yourdomain.com',
    'jwt_audience' => 'yourdomain.com',

    // ========================================
    // RATE LIMITING SETTINGS
    // ========================================
    'rate_limit' => [
        'enabled' => true,              // Enable/disable rate limiting
        'max_requests' => 100,          // Maximum requests per window
        'window_seconds' => 60,         // Time window in seconds (1 minute)
        'storage_dir' => __DIR__ . '/../storage/rate_limits', // Storage directory
    ],

    // ========================================
    // LOGGING SETTINGS
    // ========================================
    'logging' => [
        'enabled' => true,              // Enable/disable request logging
        'log_dir' => __DIR__ . '/../logs', // Log directory
        'log_level' => 'info',          // Minimum log level: debug, info, warning, error
        'log_headers' => true,          // Log request headers
        'log_body' => true,             // Log request body
        'log_query_params' => true,     // Log query parameters
        'log_response_body' => false,   // Log response body (can be large)
        'max_body_length' => 1000,      // Maximum body length to log
        'sensitive_keys' => ['password', 'token', 'secret', 'api_key'], // Keys to redact
        'rotation_size' => 10485760,    // 10MB - rotate log when exceeds this size
        'max_files' => 30,              // Maximum number of log files to keep
    ],

    // ========================================
    // RBAC SETTINGS
    // ========================================
    // RBAC config: map users to roles, and roles to table permissions
    'roles' => [
        'admin' => [
            // full access
            '*' => ['list', 'read', 'create', 'update', 'delete']
        ],
        'readonly' => [
            // read only on all tables
            '*' => ['list', 'read']
        ],
        'users_manager' => [
            'users' => ['list', 'read', 'create', 'update'],
            'orders' => ['list', 'read']
        ]
    ],
    
    // ========================================
    // USER-ROLE MAPPING
    // ========================================
    // Map users to roles
    'user_roles' => [
        'admin' => 'admin',
        'user' => 'readonly'
    ],

    // ========================================
    // OAUTH PROVIDERS (Optional)
    // ========================================
    'oauth_providers' => [
        // 'google' => [
        //     'client_id' => '',
        //     'client_secret' => '',
        //     'redirect_uri' => '',
        // ],
    ],
];