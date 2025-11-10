<?php

namespace App\Cache;

use App\Cache\Drivers\FileCache;

/**
 * Cache Manager
 * 
 * Main cache orchestrator that provides high-level caching functionality
 * for API responses. Handles cache key generation, TTL management,
 * table-specific invalidation, and driver initialization.
 * 
 * Features:
 * - Automatic cache key generation from request parameters
 * - Per-table TTL configuration
 * - Table exclusion rules (never cache certain tables)
 * - User-specific cache variation (different cache per API key/user)
 * - Automatic invalidation on write operations
 * - Cache hit/miss tracking via HTTP headers
 * - Support for multiple cache drivers (File, Redis, Memcached, APCu)
 * 
 * @package App\Cache
 * @author PHP-CRUD-API-Generator
 * @version 1.0.0
 */
class CacheManager
{
    /**
     * Cache driver instance
     * 
     * @var CacheInterface
     */
    private CacheInterface $driver;

    /**
     * Cache configuration
     * 
     * @var array
     */
    private array $config;

    /**
     * Cache statistics for current request
     * 
     * @var array
     */
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'invalidations' => 0,
    ];

    /**
     * Initialize cache manager
     * 
     * Creates appropriate cache driver based on configuration.
     * Falls back to file cache if configured driver is unavailable.
     * 
     * @param array $config Cache configuration from config/cache.php
     * 
     * @example
     * $cache = new CacheManager([
     *     'enabled' => true,
     *     'driver' => 'file',
     *     'ttl' => 300,
     *     'perTable' => ['users' => 60],
     * ]);
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->initializeDriver($config['driver'] ?? 'file');
    }

    /**
     * Initialize cache driver
     * 
     * Creates the appropriate cache driver instance based on configuration.
     * Currently supports: file, redis, memcached, apcu
     * 
     * @param string $driverName Driver name from config
     * @return void
     * 
     * @throws \RuntimeException If driver cannot be initialized
     */
    private function initializeDriver(string $driverName): void
    {
        switch ($driverName) {
            case 'file':
                $this->driver = new FileCache($this->config);
                break;

            case 'redis':
                // Redis driver not yet implemented - fallback to file cache
                // TODO: Implement RedisCache driver in src/Cache/Drivers/RedisCache.php
                $this->driver = new FileCache($this->config);
                break;

            case 'memcached':
                // Memcached driver not yet implemented - fallback to file cache
                // TODO: Implement MemcachedCache driver in src/Cache/Drivers/MemcachedCache.php
                $this->driver = new FileCache($this->config);
                break;

            case 'apcu':
                // APCu driver not yet implemented - fallback to file cache
                // TODO: Implement ApcuCache driver in src/Cache/Drivers/ApcuCache.php
                $this->driver = new FileCache($this->config);
                break;

            default:
                // Default to file cache
                $this->driver = new FileCache($this->config);
                break;
        }
    }

    /**
     * Check if caching is enabled
     * 
     * @return bool True if caching is enabled in config
     */
    public function isEnabled(): bool
    {
        return $this->config['enabled'] ?? false;
    }

    /**
     * Check if specific table should be cached
     * 
     * Checks against exclusion list and enabled status.
     * Some tables like sessions, logs should never be cached.
     * 
     * @param string $table Table name
     * @return bool True if table should be cached
     * 
     * @example
     * if ($cache->shouldCache('users')) {
     *     // Cache users table
     * }
     * if ($cache->shouldCache('sessions')) {
     *     // False - sessions excluded by default
     * }
     */
    public function shouldCache(string $table): bool
    {
        // Check if caching is enabled
        if (!$this->isEnabled()) {
            return false;
        }

        // Check exclusion list
        $excludeTables = $this->config['excludeTables'] ?? [];
        if (in_array($table, $excludeTables)) {
            return false;
        }

        return true;
    }

    /**
     * Generate unique cache key for request
     * 
     * Creates a deterministic cache key based on table name and request parameters.
     * Optionally varies cache by API key or user ID for per-user caching.
     * 
     * Key Format: api:table:{table}:params:{hash}[:user:{userhash}]
     * 
     * @param string $table Table name
     * @param array $params Request parameters (filter, sort, page, etc.)
     * @return string Unique cache key
     * 
     * @example
     * $key = $cache->generateKey('users', [
     *     'filter' => 'status:eq:active',
     *     'page' => 1,
     *     'page_size' => 20
     * ]);
     * // Returns: "api:table:users:params:a3f5c8d9e2..."
     */
    public function generateKey(string $table, array $params): string
    {
        // Sort params for consistent keys
        ksort($params);

        // Base key
        $baseKey = sprintf(
            'api:table:%s:params:%s',
            $table,
            md5(json_encode($params))
        );

        // Add user variation if configured
        $varyBy = $this->config['varyBy'] ?? [];
        $userSuffix = '';

        if (in_array('api_key', $varyBy)) {
            $apiKey = $this->getApiKeyFromRequest();
            if ($apiKey) {
                $userSuffix .= ':apikey:' . substr(hash('sha256', $apiKey), 0, 16);
            }
        }

        if (in_array('user_id', $varyBy)) {
            $userId = $_SESSION['user_id'] ?? null;
            if ($userId) {
                $userSuffix .= ':user:' . $userId;
            }
        }

        return $baseKey . $userSuffix;
    }

    /**
     * Get cached response
     * 
     * Retrieves cached data if available and not expired.
     * Increments hit/miss statistics.
     * 
     * @param string $key Cache key
     * @return mixed|null Cached data or null if not found/expired
     * 
     * @example
     * $data = $cache->get('api:table:users:params:...');
     * if ($data !== null) {
     *     // Cache hit!
     *     return $data;
     * }
     */
    public function get(string $key)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $value = $this->driver->get($key);

        if ($value !== null) {
            $this->stats['hits']++;
        } else {
            $this->stats['misses']++;
        }

        return $value;
    }

    /**
     * Store response in cache
     * 
     * Caches data with table-specific or default TTL.
     * Increments write statistics.
     * 
     * @param string $key Cache key
     * @param mixed $value Data to cache
     * @param string|null $table Table name (for TTL lookup)
     * @return bool True on success
     * 
     * @example
     * $cache->set(
     *     'api:table:users:params:...',
     *     $userData,
     *     'users'
     * );
     */
    public function set(string $key, $value, ?string $table = null): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $ttl = $this->getTtl($table ?? '');
        $success = $this->driver->set($key, $value, $ttl);

        if ($success) {
            $this->stats['writes']++;
        }

        return $success;
    }

    /**
     * Get TTL for specific table
     * 
     * Returns per-table TTL if configured, otherwise default TTL.
     * 
     * @param string $table Table name
     * @return int TTL in seconds
     * 
     * @example
     * $ttl = $cache->getTtl('users');        // Returns 60 if configured
     * $ttl = $cache->getTtl('products');     // Returns 300 (default)
     */
    public function getTtl(string $table): int
    {
        // Check per-table config
        if (isset($this->config['perTable'][$table])) {
            return (int) $this->config['perTable'][$table];
        }

        // Return default TTL
        return (int) ($this->config['ttl'] ?? 300);
    }

    /**
     * Invalidate all cache for specific table
     * 
     * Deletes all cache keys matching the table pattern.
     * Called automatically on create/update/delete operations.
     * 
     * @param string $table Table name
     * @return bool True on success
     * 
     * @example
     * // After creating new user
     * $cache->invalidateTable('users');
     * // All user cache entries deleted
     */
    public function invalidateTable(string $table): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $pattern = sprintf('api:table:%s:*', $table);
        $success = $this->driver->deletePattern($pattern);

        if ($success) {
            $this->stats['invalidations']++;
        }

        return $success;
    }

    /**
     * Delete specific cache key
     * 
     * @param string $key Cache key to delete
     * @return bool True on success
     */
    public function delete(string $key): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return $this->driver->delete($key);
    }

    /**
     * Clear entire cache
     * 
     * Removes all cached data. Use with caution!
     * 
     * @return bool True on success
     */
    public function clear(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return $this->driver->clear();
    }

    /**
     * Check if cache key exists
     * 
     * @param string $key Cache key
     * @return bool True if exists and not expired
     */
    public function has(string $key): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return $this->driver->has($key);
    }

    /**
     * Get cache statistics
     * 
     * Returns stats from driver plus current request stats.
     * 
     * @return array Combined statistics
     * 
     * @example
     * $stats = $cache->getStats();
     * // [
     * //   'driver' => 'file',
     * //   'hits' => 42,
     * //   'misses' => 8,
     * //   'total_size' => 1024000,
     * //   ...
     * // ]
     */
    public function getStats(): array
    {
        $driverStats = $this->driver->getStats();

        return array_merge($driverStats, [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'writes' => $this->stats['writes'],
            'invalidations' => $this->stats['invalidations'],
            'hit_ratio' => $this->getHitRatio(),
        ]);
    }

    /**
     * Calculate cache hit ratio
     * 
     * @return float Hit ratio (0.0 to 1.0)
     */
    private function getHitRatio(): float
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        if ($total === 0) {
            return 0.0;
        }

        return round($this->stats['hits'] / $total, 3);
    }

    /**
     * Get API key from current request
     * 
     * Checks headers and query parameters for API key.
     * 
     * @return string|null API key or null if not found
     */
    private function getApiKeyFromRequest(): ?string
    {
        // Check header
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $apiKey = $headers['X-API-Key'] ?? $headers['X-Api-Key'] ?? null;

        // Check query parameter
        if (!$apiKey) {
            $apiKey = $_GET['api_key'] ?? null;
        }

        return $apiKey;
    }

    /**
     * Get underlying cache driver
     * 
     * Provides access to driver for advanced operations.
     * 
     * @return CacheInterface Cache driver instance
     */
    public function getDriver(): CacheInterface
    {
        return $this->driver;
    }
}
