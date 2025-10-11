<?php

namespace App;

use PDO;

class ApiGenerator
{
    private PDO $pdo;
    private SchemaInspector $inspector;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->inspector = new SchemaInspector($pdo);
    }

    /**
     * Enhanced list: supports filtering, sorting, pagination, field selection.
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
                $selectedFields = implode(', ', array_map(fn($f) => "`$f`", $validFields));
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
                            $where[] = "`$col` LIKE :$paramKey";
                            $params[$paramKey] = $val;
                            $paramCounter++;
                        } else {
                            $paramKey = "{$col}_{$paramCounter}";
                            $where[] = "`$col` = :$paramKey";
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
                            $where[] = "`$col` = :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'neq':
                        case 'ne':
                            $where[] = "`$col` != :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'gt':
                            $where[] = "`$col` > :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'gte':
                        case 'ge':
                            $where[] = "`$col` >= :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'lt':
                            $where[] = "`$col` < :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'lte':
                        case 'le':
                            $where[] = "`$col` <= :$paramKey";
                            $params[$paramKey] = $val;
                            break;
                        case 'like':
                            $where[] = "`$col` LIKE :$paramKey";
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
                            $where[] = "`$col` IN (" . implode(',', $placeholders) . ")";
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
                            $where[] = "`$col` NOT IN (" . implode(',', $placeholders) . ")";
                            break;
                        case 'null':
                            $where[] = "`$col` IS NULL";
                            break;
                        case 'notnull':
                            $where[] = "`$col` IS NOT NULL";
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
                    $orders[] = "`$col` $direction";
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

        $sql = "SELECT $selectedFields FROM `$table`";
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
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `$table`" . ($where ? ' WHERE ' . implode(' AND ', $where) : ''));
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

    public function read(string $table, $id): ?array
    {
        $pk = $this->inspector->getPrimaryKey($table);
        $stmt = $this->pdo->prepare("SELECT * FROM `$table` WHERE `$pk` = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function create(string $table, array $data): array
    {
        $cols = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $cols);
        $sql = sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $table,
            implode(',', array_map(fn($c) => "`$c`", $cols)),
            implode(',', $placeholders)
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        $id = $this->pdo->lastInsertId();
        return $this->read($table, $id);
    }

    public function update(string $table, $id, array $data): array
    {
        $pk = $this->inspector->getPrimaryKey($table);
        $sets = [];
        foreach ($data as $col => $val) {
            $sets[] = "`$col` = :$col";
        }
        // Handle no fields to update
        if (empty($sets)) {
            return ["error" => "No fields to update. Send at least one column."];
        }
        $sql = sprintf(
            "UPDATE `%s` SET %s WHERE `$pk` = :id",
            $table,
            implode(', ', $sets)
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

    public function delete(string $table, $id): array
    {
        $pk = $this->inspector->getPrimaryKey($table);
        $stmt = $this->pdo->prepare("DELETE FROM `$table` WHERE `$pk` = :id");
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() === 0) {
            return ['error' => "Item with id $id not found in $table."];
        }
        return ['success' => true];
    }

    /**
     * Bulk create multiple records
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
     * Bulk delete multiple records by IDs
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

        $sql = "DELETE FROM `$table` WHERE `$pk` IN (" . implode(',', $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return [
            'success' => true,
            'deleted' => $stmt->rowCount()
        ];
    }
}
