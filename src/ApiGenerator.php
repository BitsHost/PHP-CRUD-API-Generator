<?php

namespace App;

use PDO;

/**
 * API Generator Class
 * 
 * Generates RESTful CRUD operations for database tables with advanced features
 * including filtering, sorting, pagination, field selection, and counting.
 * 
 * Features:
 * - Dynamic CRUD operations (list, read, create, update, delete)
 * - Advanced filtering with multiple operators (eq, neq, gt, gte, lt, lte, like, in, between)
 * - Flexible sorting (single and multiple fields)
 * - Pagination support (page/limit)
 * - Field selection (specific columns)
 * - Record counting
 * - Bulk operations
 * - Safe parameter binding to prevent SQL injection
 * 
 * @package App
 * @author  PHP-CRUD-API-Generator
 * @version 1.0.0
 */
class ApiGenerator
{
    /**
     * PDO database connection instance
     * 
     * @var PDO
     */
    private PDO $pdo;
    
    /**
     * Schema inspector instance for database introspection
     * 
     * @var SchemaInspector
     */
    private SchemaInspector $inspector;
    private \App\Database\Dialect\DialectInterface $dialect;

    /**
     * Initialize API Generator
     * 
     * @param PDO $pdo PDO database connection instance
     */
    public function __construct(PDO $pdo, ?\App\Database\Dialect\DialectInterface $dialect = null)
    {
        $this->pdo = $pdo;
        $this->inspector = new SchemaInspector($pdo, $dialect);
        $this->dialect = $dialect ?? new \App\Database\Dialect\MySqlDialect();
    }

    /**
     * List records from a table with advanced filtering, sorting, and pagination
     * 
     * Supports:
     * - Field selection: opts['fields'] = 'id,name,email'
     * - Filtering: opts['filter'] = 'name:eq:John,age:gt:18'
     * - Sorting: opts['sort'] = 'name:asc,created_at:desc'
     * - Pagination: opts['page'] = 1, opts['limit'] = 20
     * 
     * Filter operators:
     * - eq: Equal (=)
     * - neq/ne: Not equal (!=)
     * - gt: Greater than (>)
     * - gte/ge: Greater than or equal (>=)
     * - lt: Less than (<)
     * - lte/le: Less than or equal (<=)
     * - like: Pattern matching (LIKE)
     * - in: In list (IN)
     * - between: Between range (BETWEEN)
     * 
     * @param string $table Table name to query
     * @param array  $opts  Query options (fields, filter, sort, page, limit)
     * 
     * @return array Array of records matching the criteria
     * 
     * @throws \PDOException If database query fails
     * 
     * @example
     * // Get all users
     * $api->list('users');
     * 
     * @example
     * // Get users with filtering and pagination
     * $api->list('users', [
     *     'fields' => 'id,name,email',
     *     'filter' => 'age:gt:18,status:eq:active',
     *     'sort' => 'name:asc',
     *     'page' => 1,
     *     'limit' => 20
     * ]);
     */
    public function list(string $table, array $opts = []): array
    {
    $columns = $this->inspector->getColumns($table);
        $colNames = array_column($columns, 'Field');

        // --- Field Selection ---
        $selectedFields = '*';
        if (!empty($opts['fields'])) {
            $requestedFields = array_map('trim', explode(',', $opts['fields']));
            $validFields = array_filter($requestedFields, fn($f) => in_array($f, $colNames, true));
            if (!empty($validFields)) {
                $selectedFields = implode(', ', array_map(fn($f) => $this->dialect->quoteIdent($f), $validFields));
            }
        }

        // --- Filtering ---
        $where = [];
        $params = [];
        $paramCounter = 0; // To handle duplicate column filters
        if (!empty($opts['filter'])) {
            // Example filter: ['name:eq:Alice', 'age:gt:18', 'email:like:%gmail.com']
            $filters = explode(',', $opts['filter']);
            foreach ($filters as $f) {
                $parts = explode(':', $f, 3);
                if (count($parts) === 2) {
                    // Backward compatibility: col:value means col = value
                    $col = $parts[0];
                    $val = $parts[1];
                    if (in_array($col, $colNames, true)) {
                        if (str_contains($val, '%')) {
                            $paramKey = "{$col}_{$paramCounter}";
                            $where[] = $this->dialect->quoteIdent($col) . " LIKE :$paramKey";
                            $params[$paramKey] = $val;
                            $paramCounter++;
                        } else {
                            $paramKey = "{$col}_{$paramCounter}";
                            $where[] = $this->dialect->quoteIdent($col) . " = :$paramKey";
                            $params[$paramKey] = $val;
                            $paramCounter++;
                        }
                    }
                } elseif (count($parts) === 3 && in_array($parts[0], $colNames, true)) {
                    // New format: col:operator:value
                    $col = $parts[0];
                    $operator = strtolower($parts[1]);
                    $val = $parts[2];
                    $paramKey = "{$col}_{$paramCounter}";
                    
                    switch ($operator) {
                        case 'eq':
                            $where[] = $this->dialect->quoteIdent($col) . " = :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'neq':
                        case 'ne':
                            $where[] = $this->dialect->quoteIdent($col) . " != :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'gt':
                            $where[] = $this->dialect->quoteIdent($col) . " > :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'gte':
                        case 'ge':
                            $where[] = $this->dialect->quoteIdent($col) . " >= :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'lt':
                            $where[] = $this->dialect->quoteIdent($col) . " < :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'lte':
                        case 'le':
                            $where[] = $this->dialect->quoteIdent($col) . " <= :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'like':
                            $where[] = $this->dialect->quoteIdent($col) . " LIKE :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'in':
                            // Support for IN operator: col:in:val1|val2|val3
                            $values = explode('|', $val);
                            $placeholders = [];
                            foreach ($values as $i => $v) {
                                $inParamKey = "{$paramKey}_in_{$i}";
                                $placeholders[] = ":$inParamKey";
                                $params[$inParamKey] = $v;
                            }
                            $where[] = $this->dialect->quoteIdent($col) . " IN (" . implode(',', $placeholders) . ")";
                            break;
                        case 'notin':
                        case 'nin':
                            // Support for NOT IN operator: col:notin:val1|val2|val3
                            $values = explode('|', $val);
                            $placeholders = [];
                            foreach ($values as $i => $v) {
                                $inParamKey = "{$paramKey}_nin_{$i}";
                                $placeholders[] = ":$inParamKey";
                                $params[$inParamKey] = $v;
                            }
                            $where[] = $this->dialect->quoteIdent($col) . " NOT IN (" . implode(',', $placeholders) . ")";
                            break;
                        case 'null':
                            $where[] = $this->dialect->quoteIdent($col) . " IS NULL";
                            break;
                        case 'notnull':
                            $where[] = $this->dialect->quoteIdent($col) . " IS NOT NULL";
                            break;
                    }
                    $paramCounter++;
                }
            }
        }

        // --- Sorting ---
        $orderBy = '';
        if (!empty($opts['sort'])) {
            $orders = [];
            $sorts = explode(',', $opts['sort']);
            foreach ($sorts as $sort) {
                $direction = 'ASC';
                $col = $sort;
                if (str_starts_with($sort, '-')) {
                    $direction = 'DESC';
                    $col = substr($sort, 1);
                }
                if (in_array($col, $colNames, true)) {
                    $orders[] = $this->dialect->quoteIdent($col) . " $direction";
                }
            }
            if ($orders) {
                $orderBy = 'ORDER BY ' . implode(', ', $orders);
            }
        }

        // --- Pagination ---
        $page = max(1, (int)($opts['page'] ?? 1));
        $pageSize = max(1, min(100, (int)($opts['page_size'] ?? 20))); // max 100 rows per page
        $offset = ($page - 1) * $pageSize;
        $limit = "LIMIT $pageSize OFFSET $offset";

        $sql = 'SELECT ' . $selectedFields . ' FROM ' . $this->dialect->quoteIdent($table);
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        if ($orderBy) {
            $sql .= ' ' . $orderBy;
        }
        $sql .= " $limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Optionally: include pagination meta info
        $countStmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM ' . $this->dialect->quoteIdent($table) . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        return [
            'data' => $rows,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'pages' => (int)ceil($total / $pageSize)
            ]
        ];
    }

    /**
     * Read a single record by primary key
     * 
     * Retrieves a single record from the specified table using its primary key value.
     * Automatically detects the primary key column name.
     * 
     * @param string     $table Table name to query
     * @param int|string $id    Primary key value to search for
     * 
     * @return array|null Record data as associative array, or null if not found
     * 
     * @throws \PDOException If database query fails
     * 
     * @example
     * // Read user with ID 5
     * $user = $api->read('users', 5);
     * if ($user) {
     *     echo $user['name'];
     * }
     */
    public function read(string $table, $id): ?array
    {
        $pk = $this->inspector->getPrimaryKey($table);
        $stmt = $this->pdo->prepare(
            'SELECT * FROM ' . $this->dialect->quoteIdent($table) . ' WHERE ' . $this->dialect->quoteIdent($pk) . ' = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Create a new record in the table
     * 
     * Inserts a new record with the provided data and returns the created record
     * including the auto-generated primary key.
     * 
     * @param string $table Table name to insert into
     * @param array  $data  Associative array of column => value pairs
     * 
     * @return array The created record including generated ID
     * 
     * @throws \PDOException If database insert fails or validation errors occur
     * 
     * @example
     * // Create new user
     * $newUser = $api->create('users', [
     *     'name' => 'John Doe',
     *     'email' => 'john@example.com',
     *     'age' => 30
     * ]);
     * echo "Created user with ID: " . $newUser['id'];
     */
    public function create(string $table, array $data): array
    {
        $cols = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $cols);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->dialect->quoteIdent($table),
            implode(',', array_map(fn($c) => $this->dialect->quoteIdent($c), $cols)),
            implode(',', $placeholders)
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        $id = $this->pdo->lastInsertId();
        return $this->read($table, $id);
    }

    /**
     * Update an existing record by primary key
     * 
     * Updates specified fields of a record identified by its primary key.
     * Only provided fields are updated; others remain unchanged.
     * 
     * @param string     $table Table name to update
     * @param int|string $id    Primary key value of record to update
     * @param array      $data  Associative array of column => value pairs to update
     * 
     * @return array Updated record data, or error array if record not found
     * 
     * @throws \PDOException If database update fails
     * 
     * @example
     * // Update user email
     * $updated = $api->update('users', 5, [
     *     'email' => 'newemail@example.com',
     *     'updated_at' => date('Y-m-d H:i:s')
     * ]);
     */
    public function update(string $table, $id, array $data): array
    {
        $pk = $this->inspector->getPrimaryKey($table);
        $sets = [];
        foreach ($data as $col => $val) {
            $sets[] = $this->dialect->quoteIdent($col) . " = :$col";
        }
        // Handle no fields to update
        if (empty($sets)) {
            return ["error" => "No fields to update. Send at least one column."];
        }
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :id',
            $this->dialect->quoteIdent($table),
            implode(', ', $sets)
            , $this->dialect->quoteIdent($pk)
        );
        $stmt = $this->pdo->prepare($sql);
        $data['id'] = $id;
        $stmt->execute($data);
        // Check if any row was actually updated
        if ($stmt->rowCount() === 0) {
            // Check if the row exists at all
            $existing = $this->read($table, $id);
            if ($existing === null) {
                return ["error" => "Item with id $id not found in $table."];
            } else {
                // The row exists but there was no change (e.g., same data)
                return $existing;
            }
        }
        $updated = $this->read($table, $id);
        if ($updated === null) {
            return ["error" => "Unexpected error: item not found after update."];
        }
        return $updated;
    }

    /**
     * Delete a record by primary key
     * 
     * Permanently removes a record from the table identified by its primary key.
     * 
     * @param string     $table Table name to delete from
     * @param int|string $id    Primary key value of record to delete
     * 
     * @return array Success status or error message if record not found
     * 
     * @throws \PDOException If database delete fails
     * 
     * @example
     * // Delete user with ID 5
     * $result = $api->delete('users', 5);
     * if ($result['success']) {
     *     echo "User deleted successfully";
     * }
     */
    public function delete(string $table, $id): array
    {
        $pk = $this->inspector->getPrimaryKey($table);
        $stmt = $this->pdo->prepare(
            'DELETE FROM ' . $this->dialect->quoteIdent($table) . ' WHERE ' . $this->dialect->quoteIdent($pk) . ' = :id'
        );
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() === 0) {
            return ['error' => "Item with id $id not found in $table."];
        }
        return ['success' => true];
    }

    /**
     * Bulk create multiple records in a transaction
     * 
     * Creates multiple records in a single database transaction.
     * If any record fails, all changes are rolled back.
     * 
     * @param string $table   Table name to insert into
     * @param array  $records Array of associative arrays, each containing record data
     * 
     * @return array Success status with count of created records, or error
     * 
     * @throws \PDOException If database transaction fails
     * 
     * @example
     * // Create multiple users at once
     * $result = $api->bulkCreate('users', [
     *     ['name' => 'John', 'email' => 'john@example.com'],
     *     ['name' => 'Jane', 'email' => 'jane@example.com'],
     *     ['name' => 'Bob', 'email' => 'bob@example.com']
     * ]);
     * echo "Created " . $result['created'] . " users";
     */
    public function bulkCreate(string $table, array $records): array
    {
        if (empty($records)) {
            return ['error' => 'No records provided for bulk create'];
        }

        $this->pdo->beginTransaction();
        try {
            $created = [];
            foreach ($records as $data) {
                $created[] = $this->create($table, $data);
            }
            $this->pdo->commit();
            return [
                'success' => true,
                'created' => count($created),
                'data' => $created
            ];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return ['error' => 'Bulk create failed: ' . $e->getMessage()];
        }
    }

    /**
     * Bulk delete multiple records by their primary keys
     * 
     * Deletes multiple records in a single query based on their primary key values.
     * More efficient than deleting records one by one.
     * 
     * @param string $table Table name to delete from
     * @param array  $ids   Array of primary key values to delete
     * 
     * @return array Success status with count of deleted records, or error
     * 
     * @throws \PDOException If database delete fails
     * 
     * @example
     * // Delete multiple users
     * $result = $api->bulkDelete('users', [5, 10, 15, 20]);
     * echo "Deleted " . $result['deleted'] . " users";
     */
    public function bulkDelete(string $table, array $ids): array
    {
        if (empty($ids)) {
            return ['error' => 'No IDs provided for bulk delete'];
        }

        $pk = $this->inspector->getPrimaryKey($table);
        $placeholders = [];
        $params = [];
        
        foreach ($ids as $i => $id) {
            $key = "id_$i";
            $placeholders[] = ":$key";
            $params[$key] = $id;
        }

        $sql = 'DELETE FROM ' . $this->dialect->quoteIdent($table) . ' WHERE ' . $this->dialect->quoteIdent($pk) . ' IN (' . implode(',', $placeholders) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return [
            'success' => true,
            'deleted' => $stmt->rowCount()
        ];
    }

    /**
     * Count records in a table with optional filtering
     * 
     * Returns the total count of records matching the filter criteria.
     * Supports the same filter operators as the list() method.
     * 
     * @param string $table Table name to count records from
     * @param array  $opts  Query options (filter)
     * 
     * @return array Array containing the total count
     * 
     * @throws \PDOException If database query fails
     * 
     * @example
     * // Count all users
     * $result = $api->count('users');
     * echo "Total users: " . $result['count'];
     * 
     * @example
     * // Count active users over age 18
     * $result = $api->count('users', [
     *     'filter' => 'status:eq:active,age:gt:18'
     * ]);
     * echo "Active adult users: " . $result['count'];
     */
    public function count(string $table, array $opts = []): array
    {
    $columns = $this->inspector->getColumns($table);
        $colNames = array_column($columns, 'Field');

        // --- Filtering (same as list method) ---
        $where = [];
        $params = [];
        $paramCounter = 0;
        if (!empty($opts['filter'])) {
            $filters = explode(',', $opts['filter']);
            foreach ($filters as $f) {
                $parts = explode(':', $f, 3);
                if (count($parts) === 2) {
                    $col = $parts[0];
                    $val = $parts[1];
                    if (in_array($col, $colNames, true)) {
                        if (str_contains($val, '%')) {
                            $paramKey = "{$col}_{$paramCounter}";
                            $where[] = $this->dialect->quoteIdent($col) . " LIKE :$paramKey";
                            $params[$paramKey] = $val;
                            $paramCounter++;
                        } else {
                            $paramKey = "{$col}_{$paramCounter}";
                            $where[] = $this->dialect->quoteIdent($col) . " = :$paramKey";
                            $params[$paramKey] = $val;
                            $paramCounter++;
                        }
                    }
                } elseif (count($parts) === 3 && in_array($parts[0], $colNames, true)) {
                    $col = $parts[0];
                    $operator = strtolower($parts[1]);
                    $val = $parts[2];
                    $paramKey = "{$col}_{$paramCounter}";
                    
                    switch ($operator) {
                        case 'eq':
                            $where[] = $this->dialect->quoteIdent($col) . " = :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'neq':
                        case 'ne':
                            $where[] = $this->dialect->quoteIdent($col) . " != :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'gt':
                            $where[] = $this->dialect->quoteIdent($col) . " > :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'gte':
                        case 'ge':
                            $where[] = $this->dialect->quoteIdent($col) . " >= :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'lt':
                            $where[] = $this->dialect->quoteIdent($col) . " < :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'lte':
                        case 'le':
                            $where[] = $this->dialect->quoteIdent($col) . " <= :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'like':
                            $where[] = $this->dialect->quoteIdent($col) . " LIKE :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'in':
                            $values = explode('|', $val);
                            $placeholders = [];
                            foreach ($values as $i => $v) {
                                $inParamKey = "{$paramKey}_in_{$i}";
                                $placeholders[] = ":$inParamKey";
                                $params[$inParamKey] = $v;
                            }
                            $where[] = $this->dialect->quoteIdent($col) . " IN (" . implode(',', $placeholders) . ")";
                            break;
                        case 'notin':
                        case 'nin':
                            $values = explode('|', $val);
                            $placeholders = [];
                            foreach ($values as $i => $v) {
                                $inParamKey = "{$paramKey}_nin_{$i}";
                                $placeholders[] = ":$inParamKey";
                                $params[$inParamKey] = $v;
                            }
                            $where[] = $this->dialect->quoteIdent($col) . " NOT IN (" . implode(',', $placeholders) . ")";
                            break;
                        case 'null':
                            $where[] = $this->dialect->quoteIdent($col) . " IS NULL";
                            break;
                        case 'notnull':
                            $where[] = $this->dialect->quoteIdent($col) . " IS NOT NULL";
                            break;
                    }
                    $paramCounter++;
                }
            }
        }

        $sql = 'SELECT COUNT(*) FROM ' . $this->dialect->quoteIdent($table);
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $count = (int)$stmt->fetchColumn();

        return ['count' => $count];
    }
}
