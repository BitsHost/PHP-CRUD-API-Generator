<?php
namespace App\Database\Dialect;

use PDO;

/**
 * MySQL/MariaDB Dialect implementation
 */
class MySqlDialect implements DialectInterface
{
    public function quoteIdent(string $ident): string
    {
        // Simple backtick quoting, doubled backticks inside identifier
        return '`' . str_replace('`', '``', $ident) . '`';
    }

    /**
     * @return array<int,string>
     */
    public function listTables(PDO $pdo): array
    {
        $stmt = $pdo->query('SHOW TABLES');
        if ($stmt === false) {
            return [];
        }
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listColumns(PDO $pdo, string $table): array
    {
        // Table name is quoted by caller when used in SQL text.
        $quoted = $this->quoteIdent($table);
        $stmt = $pdo->query("SHOW COLUMNS FROM $quoted");
        if ($stmt === false) {
            return [];
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getPrimaryKey(PDO $pdo, string $table): ?string
    {
        foreach ($this->listColumns($pdo, $table) as $column) {
            if (($column['Key'] ?? null) === 'PRI') {
                return $column['Field'] ?? null;
            }
        }
        return null;
    }
}
