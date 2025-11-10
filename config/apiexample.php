<?php

/**
 * API Configuration (User File)
 * 
 * This file is where YOU configure the API behavior.
 * Edit the values below to customize authentication, RBAC, rate limiting, etc.
 * 
 * HOW IT WORKS:
 * 1. You edit this simple PHP array
 * 2. Framework loads it via src/Config/ApiConfig.php (you don't need to touch that)
 * 3. Framework gets type-safe configuration with validation
 * 
 * See docs/CONFIG_FLOW.md for technical details
 * See docs/AUTHENTICATION.md for authentication guide
 */

return [
    // ========================================
    // AUTHENTICATION SETTINGS
    // ========================================
    // Enable/disable authentication globally
    'auth_enabled' => true,
    
    // Choose ONE authentication method (use exact values below):
    // - 'apikey' = API Key authentication (X-API-Key header or ?api_key= query param)
    // - 'basic'  = HTTP Basic Auth (username:password, good for development)
    // - 'jwt'    = JSON Web Tokens (login once, use token, best for production)
    // - 'oauth'  = OAuth tokens (placeholder, not fully implemented)
    // 
    // IMPORTANT: Use exact values above (e.g., 'apikey' NOT 'api_key')
    // See docs/AUTHENTICATION.md for complete guide
    'auth_method' => 'apikey',
    
    // ------------------------------------------
    // API KEY AUTHENTICATION SETTINGS
    // ------------------------------------------
    // Array of valid API keys (use long random strings in production)
    // Usage: curl -H "X-API-Key: changeme123" http://localhost:8000?action=tables
    // Or:    http://localhost:8000?action=tables&api_key=changeme123
    'api_keys' => ['changeme123'],
    
    // Default role assigned to ALL API key users (since keys don't have individual roles)
    // Options: 'admin', 'editor', 'readonly', or any custom role defined below
    'api_key_role' => 'admin',
    
    // ------------------------------------------
    // BASIC AUTHENTICATION SETTINGS
    // ------------------------------------------
    // Config file users (simple but not recommended for production)
    // Usage: curl -u admin:secret http://localhost:8000?action=tables
    'basic_users' => [
        'admin' => 'secret',      // username => password
        'user' => 'userpass',
    ],
    
    // ------------------------------------------
    // DATABASE AUTHENTICATION SETTINGS
    // ------------------------------------------
    // Enable database user lookup for Basic Auth and JWT login
    // When enabled, users created via scripts/create_user.php work automatically
    // Database users have passwords hashed with Argon2ID (secure)
    'use_database_auth' => true,
    
    // ------------------------------------------
    // JWT (JSON WEB TOKEN) SETTINGS
    // ------------------------------------------
    // JWT secret key for signing tokens (CHANGE THIS IN PRODUCTION!)
    // Generate with: php -r "echo bin2hex(random_bytes(32));"
    // Recommended: 64+ characters, random hex string
    'jwt_secret' => 'YourSuperSecretKeyChangeMe',
    
    // Token expiration time in seconds
    // 3600 = 1 hour (recommended), 86400 = 24 hours
    'jwt_expiration' => 3600,
    
    // Optional: JWT issuer and audience claims for validation
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
    // MONITORING SETTINGS
    // ========================================
    'monitoring' => [
        'enabled' => true,                                      // Enable/disable monitoring
        'metrics_dir' => __DIR__ . '/../storage/metrics',      // Metrics storage directory
        'alerts_dir' => __DIR__ . '/../storage/alerts',        // Alerts storage directory
        'retention_days' => 7,                                  // How long to keep metrics
        'collect_system_metrics' => true,                       // Collect system metrics (memory, CPU, disk)
        'thresholds' => [
            'error_rate' => 5.0,                               // Alert if error rate > 5%
            'response_time' => 1000,                           // Alert if avg response > 1000ms
            'auth_failures' => 10,                             // Alert if auth failures > 10 in time window
        ],
        'alert_handlers' => [
            // Add custom alert handlers here (closures or callables)
            // Example: function($alert) { mail('admin@example.com', 'Alert', $alert['message']); }
        ],
    ],

    // ========================================
    // RBAC (Role-Based Access Control) SETTINGS
    // ========================================
    // Define what each role can do with which tables
    // 
    // Permission Actions:
    // - 'list'   = View list of records (GET ?action=list)
    // - 'read'   = View single record (GET ?action=read&id=1)
    // - 'create' = Insert new record (POST ?action=create)
    // - 'update' = Modify record (PUT ?action=update&id=1)
    // - 'delete' = Remove record (DELETE ?action=delete&id=1)
    //
    // Wildcard '*' = applies to ALL tables
    // Specific table permissions override wildcards
    // Empty array [] = EXPLICIT DENY (blocks access even if wildcard allows)
    //
    // See docs/AUTHENTICATION.md for complete RBAC guide
    'roles' => [
        // Admin role: Full access to everything (including system tables)
        'admin' => [
            '*' => ['list', 'read', 'create', 'update', 'delete']
        ],
        
        // Readonly role: Can view data but not modify
        'readonly' => [
            '*' => ['list', 'read'],     // Read-only access to all tables
            
            // EXPLICIT DENY: Block access to system tables (empty array = no permissions)
            'api_users' => [],           // Cannot see user management
            'api_key_usage' => [],       // Cannot see API usage tracking
        ],
        
        // Editor role: Can modify data but not access system tables
        'editor' => [
            '*' => ['list', 'read', 'create', 'update', 'delete'],
            
            // EXPLICIT DENY: System tables blocked even though wildcard allows
            'api_users' => [],           // Cannot manage users
            'api_key_usage' => [],       // Cannot see usage data
        ],
        
        // Custom role: Specific table permissions only (no wildcard = deny other tables)
        'users_manager' => [
            'users' => ['list', 'read', 'create', 'update'],  // Can manage users table
            'orders' => ['list', 'read'],                      // Can view orders
            // All other tables: no access (no wildcard defined)
        ]
    ],
    
    // ========================================
    // USER-ROLE MAPPING (for Basic Auth config users)
    // ========================================
    // Map usernames from 'basic_users' above to roles defined in 'roles'
    // 
    // NOTE: This mapping is ONLY for config file users ('basic_users')
    // Database users (api_users table) have role in 'api_users.role' column
    // JWT users have role embedded in token claims
    // API Key users use 'api_key_role' setting above
    'user_roles' => [
        'admin' => 'admin',      // Config user 'admin' gets 'admin' role
        'user' => 'readonly',    // Config user 'user' gets 'readonly' role
        // Database users: role comes from api_users.role (no mapping needed)
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
