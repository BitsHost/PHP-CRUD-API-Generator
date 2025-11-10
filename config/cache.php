<?php

/**
 * Cache Configuration (User File)
 * 
 * This file is where YOU configure API response caching.
 * Edit the values below to customize cache behavior, TTL, drivers, etc.
 * 
 * HOW IT WORKS:
 * 1. You edit this simple PHP array
 * 2. Framework loads it via src/Config/CacheConfig.php (you don't need to touch that)
 * 3. Framework gets type-safe configuration with validation
 * 
 * Performance Impact:
 * - Without cache: 100-200ms per request
 * - With cache (hit): 2-10ms per request
 * - Improvement: 10-100x faster responses!
 * 
 * See docs/CONFIG_FLOW.md for technical details
 * See docs/CACHING_IMPLEMENTATION.md for cache guide
 * 
 * @package App\Config
 */

return [
    // ========================================
    // ENABLE/DISABLE CACHING
    // ========================================
    
    /**
     * Master cache switch
     * 
     * Set to false to completely disable caching.
     * Useful for development/debugging.
     */
    'enabled' => true,

    // ========================================
    // CACHE DRIVER
    // ========================================
    
    /**
     * Cache driver to use
     * 
     * Available drivers:
     * - 'file'      : File-based cache (default, no dependencies)
     * - 'redis'     : Redis cache (requires predis/predis package) - COMING SOON
     * - 'memcached' : Memcached cache (requires ext-memcached) - COMING SOON
     * - 'apcu'      : APCu cache (requires ext-apcu) - COMING SOON
     * 
     * Recommendation:
     * - Development: 'file'
     * - Production (low-medium traffic): 'file' or 'apcu'
     * - Production (high traffic): 'redis' or 'memcached'
     */
    'driver' => 'file',

    // ========================================
    // TTL (TIME TO LIVE) SETTINGS
    // ========================================
    
    /**
     * Default cache TTL in seconds
     * 
     * How long to keep cached data before expiring.
     * Applies to all tables unless overridden in perTable config.
     * 
     * Common values:
     * - 60    = 1 minute (frequently changing data)
     * - 300   = 5 minutes (moderate)
     * - 600   = 10 minutes
     * - 1800  = 30 minutes
     * - 3600  = 1 hour (rarely changing data)
     * - 86400 = 24 hours (static data)
     */
    'ttl' => 300, // 5 minutes default

    /**
     * Per-table TTL overrides
     * 
     * Set different cache durations for specific tables.
     * Tables not listed here will use the default TTL.
     * 
     * Strategy:
     * - Frequently updated tables: Short TTL (60-300s)
     * - Rarely updated tables: Long TTL (1800-86400s)
     * - Static data (categories, settings): Very long TTL (86400s+)
     */
    'perTable' => [
        // Real-time data - cache briefly
        'active_users'      => 30,      // 30 seconds
        'online_status'     => 30,
        'current_sessions'  => 30,
        
        // Frequently changing - short cache
        'notifications'     => 60,      // 1 minute
        'messages'          => 60,
        'cart_items'        => 60,
        'orders'            => 120,     // 2 minutes
        
        // Moderate changes - medium cache
        'users'             => 300,     // 5 minutes
        'posts'             => 300,
        'comments'          => 300,
        'products'          => 600,     // 10 minutes
        
        // Rarely changing - long cache
        'categories'        => 1800,    // 30 minutes
        'tags'              => 1800,
        'pages'             => 3600,    // 1 hour
        
        // Static/config data - very long cache
        'settings'          => 86400,   // 24 hours
        'app_config'        => 86400,
        'countries'         => 86400,
        'currencies'        => 86400,
    ],

    // ========================================
    // CACHE EXCLUSIONS
    // ========================================
    
    /**
     * Tables to NEVER cache
     * 
     * Some tables should always fetch fresh data from database.
     * Add tables here that must never be cached.
     * 
     * Common exclusions:
     * - Sessions (always need fresh data)
     * - Logs (append-only, no benefit from caching)
     * - Audit trails (compliance requirement for real-time)
     * - Rate limits (must be accurate)
     * - Temporary tables
     */
    'excludeTables' => [
        'sessions',
        'user_sessions',
        'api_logs',
        'request_logs',
        'audit_logs',
        'audit_trail',
        'rate_limits',
        'rate_limit_hits',
        'temp_data',
        'queue_jobs',
        'failed_jobs',
    ],

    // ========================================
    // CACHE VARIATION
    // ========================================
    
    /**
     * Vary cache by these identifiers
     * 
     * Determines if cache should be different for each user.
     * 
     * Options:
     * - 'api_key'  : Different cache per API key (recommended for multi-user APIs)
     * - 'user_id'  : Different cache per logged-in user
     * - [] (empty) : Same cache for all users (faster, uses less memory)
     * 
     * Use case examples:
     * - Public API with API keys: ['api_key']
     * - User-specific data: ['user_id']
     * - Public read-only API: [] (no variation)
     * 
     * Note: Varying cache increases memory usage (more cache entries)
     */
    'varyBy' => ['api_key'], // Different cache per API key

    // ========================================
    // FILE CACHE DRIVER SETTINGS
    // ========================================
    
    /**
     * File cache configuration
     * 
     * Used when driver = 'file'
     */
    'file' => [
        /**
         * Cache storage path
         * 
         * Where to store cache files.
         * Must be writable by web server.
         */
        'path' => __DIR__ . '/../storage/cache',

        /**
         * File permissions for cache files
         * 
         * 0644 = Owner read/write, group/others read-only
         * 0600 = Owner read/write only (more secure)
         */
        'file_permissions' => 0644,

        /**
         * Directory permissions for cache directories
         * 
         * 0755 = Owner full, group/others read+execute
         * 0700 = Owner full only (more secure)
         */
        'dir_permissions' => 0755,
    ],

    // ========================================
    // REDIS CACHE DRIVER SETTINGS (COMING SOON)
    // ========================================
    
    /**
     * Redis cache configuration
     * 
     * Used when driver = 'redis'
     * Requires: composer require predis/predis
     */
    'redis' => [
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'password' => null,        // Set if Redis has auth enabled
        'database' => 0,           // Redis database number (0-15)
        'prefix'   => 'api_cache:', // Key prefix to avoid conflicts
    ],

    // ========================================
    // MEMCACHED CACHE DRIVER SETTINGS (COMING SOON)
    // ========================================
    
    /**
     * Memcached cache configuration
     * 
     * Used when driver = 'memcached'
     * Requires: pecl install memcached
     */
    'memcached' => [
        'host'   => '127.0.0.1',
        'port'   => 11211,
        'weight' => 100,
        'prefix' => 'api_cache:', // Key prefix
    ],

    // ========================================
    // APCU CACHE DRIVER SETTINGS (COMING SOON)
    // ========================================
    
    /**
     * APCu cache configuration
     * 
     * Used when driver = 'apcu'
     * Requires: pecl install apcu
     * 
     * Note: APCu is process-local (not shared across servers)
     * Good for single-server deployments
     */
    'apcu' => [
        'prefix' => 'api_cache:', // Key prefix
    ],
];
