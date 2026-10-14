<?php
namespace App\Database;

use PDO;
use App\Database\Dialect\DialectInterface;
use App\Database\Dialect\MySqlDialect;

/**
 * SchemaInspector using pluggable DialectInterface (Phase 3)
 */
class SchemaInspector
{
    private PDO $pdo;
    private DialectInterface $dialect;

    public function __construct(PDO $pdo, ?DialectInterface $dialect = null)
    {
        $this->pdo = $pdo;
        $this->dialect = $dialect ?? new MySqlDialect();
    }

    /**
     * @return array<int,string>
     */
    public function getTables(): array
    {
        return $this->dialect->listTables($this->pdo);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getColumns(string $table): array
    {
        return $this->dialect->listColumns($this->pdo, $table);
    }

    public function getPrimaryKey(string $table): ?string
    {
        return $this->dialect->getPrimaryKey($this->pdo, $table);
    }

    public function hasColumn(string $table, string $column): bool
    {
        foreach ($this->getColumns($table) as $col) {
            $name = $col['Field'] ?? $col['name'] ?? $col['column_name'] ?? null;
            if (is_string($name) && strcasecmp($name, $column) === 0) {
                return true;
            }
        }
        return false;
    }

    public function quoteIdent(string $ident): string
    {
        return $this->dialect->quoteIdent($ident);
    }
}
