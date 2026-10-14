<?php
/**
 * Multitenancy policy: which tables are scoped and how.
 *
 * @package   PHP-CRUD-API-Generator
 * @author    BitsHost
 * @copyright 2026 BitsHost
 * @license   MIT License
 */
declare(strict_types=1);

namespace App\Security;

use App\Database\SchemaInspector;

/**
 * Mirrors TablePolicy style: config-driven, no SQL.
 */
final class TenantPolicy
{
    private bool $enabled;
    private string $column;
    private string $source;
    private string $claim;
    private string $header;
    private bool $enforceOnWrite;
    private bool $requireColumn;
    /** @var list<string> */
    private array $tables;
    /** @var array<string,string> username => tenant id */
    private array $basicUserTenants;
    private ?string $apiKeyTenant;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->enabled = (bool)($config['enabled'] ?? false);
        $this->column = (string)($config['column'] ?? 'tenant_id');
        $this->source = (string)($config['source'] ?? 'jwt');
        $this->claim = (string)($config['claim'] ?? 'tenant_id');
        $this->header = (string)($config['header'] ?? 'X-Tenant-Id');
        $this->enforceOnWrite = (bool)($config['enforce_on_write'] ?? true);
        $this->requireColumn = (bool)($config['require_column'] ?? true);
        $tables = $config['tables'] ?? ['*'];
        if (!is_array($tables) || $tables === []) {
            $tables = ['*'];
        }
        $this->tables = array_values(array_map('strval', $tables));
        $map = $config['basic_user_tenants'] ?? [];
        $this->basicUserTenants = is_array($map)
            ? array_map('strval', $map)
            : [];
        $apiKeyTenant = $config['api_key_tenant'] ?? null;
        $this->apiKeyTenant = $apiKeyTenant !== null && $apiKeyTenant !== ''
            ? (string)$apiKeyTenant
            : null;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getClaim(): string
    {
        return $this->claim;
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    public function enforceOnWrite(): bool
    {
        return $this->enforceOnWrite;
    }

    public function requireColumn(): bool
    {
        return $this->requireColumn;
    }

    /**
     * @return list<string>
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * @return array<string,string>
     */
    public function getBasicUserTenants(): array
    {
        return $this->basicUserTenants;
    }

    public function getApiKeyTenant(): ?string
    {
        return $this->apiKeyTenant;
    }

    public function isTableConfigured(string $table): bool
    {
        if (!$this->enabled) {
            return false;
        }
        if (in_array('*', $this->tables, true)) {
            return true;
        }
        return in_array($table, $this->tables, true);
    }

    /**
     * Whether SQL/tenant injection should apply for this table.
     */
    public function appliesTo(string $table, SchemaInspector $inspector): bool
    {
        if (!$this->isTableConfigured($table)) {
            return false;
        }
        if ($this->requireColumn && !$inspector->hasColumn($table, $this->column)) {
            return false;
        }
        return $inspector->hasColumn($table, $this->column);
    }

    /**
     * @return array{0:array{error:string},1:int}|null
     */
    public function denyIfNotReady(string $table, SchemaInspector $inspector): ?array
    {
        if (!$this->enabled || !$this->isTableConfigured($table)) {
            return null;
        }
        if ($this->requireColumn && !$inspector->hasColumn($table, $this->column)) {
            return [[
                'error' => "Forbidden: table '$table' is tenant-scoped but missing column '{$this->column}'",
            ], 403];
        }
        return null;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'column' => $this->column,
            'source' => $this->source,
            'claim' => $this->claim,
            'header' => $this->header,
            'enforce_on_write' => $this->enforceOnWrite,
            'require_column' => $this->requireColumn,
            'tables' => $this->tables,
            'basic_user_tenants' => $this->basicUserTenants,
            'api_key_tenant' => $this->apiKeyTenant,
        ];
    }
}
