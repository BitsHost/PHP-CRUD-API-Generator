<?php
/**
 * Table allowlist / denylist policy.
 *
 * @package   PHP-CRUD-API-Generator
 * @author    BitsHost
 * @copyright 2025 BitsHost
 * @license   MIT License
 */
declare(strict_types=1);

namespace App\Security;

/**
 * Restricts which tables the API may touch, independent of RBAC.
 *
 * - allowed_tables empty  → all tables permitted (except denied)
 * - allowed_tables non-empty → whitelist mode
 * - denied_tables always blocked
 */
class TablePolicy
{
    /**
     * @param list<string> $allowed
     * @param list<string> $denied
     */
    public function __construct(
        private array $allowed = [],
        private array $denied = []
    ) {
        $this->allowed = array_values(array_filter(array_map('strval', $allowed), static fn($t) => $t !== ''));
        $this->denied = array_values(array_filter(array_map('strval', $denied), static fn($t) => $t !== ''));
    }

    public function isAllowed(string $table): bool
    {
        if (in_array($table, $this->denied, true)) {
            return false;
        }
        if ($this->allowed === []) {
            return true;
        }
        return in_array($table, $this->allowed, true);
    }

    /**
     * @param list<string> $tables
     * @return list<string>
     */
    public function filter(array $tables): array
    {
        return array_values(array_filter($tables, fn(string $t) => $this->isAllowed($t)));
    }

    /**
     * @return list<string>
     */
    public function getAllowed(): array
    {
        return $this->allowed;
    }

    /**
     * @return list<string>
     */
    public function getDenied(): array
    {
        return $this->denied;
    }

    public function hasAllowlist(): bool
    {
        return $this->allowed !== [];
    }
}
