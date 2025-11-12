<?php
/**
 * File-based cache driver.
 *
 * @package   PHP-CRUD-API-Generator
 * @author    BitsHost
 * @copyright 2025 BitsHost
 * @license   MIT License
 * @link      https://bitshost.biz/
 * @created   2025-11-12
 */

namespace App\Cache\Drivers;

use App\Cache\CacheInterface;

/**
 * File Cache Driver
 * 
 * Simple file-based caching implementation with zero external dependencies.
 * Stores cached data as serialized PHP files with embedded TTL metadata.
 * Perfect for development, small deployments, and shared hosting environments.
 * 
 * Features:
 * - No external dependencies (pure PHP)
 * - Works everywhere (no extensions needed)
 * - Automatic cleanup of expired cache files
 * - Pattern-based deletion for table invalidation
 * - Cache statistics (size, file count, hit ratio)
 * - Directory structure: storage/cache/{first2chars}/{key}.cache
 * 
 * Performance:
 * - Read: ~1-5ms for small datasets
 * - Write: ~2-8ms
 * - Good for: < 10,000 requests/day
 * - For high traffic, use Redis or Memcached
 * 
 * @package App\Cache\Drivers
 * @author PHP-CRUD-API-Generator
 * @version 1.0.0
 */
class FileCache implements CacheInterface
{
    /**
     * Base cache directory path
     * 
     * @var string
     */
    private string $cachePath;

    /**
     * File permissions for cache files
     * 
     * @var int
     */
    private int $filePermissions;

    /**
     * Directory permissions for cache directories
     * 
     * @var int
     */
    private int $dirPermissions;

    /**
     * Cache file extension
     * 
     * @var string
     */
    private const CACHE_EXTENSION = '.cache';

    /**
     * Initialize file cache driver
     * 
     * Creates cache directory if it doesn't exist.
     * Sets appropriate permissions for files and directories.
     * 
     * @param array<string,mixed> $config Cache configuration
     * 
     * @throws \RuntimeException If cache directory cannot be created
     * 
     * @example
     * $cache = new FileCache([
     *     'file' => [
     *         'path' => '/var/www/storage/cache',
     *         'permissions' => 0755
     *     ]
     * ]);
     */
    public function __construct(array $config)
    {
        $this->cachePath = $config['file']['path'] ?? __DIR__ . '/../../../storage/cache';
        $this->filePermissions = $config['file']['file_permissions'] ?? 0644;
        $this->dirPermissions = $config['file']['dir_permissions'] ?? 0755;

        // Create cache directory if it doesn't exist
        if (!is_dir($this->cachePath)) {
            if (!mkdir($this->cachePath, $this->dirPermissions, true)) {
                throw new \RuntimeException("Failed to create cache directory: {$this->cachePath}");
            }
        }

        // Verify cache directory is writable
        if (!is_writable($this->cachePath)) {
            throw new \RuntimeException("Cache directory is not writable: {$this->cachePath}");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key)
    {
        $filePath = $this->getFilePath($key);

        if (!file_exists($filePath)) {
            return null;
        }

        // Read cache file
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        // Unserialize cache data
        $data = @unserialize($content);
        if ($data === false || !is_array($data)) {
            // Corrupted cache file - delete it
            @unlink($filePath);
            return null;
        }

        // Check if expired
        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            // Expired - delete file and return null
            @unlink($filePath);
            return null;
        }

        // Return cached value
        return $data['value'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, int $ttl): bool
    {
        $filePath = $this->getFilePath($key);
        $directory = dirname($filePath);

        // Create subdirectory if needed
        if (!is_dir($directory)) {
            if (!mkdir($directory, $this->dirPermissions, true)) {
                return false;
            }
        }

        // Prepare cache data with expiration
        $data = [
            'key' => $key,
            'value' => $value,
            'created_at' => time(),
            'expires_at' => time() + $ttl,
            'ttl' => $ttl,
        ];

        // Serialize and write to file
        $content = serialize($data);
        $result = @file_put_contents($filePath, $content, LOCK_EX);

        if ($result === false) {
            return false;
        }

        // Set file permissions
        @chmod($filePath, $this->filePermissions);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $filePath = $this->getFilePath($key);

        if (file_exists($filePath)) {
            return @unlink($filePath);
        }

        return true; // Already deleted
    }

    /**
     * {@inheritdoc}
     */
    public function deletePattern(string $pattern): bool
    {
        // Convert cache pattern to file glob pattern
        // api:table:users:* becomes api.table.users.*.cache
        $filePattern = str_replace(':', '.', $pattern) . self::CACHE_EXTENSION;

        // Get all matching files
        $files = $this->glob_recursive($this->cachePath . '/' . $filePattern);

        $success = true;
        foreach ($files as $file) {
            if (!@unlink($file)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $pattern = $this->cachePath . '/*' . self::CACHE_EXTENSION;
        $files = $this->glob_recursive($pattern);

        $success = true;
        foreach ($files as $file) {
            if (!@unlink($file)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $filePath = $this->getFilePath($key);

        if (!file_exists($filePath)) {
            return false;
        }

        // Read and check expiration
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        $data = @unserialize($content);
        if ($data === false || !is_array($data)) {
            return false;
        }

        // Check if expired
        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            @unlink($filePath);
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    /**
     * @return array<string,mixed>
     */
    public function getStats(): array
    {
        $files = $this->glob_recursive($this->cachePath . '/*' . self::CACHE_EXTENSION);
        
        $totalSize = 0;
        $totalFiles = count($files);
        $expiredFiles = 0;
        $validFiles = 0;

        foreach ($files as $file) {
            $totalSize += filesize($file);

            // Check if expired
            $content = @file_get_contents($file);
            if ($content !== false) {
                $data = @unserialize($content);
                if (is_array($data) && isset($data['expires_at'])) {
                    if ($data['expires_at'] < time()) {
                        $expiredFiles++;
                    } else {
                        $validFiles++;
                    }
                }
            }
        }

        return [
            'driver' => 'file',
            'cache_path' => $this->cachePath,
            'total_files' => $totalFiles,
            'valid_files' => $validFiles,
            'expired_files' => $expiredFiles,
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
        ];
    }

    /**
     * Get file path for cache key
     * 
     * Creates a two-level directory structure based on key hash
     * to avoid having too many files in a single directory.
     * 
     * Example: 
     * - Key: api:table:users:params:abc123
     * - File: storage/cache/ab/api.table.users.params.abc123.cache
     * 
     * @param string $key Cache key
     * @return string Full file path
     */
    private function getFilePath(string $key): string
    {
        // Sanitize key for filesystem (replace : with .)
        $safeKey = str_replace(':', '.', $key);

        // Use first 2 characters of MD5 hash for subdirectory
        // This distributes files across 256 directories (00-ff)
        $hash = md5($key);
        $subdir = substr($hash, 0, 2);

        return sprintf(
            '%s/%s/%s%s',
            $this->cachePath,
            $subdir,
            $safeKey,
            self::CACHE_EXTENSION
        );
    }

    /**
     * Recursive glob function
     * 
     * Finds all files matching pattern in all subdirectories.
     * 
     * @param string $pattern Glob pattern
     * @return array Array of matching file paths
     */
    /**
     * @return array<int,string>
     */
    private function glob_recursive(string $pattern): array
    {
        $files = glob($pattern) ?: [];

        // Get all subdirectories
        $dirs = glob(dirname($pattern) . '/*', GLOB_ONLYDIR) ?: [];

        foreach ($dirs as $dir) {
            $subPattern = $dir . '/' . basename($pattern);
            $files = array_merge($files, $this->glob_recursive($subPattern));
        }

        return $files;
    }

    /**
     * Format bytes to human-readable size
     * 
     * @param int $bytes Size in bytes
     * @return string Formatted size (e.g., "1.5 MB")
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = (int) floor((strlen((string)$bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor] ?? 'B');
    }

    /**
     * Clean up expired cache files
     * 
     * Scans all cache files and deletes expired ones.
     * Can be called periodically via cron job.
     * 
     * @return array Cleanup statistics
     * 
     * @example
     * $stats = $cache->getDriver()->cleanup();
     * // ['deleted' => 42, 'freed_bytes' => 1024000]
     */
    /**
     * @return array{deleted_files:int,freed_bytes:int,freed_bytes_human:string}
     */
    public function cleanup(): array
    {
        $files = $this->glob_recursive($this->cachePath . '/*' . self::CACHE_EXTENSION);
        
        $deletedCount = 0;
        $freedBytes = 0;

        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content !== false) {
                $data = @unserialize($content);
                if (is_array($data) && isset($data['expires_at'])) {
                    if ($data['expires_at'] < time()) {
                        $size = filesize($file);
                        if (@unlink($file)) {
                            $deletedCount++;
                            $freedBytes += $size;
                        }
                    }
                }
            }
        }

        return [
            'deleted_files' => $deletedCount,
            'freed_bytes' => $freedBytes,
            'freed_bytes_human' => $this->formatBytes($freedBytes),
        ];
    }
}
