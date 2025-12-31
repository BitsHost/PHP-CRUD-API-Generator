<?php
namespace App\Database\Dialect;

use PDO;

/**
 * Database Dialect Interface
 *
 * Provides abstraction for database-specific behaviors (identifier quoting
 * and schema inspection). Phase 3 baseline: MySQL implementation.
 */
interface DialectInterface
{
    /** Quote an identifier (table/column) safely for the dialect. */
    public function quoteIdent(string $ident): string;

    /**
     * Return list of tables in the current database/schema.
     *
     * @return array<int,string>
     */
    public function listTables(PDO $pdo): array;

    /**
     * Return list of columns (associative arrays) for a given table.
     *
     * @return array<int,array<string,mixed>>
     */
    public function listColumns(PDO $pdo, string $table): array;

    /** Return the primary key column name for a given table, or null if not found. */
    public function getPrimaryKey(PDO $pdo, string $table): ?string;
}
