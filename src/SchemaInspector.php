<?php
namespace App;

use PDO;

/**
 * Database Schema Inspector
 * 
 * Provides database introspection capabilities for MySQL/MariaDB databases.
 * Retrieves information about tables, columns, and primary keys.
 * 
 * Features:
 * - List all tables in database
 * - Get column information for tables
 * - Detect primary key columns
 * - Support for MySQL/MariaDB
 * 
 * @package App
 * @author  PHP-CRUD-API-Generator
 * @version 1.0.0
 */
class SchemaInspector
{
    /**
     * PDO database connection instance
     * 
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Initialize schema inspector
     * 
     * @param PDO $pdo PDO database connection instance
     * 
     * @example
     * $inspector = new SchemaInspector($pdo);
     * $tables = $inspector->getTables();
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get list of all tables in the database
     * 
     * Returns an array of table names present in the connected database.
     * 
     * @return array Array of table names as strings
     * 
     * @throws \PDOException If database query fails
     * 
     * @example
     * $tables = $inspector->getTables();
     * // Returns: ['users', 'posts', 'comments', ...]
     */
    public function getTables(): array
    {
        $stmt = $this->pdo->query('SHOW TABLES');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get column information for a specific table
     * 
     * Returns detailed information about all columns in the specified table,
     * including field name, type, null status, key type, default value, and extra info.
     * 
     * @param string $table Table name to inspect
     * 
     * @return array Array of column information, each containing:
     *               - Field: Column name
     *               - Type: Data type (e.g., 'int(11)', 'varchar(255)')
     *               - Null: Whether NULL is allowed ('YES' or 'NO')
     *               - Key: Key type ('PRI', 'UNI', 'MUL', or '')
     *               - Default: Default value
     *               - Extra: Extra information (e.g., 'auto_increment')
     * 
     * @throws \PDOException If database query fails
     * 
     * @example
     * $columns = $inspector->getColumns('users');
     * foreach ($columns as $col) {
     *     echo $col['Field'] . ': ' . $col['Type'];
     * }
     */
    public function getColumns(string $table): array
    {
        $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `$table`");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get primary key column name for a table
     * 
     * Identifies and returns the primary key column name for the specified table.
     * Returns null if no primary key is defined.
     * 
     * @param string $table Table name to inspect
     * 
     * @return string|null Primary key column name, or null if not found
     * 
     * @throws \PDOException If database query fails
     * 
     * @example
     * $pk = $inspector->getPrimaryKey('users');
     * // Returns: 'id'
     * 
     * @example
     * // Handle tables without primary key
     * $pk = $inspector->getPrimaryKey('log_table');
     * if ($pk === null) {
     *     echo "No primary key defined";
     * }
     */
    public function getPrimaryKey(string $table): ?string
    {
        $columns = $this->getColumns($table);
        foreach ($columns as $column) {
            if ($column['Key'] === 'PRI') {
                return $column['Field'];
            }
        }
        return null;
    }
}