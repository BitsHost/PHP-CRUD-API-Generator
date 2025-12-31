<?php
declare(strict_types=1);

namespace App\Security;

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
 * @author Adrian D
 * @license MIT
 * @link https://upmvc.com
 * @version 1.2.0
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
	 * @param array{
	 *   enabled?: bool,
	 *   max_requests?: int,
	 *   window_seconds?: int,
	 *   storage_dir?: string
	 * } $config Configuration options
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

	/** Get current request count for an identifier */
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

	/** Get remaining requests for an identifier */
	public function getRemainingRequests(string $identifier): int
	{
		if (!$this->enabled) {
			return $this->maxRequests;
		}

		$count = $this->getRequestCount($identifier);
		return max(0, $this->maxRequests - $count);
	}

	/** Get time until rate limit resets (in seconds) */
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

	/** Reset rate limit for an identifier (admin use) */
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
	 * @return array<string,string>
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

	/** Send rate limit exceeded response and exit */
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

	/** Clean up old rate limit files (maintenance) */
	public function cleanup(int $olderThanSeconds = 3600): int
	{
		if (!$this->enabled || !is_dir($this->storageDir)) {
			return 0;
		}

		$deleted = 0;
		$now = time();
		$files = glob($this->storageDir . '/ratelimit_*.dat');
		if ($files === false) {
			$files = [];
		}

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
	 * @return array<int,int> Timestamps (unix time) of requests
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
		return is_array($data) ? array_values(array_filter($data, 'is_int')) : [];
	}

	/**
	 * Save requests for an identifier
	 *
	 * @param array<int,int> $requests
	 */
	private function saveRequests(string $identifier, array $requests): bool
	{
		$file = $this->getStorageFile($identifier);
		return @file_put_contents($file, serialize($requests), LOCK_EX) !== false;
	}

	/** Get storage file path for an identifier */
	private function getStorageFile(string $identifier): string
	{
		// Use SHA-256 hash for consistent, secure file naming
		$hash = hash('sha256', $identifier);
		return $this->storageDir . '/ratelimit_' . $hash . '.dat';
	}
}
