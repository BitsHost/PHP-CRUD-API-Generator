<?php
declare(strict_types=1);

namespace App;

/**
 * Rate Limiter
 * 
 * Prevents API abuse by limiting the number of requests per time window.
 * Uses a sliding window algorithm with file-based storage for persistence.
 * Can be easily extended to use Redis, Memcached, or database storage.
 * 
 * Features:
 * - Sliding window algorithm for accurate rate limiting
 * - Configurable limits per identifier (IP, user, API key)
 * - Per-request overrides for custom limits
 * - Request count tracking
 * - Remaining requests calculation
 * - Reset time prediction
 * - Rate limit headers (X-RateLimit-*)
 * - File-based persistence (Redis-ready)
 * - Automatic cleanup of old data
 * - Thread-safe file operations
 * 
 * @package App
 * @author Adrian D
 * @copyright 2025 BitHost
 * @license MIT
 * @link https://upmvc.com
 * @version 1.2.0
 * 
 * @example
 * // Basic usage
 * $limiter = new RateLimiter([
 *     'enabled' => true,
 *     'max_requests' => 100,
 *     'window_seconds' => 60
 * ]);
 * 
 * if (!$limiter->checkLimit($userIp)) {
 *     $limiter->sendRateLimitResponse();
 * }
 */
class RateLimiter
{
    private string $storageDir;
    private int $maxRequests;
    private int $windowSeconds;
    private bool $enabled;

    /**
     * Initialize the rate limiter
     *
     * @param array $config Configuration options:
     *   - enabled: bool Whether rate limiting is active (default: true)
     *   - max_requests: int Maximum requests per window (default: 100)
     *   - window_seconds: int Time window in seconds (default: 60)
     *   - storage_dir: string Directory for rate limit data (default: sys_get_temp_dir())
     */
    public function __construct(array $config = [])
    {
        $this->enabled = $config['enabled'] ?? true;
        $this->maxRequests = $config['max_requests'] ?? 100;
        $this->windowSeconds = $config['window_seconds'] ?? 60;
        $this->storageDir = $config['storage_dir'] ?? sys_get_temp_dir() . '/rate_limits';

        // Create storage directory if it doesn't exist
        if ($this->enabled && !is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Check if the request should be allowed
     *
     * @param string $identifier Unique identifier (IP, user ID, API key, etc.)
     * @param int|null $maxRequests Override default max requests
     * @param int|null $windowSeconds Override default window
     * @return bool True if request is allowed, false if rate limit exceeded
     */
    public function checkLimit(
        string $identifier,
        ?int $maxRequests = null,
        ?int $windowSeconds = null
    ): bool {
        if (!$this->enabled) {
            return true; // Rate limiting disabled
        }

        $maxRequests = $maxRequests ?? $this->maxRequests;
        $windowSeconds = $windowSeconds ?? $this->windowSeconds;

        $requests = $this->getRequests($identifier);
        $now = time();
        
        // Remove expired requests (outside the time window)
        $requests = array_filter($requests, fn($timestamp) => ($now - $timestamp) < $windowSeconds);

        // Check if limit exceeded
        if (count($requests) >= $maxRequests) {
            $this->saveRequests($identifier, $requests);
            return false;
        }

        // Add current request
        $requests[] = $now;
        $this->saveRequests($identifier, $requests);

        return true;
    }

    /**
     * Get current request count for an identifier
     *
     * @param string $identifier Unique identifier
     * @return int Number of requests in current window
     */
    public function getRequestCount(string $identifier): int
    {
        if (!$this->enabled) {
            return 0;
        }

        $requests = $this->getRequests($identifier);
        $now = time();
        
        // Count only requests within the window
        $activeRequests = array_filter(
            $requests,
            fn($timestamp) => ($now - $timestamp) < $this->windowSeconds
        );

        return count($activeRequests);
    }

    /**
     * Get remaining requests for an identifier
     *
     * @param string $identifier Unique identifier
     * @return int Number of remaining requests
     */
    public function getRemainingRequests(string $identifier): int
    {
        if (!$this->enabled) {
            return $this->maxRequests;
        }

        $count = $this->getRequestCount($identifier);
        return max(0, $this->maxRequests - $count);
    }

    /**
     * Get time until rate limit resets (in seconds)
     *
     * @param string $identifier Unique identifier
     * @return int Seconds until oldest request expires
     */
    public function getResetTime(string $identifier): int
    {
        if (!$this->enabled) {
            return 0;
        }

        $requests = $this->getRequests($identifier);
        if (empty($requests)) {
            return 0;
        }

        $now = time();
        $oldestRequest = min($requests);
        $resetTime = $oldestRequest + $this->windowSeconds;

        return max(0, $resetTime - $now);
    }

    /**
     * Reset rate limit for an identifier (admin use)
     * 
     * Clears all request history for the specified identifier,
     * effectively resetting their rate limit to zero. Useful for
     * administrative overrides or testing.
     *
     * @param string $identifier Unique identifier to reset
     * 
     * @return bool True on success, false if file deletion fails
     * 
     * @example
     * // Reset rate limit for specific user
     * $limiter->reset('user:123');
     * 
     * @example
     * // Reset IP-based rate limit
     * $limiter->reset('ip:192.168.1.100');
     */
    public function reset(string $identifier): bool
    {
        $file = $this->getStorageFile($identifier);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    /**
     * Get rate limit headers for HTTP response
     * 
     * Returns standard rate limit headers following common API conventions.
     * These headers inform clients about their rate limit status.
     * 
     * Headers returned:
     * - X-RateLimit-Limit: Maximum requests allowed in window
     * - X-RateLimit-Remaining: Requests remaining in current window
     * - X-RateLimit-Reset: Unix timestamp when limit resets
     * - X-RateLimit-Window: Window duration in seconds
     *
     * @param string $identifier Unique identifier to check
     * 
     * @return array Associative array of header names and values
     * 
     * @example
     * $headers = $limiter->getHeaders('user:123');
     * foreach ($headers as $name => $value) {
     *     header("$name: $value");
     * }
     * // Sends:
     * // X-RateLimit-Limit: 100
     * // X-RateLimit-Remaining: 45
     * // X-RateLimit-Reset: 1729540900
     * // X-RateLimit-Window: 60
     */
    public function getHeaders(string $identifier): array
    {
        if (!$this->enabled) {
            return [];
        }

        return [
            'X-RateLimit-Limit' => (string)$this->maxRequests,
            'X-RateLimit-Remaining' => (string)$this->getRemainingRequests($identifier),
            'X-RateLimit-Reset' => (string)(time() + $this->getResetTime($identifier)),
            'X-RateLimit-Window' => (string)$this->windowSeconds,
        ];
    }

    /**
     * Send rate limit exceeded response and exit
     * 
     * Sends a 429 Too Many Requests response with rate limit headers
     * and JSON error message. This method terminates script execution.
     * 
     * Response includes:
     * - HTTP 429 status code
     * - Retry-After header (seconds)
     * - All rate limit headers (X-RateLimit-*)
     * - JSON error message with details
     *
     * @param string $identifier Unique identifier that exceeded limit
     * 
     * @return never This method terminates script execution
     * 
     * @example
     * if (!$limiter->checkLimit($ip)) {
     *     $limiter->sendRateLimitResponse($ip);
     *     // Script execution stops here
     * }
     */
    public function sendRateLimitResponse(string $identifier): never
    {
        $resetTime = $this->getResetTime($identifier);
        $resetDate = date('Y-m-d H:i:s', time() + $resetTime);

        http_response_code(429); // Too Many Requests
        header('Content-Type: application/json');
        header('Retry-After: ' . $resetTime);
        
        // Add rate limit headers
        foreach ($this->getHeaders($identifier) as $name => $value) {
            header("$name: $value");
        }

        echo json_encode([
            'error' => 'Rate limit exceeded',
            'message' => "Too many requests. Please try again in {$resetTime} seconds.",
            'retry_after' => $resetTime,
            'reset_at' => $resetDate,
            'limit' => $this->maxRequests,
            'window' => $this->windowSeconds
        ], JSON_PRETTY_PRINT);

        exit;
    }

    /**
     * Clean up old rate limit files (maintenance)
     *
     * @param int $olderThanSeconds Delete files older than this (default: 1 hour)
     * @return int Number of files deleted
     */
    public function cleanup(int $olderThanSeconds = 3600): int
    {
        if (!$this->enabled || !is_dir($this->storageDir)) {
            return 0;
        }

        $deleted = 0;
        $now = time();
        $files = glob($this->storageDir . '/ratelimit_*.dat');

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $olderThanSeconds) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    // ==================================================================================
    // PRIVATE METHODS
    // ==================================================================================

    /**
     * Get stored requests for an identifier
     *
     * @param string $identifier Unique identifier
     * @return array Array of timestamps
     */
    private function getRequests(string $identifier): array
    {
        $file = $this->getStorageFile($identifier);
        
        if (!file_exists($file)) {
            return [];
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return [];
        }

        $data = @unserialize($content);
        return is_array($data) ? $data : [];
    }

    /**
     * Save requests for an identifier
     *
     * @param string $identifier Unique identifier
     * @param array $requests Array of timestamps
     * @return bool True on success
     */
    private function saveRequests(string $identifier, array $requests): bool
    {
        $file = $this->getStorageFile($identifier);
        return @file_put_contents($file, serialize($requests), LOCK_EX) !== false;
    }

    /**
     * Get storage file path for an identifier
     *
     * @param string $identifier Unique identifier
     * @return string Full file path
     */
    private function getStorageFile(string $identifier): string
    {
        // Use SHA-256 hash for consistent, secure file naming
        $hash = hash('sha256', $identifier);
        return $this->storageDir . '/ratelimit_' . $hash . '.dat';
    }
}
