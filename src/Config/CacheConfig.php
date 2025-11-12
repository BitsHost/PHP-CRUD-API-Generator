<?php

namespace App\Config;

/**
 * Cache Configuration Class
 * 
 * PSR-4 compliant configuration class for cache settings.
 * 
 * ARCHITECTURE:
 * This class WRAPS the user's config/cache.php file and provides type-safe access.
 * 
 * Flow:
 * 1. User edits config/cache.php (simple PHP array)
 * 2. CacheConfig::fromFile() loads that array
 * 3. Framework code uses typed methods (isEnabled(), getDriver(), getTableTtl(), etc.)
 * 
 * Benefits:
 * - User: Simple array configuration
 * - Developer: Type safety, IDE autocomplete, validation
 * 
 * @package App\Config
 * @see docs/CONFIG_FLOW.md for complete architecture explanation
 */
class CacheConfig
{
    private bool $enabled;
    private string $driver;
    private int $defaultTtl;
    /** @var array<string,int> */
    private array $tableTtl;
    /** @var array<int,string> */
    private array $excludeTables;
    /** @var array<int,string> */
    private array $varyBy;
    private string $cachePath;

    /**
     * Initialize cache configuration
     * 
     * @param array $config Optional configuration array (for backward compatibility)
     */
    /**
     * @param array{
     *   enabled?: bool,
     *   driver?: string,
     *   ttl?: int,
     *   table_ttl?: array<string,int>,
     *   exclude_tables?: array<int,string>,
     *   varyBy?: array<int,string>,
     *   cache_path?: string
     * } $config
     */
    public function __construct(array $config = [])
    {
        // Default values
        $this->enabled = $config['enabled'] ?? true;
        $this->driver = $config['driver'] ?? 'file';
        $this->defaultTtl = $config['ttl'] ?? 300; // 5 minutes default
        $this->tableTtl = $config['table_ttl'] ?? [
            'users' => 300,       // 5 minutes
            'products' => 600,    // 10 minutes
            'categories' => 1800, // 30 minutes
            'settings' => 3600,   // 1 hour
        ];
        $this->excludeTables = $config['exclude_tables'] ?? [
            'sessions',
            'logs',
            'audit_trail',
            'request_logs',
        ];
        $this->varyBy = $config['varyBy'] ?? ['api_key'];
        $this->cachePath = $config['cache_path'] ?? __DIR__ . '/../../storage/cache';
    }

    /**
     * Create from legacy config file
     * 
     * @param string $configFile Path to config/cache.php
     * @return self
     */
    public static function fromFile(string $configFile): self
    {
        if (!file_exists($configFile)) {
            // Return defaults if config doesn't exist
            return new self();
        }

        $config = require $configFile;
        return new self($config);
    }

    /**
     * Check if caching is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get cache driver name
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Get default TTL
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }

    /**
     * Get TTL for specific table
     */
    public function getTableTtl(string $table): int
    {
        return $this->tableTtl[$table] ?? $this->defaultTtl;
    }

    /**
     * Check if table should be cached
     */
    public function shouldCacheTable(string $table): bool
    {
        return !in_array($table, $this->excludeTables);
    }

    /**
     * Get varyBy parameters
     */
    /**
     * @return array<int,string>
     */
    public function getVaryBy(): array
    {
        return $this->varyBy;
    }

    /**
     * Get cache storage path
     */
    public function getCachePath(): string
    {
        return $this->cachePath;
    }

    /**
     * Get all table TTL configurations
     */
    /**
     * @return array<string,int>
     */
    public function getAllTableTtl(): array
    {
        return $this->tableTtl;
    }

    /**
     * Get excluded tables
     */
    /**
     * @return array<int,string>
     */
    public function getExcludedTables(): array
    {
        return $this->excludeTables;
    }

    /**
     * Convert to array (for backward compatibility)
     */
    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'driver' => $this->driver,
            'ttl' => $this->defaultTtl,
            'table_ttl' => $this->tableTtl,
            'exclude_tables' => $this->excludeTables,
            'varyBy' => $this->varyBy,
            'cache_path' => $this->cachePath,
        ];
    }

    /**
     * Enable caching
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable caching
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Set driver
     */
    public function setDriver(string $driver): void
    {
        $this->driver = $driver;
    }

    /**
     * Set default TTL
     */
    public function setDefaultTtl(int $ttl): void
    {
        $this->defaultTtl = $ttl;
    }

    /**
     * Set TTL for specific table
     */
    public function setTableTtl(string $table, int $ttl): void
    {
        $this->tableTtl[$table] = $ttl;
    }

    /**
     * Exclude table from caching
     */
    public function excludeTable(string $table): void
    {
        if (!in_array($table, $this->excludeTables, true)) {
            $this->excludeTables[] = $table;
        }
    }

    /**
     * Include previously excluded table
     */
    public function includeTable(string $table): void
    {
        $this->excludeTables = array_filter(
            $this->excludeTables,
            fn($t) => $t !== $table
        );
    }
}
