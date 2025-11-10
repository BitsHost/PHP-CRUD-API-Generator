<?php

namespace App\Cache;

/**
 * Cache Driver Interface
 * 
 * Defines the contract that all cache drivers must implement.
 * Provides a consistent API for storing, retrieving, and invalidating cached data
 * regardless of the underlying storage mechanism (file, Redis, Memcached, etc.).
 * 
 * @package App\Cache
 * @author PHP-CRUD-API-Generator
 * @version 1.0.0
 */
interface CacheInterface
{
    /**
     * Retrieve cached value by key
     * 
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found/expired
     * 
     * @example
     * $data = $cache->get('api:users:page1');
     * if ($data === null) {
     *     // Cache miss - fetch from database
     * }
     */
    public function get(string $key);

    /**
     * Store value in cache with TTL
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache (will be serialized)
     * @param int $ttl Time to live in seconds
     * @return bool True on success, false on failure
     * 
     * @example
     * $cache->set('api:users:page1', $userData, 300); // Cache for 5 minutes
     */
    public function set(string $key, $value, int $ttl): bool;

    /**
     * Delete specific cache key
     * 
     * @param string $key Cache key to delete
     * @return bool True on success, false on failure
     * 
     * @example
     * $cache->delete('api:users:page1');
     */
    public function delete(string $key): bool;

    /**
     * Delete all cache keys matching pattern
     * 
     * Used for invalidating entire tables on write operations.
     * Pattern syntax depends on driver implementation.
     * 
     * @param string $pattern Pattern to match (e.g., 'api:users:*')
     * @return bool True on success, false on failure
     * 
     * @example
     * $cache->deletePattern('api:users:*'); // Delete all user cache
     */
    public function deletePattern(string $pattern): bool;

    /**
     * Clear all cached data
     * 
     * @return bool True on success, false on failure
     * 
     * @example
     * $cache->clear(); // Flush entire cache
     */
    public function clear(): bool;

    /**
     * Check if cache key exists and is not expired
     * 
     * @param string $key Cache key to check
     * @return bool True if exists and valid, false otherwise
     * 
     * @example
     * if ($cache->has('api:users:page1')) {
     *     $data = $cache->get('api:users:page1');
     * }
     */
    public function has(string $key): bool;

    /**
     * Get cache statistics
     * 
     * Returns driver-specific statistics about cache usage.
     * Useful for monitoring and debugging.
     * 
     * @return array Statistics array
     * 
     * @example
     * $stats = $cache->getStats();
     * // ['driver' => 'file', 'total_size' => 1024000, 'file_count' => 42]
     */
    public function getStats(): array;
}
