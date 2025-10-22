<?php

namespace App;

class Validator
{
    /**
     * Validate and sanitize table name
     */
    public static function validateTableName(string $table): bool
    {
        // Allow alphanumeric and underscores only
        return preg_match('/^[a-zA-Z0-9_]+$/', $table) === 1;
    }

    /**
     * Validate column name
     */
    public static function validateColumnName(string $column): bool
    {
        // Allow alphanumeric and underscores only
        return preg_match('/^[a-zA-Z0-9_]+$/', $column) === 1;
    }

    /**
     * Validate page number
     */
    public static function validatePage($page): int
    {
        $pageInt = filter_var($page, FILTER_VALIDATE_INT);
        return ($pageInt !== false && $pageInt > 0) ? $pageInt : 1;
    }

    /**
     * Validate page size
     */
    public static function validatePageSize($pageSize, int $max = 100, int $default = 20): int
    {
        $pageSizeInt = filter_var($pageSize, FILTER_VALIDATE_INT);
        if ($pageSizeInt === false || $pageSizeInt < 1) {
            return $default;
        }
        return min($pageSizeInt, $max);
    }

    /**
     * Validate ID parameter
     */
    public static function validateId($id): bool
    {
        // Allow integers and UUIDs
        if (is_numeric($id)) {
            return filter_var($id, FILTER_VALIDATE_INT) !== false;
        }
        // UUID format
        return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $id) === 1;
    }

    /**
     * Validate filter operator
     */
    public static function validateOperator(string $operator): bool
    {
        $validOperators = ['eq', 'neq', 'ne', 'gt', 'gte', 'ge', 'lt', 'lte', 'le', 'like', 'in', 'notin', 'nin', 'null', 'notnull'];
        return in_array(strtolower($operator), $validOperators, true);
    }

    /**
     * Sanitize and validate field list
     */
    public static function sanitizeFields(string $fields): array
    {
        $fieldList = array_map('trim', explode(',', $fields));
        return array_filter($fieldList, fn($f) => self::validateColumnName($f));
    }

    /**
     * Validate sort format
     */
    public static function validateSort(string $sort): bool
    {
        $sorts = explode(',', $sort);
        foreach ($sorts as $s) {
            $col = ltrim($s, '-');
            if (!self::validateColumnName($col)) {
                return false;
            }
        }
        return true;
    }
}
