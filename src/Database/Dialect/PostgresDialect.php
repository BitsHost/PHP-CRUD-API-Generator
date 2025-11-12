<?php
declare(strict_types=1);

namespace App\Database\Dialect;

use PDO;
use LogicException;

class PostgresDialect implements DialectInterface
{
    public function quoteIdent(string $identifier): string
    {
        // Double-quote identifiers and escape existing quotes
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * @return array<int,string>
     */
    public function listTables(PDO $pdo): array
    {
        $sql = "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname NOT IN ('pg_catalog','information_schema') ORDER BY tablename";
        $stmt = $pdo->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listColumns(PDO $pdo, string $table): array
    {
        $sql = "SELECT column_name AS Field, data_type AS Type, is_nullable AS Null, column_default AS Default FROM information_schema.columns WHERE table_name = :t ORDER BY ordinal_position";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':t' => $table]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        // Normalize to MySQL-like shape where useful
        return array_map(function ($r) {
            return [
                'Field' => $r['Field'],
                'Type' => $r['Type'],
                'Null' => $r['Null'] === 'YES' ? 'YES' : 'NO',
                'Default' => $r['Default'],
                'Key' => null,
            ];
        }, $rows);
    }

    public function getPrimaryKey(PDO $pdo, string $table): ?string
    {
        $sql = "SELECT a.attname AS pk FROM pg_index i JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey) WHERE i.indrelid = :t::regclass AND i.indisprimary";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':t' => $table]);
        $pk = $stmt->fetchColumn();
        return $pk ? (string)$pk : null;
    }
}
