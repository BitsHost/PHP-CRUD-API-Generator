<?php
declare(strict_types=1);

namespace App\Cache\Drivers;

use App\Cache\CacheInterface;

/**
 * Redis Cache Driver (stub)
 *
 * Placeholder for a future Redis-backed cache implementation. All methods
 * currently throw to make unsupported usage obvious at runtime while keeping
 * the codebase compiling and type-correct.
 */
class RedisCache implements CacheInterface
{
    /**
     * @param array<string,mixed> $config
     */
    public function __construct(private array $config = [])
    {
        // TODO: Implement Redis connection using ext-redis or Predis
    }

    /** @inheritDoc */
    public function get(string $key)
    {
        throw new \RuntimeException('RedisCache not implemented yet');
    }

    /** @inheritDoc */
    public function set(string $key, $value, int $ttl): bool
    {
        throw new \RuntimeException('RedisCache not implemented yet');
    }

    /** @inheritDoc */
    public function delete(string $key): bool
    {
        throw new \RuntimeException('RedisCache not implemented yet');
    }

    /** @inheritDoc */
    public function deletePattern(string $pattern): bool
    {
        throw new \RuntimeException('RedisCache not implemented yet');
    }

    /** @inheritDoc */
    public function clear(): bool
    {
        throw new \RuntimeException('RedisCache not implemented yet');
    }

    /** @inheritDoc */
    public function has(string $key): bool
    {
        throw new \RuntimeException('RedisCache not implemented yet');
    }

    /** @inheritDoc */
    /**
     * @return array<string,mixed>
     */
    public function getStats(): array
    {
        throw new \RuntimeException('RedisCache not implemented yet');
    }

    /**
     * Expose config for introspection (prevents only-written warning)
     *
     * @return array<string,mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
