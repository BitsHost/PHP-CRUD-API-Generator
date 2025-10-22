<?php
namespace App;

use PDO;
use PDOException;

/**
 * Database Connection Manager
 * 
 * Provides a simple PDO database connection manager with MySQL/MariaDB support.
 * Automatically configures PDO with recommended settings for secure and reliable operation.
 * 
 * Features:
 * - MySQL/MariaDB connection support
 * - UTF-8 (utf8mb4) character set by default
 * - Exception mode for error handling
 * - Secure connection configuration
 * 
 * @package App
 * @author  PHP-CRUD-API-Generator
 * @version 1.0.0
 */
class Database
{
    /**
     * PDO database connection instance
     * 
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Initialize database connection
     * 
     * Creates a new PDO connection to MySQL/MariaDB database with
     * exception error mode and UTF-8 character set.
     * 
     * @param array $config Database configuration array with keys:
     *                      - host: Database server hostname (e.g., 'localhost')
     *                      - dbname: Database name to connect to
     *                      - user: Database username
     *                      - pass: Database password
     *                      - charset: Character set (optional, default: 'utf8mb4')
     * 
     * @throws PDOException If connection fails
     * 
     * @example
     * $db = new Database([
     *     'host' => 'localhost',
     *     'dbname' => 'my_database',
     *     'user' => 'db_user',
     *     'pass' => 'db_password',
     *     'charset' => 'utf8mb4'
     * ]);
     */
    public function __construct(array $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['dbname'],
            $config['charset'] ?? 'utf8mb4'
        );
        $this->pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }

    /**
     * Get the PDO connection instance
     * 
     * Returns the underlying PDO object for direct database operations.
     * 
     * @return PDO The active PDO connection
     * 
     * @example
     * $pdo = $db->getPdo();
     * $stmt = $pdo->query("SELECT * FROM users");
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}