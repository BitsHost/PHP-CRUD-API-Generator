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
     * Parse filters and build SQL WHERE clause and params.
     */
    private function buildWhereClause(array $colNames, ?string $filter): array
    {
        $where = [];
        $params = [];
        $paramCounter = 0;

        if (empty($filter)) {
            return [$where, $params];
        }

        $filters = explode(',', $filter);
        foreach ($filters as $f) {
            $parts = explode(':', $f, 3);
            if (count($parts) === 2) {
                $col = $parts[0];
                $val = $parts[1];
                if (Validator::validateColumnName($col) && in_array($col, $colNames, true)) {
                    $paramKey = "{$col}_{$paramCounter}";
                    if (str_contains($val, '%')) {
                        $where[] = "`$col` LIKE :$paramKey";
                    } else {
                        $where[] = "`$col` = :$paramKey";
                    }
                    $params[$paramKey] = $val;
                    $paramCounter++;
                }
            } elseif (count($parts) === 3) {
                [$col, $operator, $val] = $parts;
                $operator = strtolower($operator);
                if (!Validator::validateColumnName($col) || !in_array($col, $colNames, true)) {
                    continue;
                }
                if (!Validator::validateOperator($operator)) {
                    continue;
                }
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
        return [$where, $params];
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
        [$where, $params] = $this->buildWhereClause($colNames, $opts['filter'] ?? null);

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
                if (Validator::validateColumnName($col) && in_array($col, $colNames, true)) {
                    $orders[] = "`$col` $direction";
                }
            }
            if (!empty($orders)) {
                $orderBy = 'ORDER BY ' . implode(', ', $orders);
            }
        }

        // --- Pagination ---
        $page = max(1, (int)($opts['page'] ?? 1));
        $pageSize = min(100, max(1, (int)($opts['page_size'] ?? 20)));
        $offset = ($page - 1) * $pageSize;
        $limit = "LIMIT $pageSize OFFSET $offset";

        $sql = "SELECT $selectedFields FROM `$table`";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        if ($orderBy) {
            $sql .= " $orderBy";
        }
        $sql .= " $limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count records with optional filtering
     */
    public function count(string $table, array $opts = []): array
    {
        $columns = $this->inspector->getColumns($table);
        $colNames = array_column($columns, 'Field');

        [$where, $params] = $this->buildWhereClause($colNames, $opts['filter'] ?? null);

        $sql = "SELECT COUNT(*) FROM `$table`";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $count = (int)$stmt->fetchColumn();

        return ['count' => $count];
    }
}
