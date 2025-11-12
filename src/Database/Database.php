<?php
namespace App\Database;

use PDO;
use PDOException;

/**
 * Database Connection Manager
 * Moved to App\\Database namespace.
 */
class Database
{
    private PDO $pdo;

    /**
     * @param array{
     *  host:string,
     *  dbname:string,
     *  user:string,
     *  pass:string,
     *  charset?:string
     * } $config
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

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
