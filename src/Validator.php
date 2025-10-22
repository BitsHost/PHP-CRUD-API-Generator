<?php

namespace App;

/**
 * Input Validator
 * 
 * Static validation utility class for sanitizing and validating all API inputs
 * to prevent SQL injection, XSS attacks, and invalid data processing. Provides
 * strict validation rules for table names, column names, IDs, pagination, filters,
 * and sorting parameters.
 * 
 * Features:
 * - SQL injection prevention (table/column name validation)
 * - ID format validation (integers and UUIDs)
 * - Pagination boundary enforcement
 * - Filter operator whitelisting
 * - Sort parameter validation
 * - Field list sanitization
 * - Type coercion with safe defaults
 * 
 * Security:
 * - All validations use whitelist approach (allow known good patterns)
 * - Regex patterns prevent special characters in identifiers
 * - Integer validation prevents type juggling attacks
 * - UUID validation follows RFC 4122 format
 * 
 * @package App
 * @author Adrian D
 * @copyright 2025 BitHost
 * @license MIT
 * @version 1.4.0
 * @link https://upmvc.com
 * 
 * @example
 * // Validate table name before query
 * if (!Validator::validateTableName($_GET['table'])) {
 *     throw new Exception('Invalid table name');
 * }
 * 
 * // Sanitize pagination
 * $page = Validator::validatePage($_GET['page'] ?? 1);        // Returns 1-N
 * $pageSize = Validator::validatePageSize($_GET['page_size'] ?? 20);  // Max 100
 * 
 * // Validate filter operator
 * if (!Validator::validateOperator($operator)) {
 *     throw new Exception('Invalid operator');
 * }
 */
class Validator
{
    /**
     * Validate and sanitize table name
     * 
     * Ensures table name contains only safe characters (alphanumeric and underscores)
     * to prevent SQL injection attacks. This is critical security validation that
     * must be applied before any dynamic table name usage in queries.
     * 
     * Allowed Pattern: [a-zA-Z0-9_]+
     * - Letters: A-Z, a-z
     * - Numbers: 0-9
     * - Underscore: _
     * 
     * @param string $table Table name to validate
     * @return bool True if valid and safe to use, false if contains invalid characters
     * 
     * @example
     * Validator::validateTableName('users');        // true
     * Validator::validateTableName('user_profiles'); // true
     * Validator::validateTableName('users2024');    // true
     * Validator::validateTableName('users-table');  // false (hyphen not allowed)
     * Validator::validateTableName('users; DROP');  // false (SQL injection attempt)
     * Validator::validateTableName('users.posts');  // false (dot not allowed)
     */
    public static function validateTableName(string $table): bool
    {
        // Allow alphanumeric and underscores only
        return preg_match('/^[a-zA-Z0-9_]+$/', $table) === 1;
    }

    /**
     * Validate column name
     * 
     * Ensures column name contains only safe characters (alphanumeric and underscores)
     * to prevent SQL injection in SELECT, WHERE, ORDER BY, and other column references.
     * Uses same strict pattern as table name validation.
     * 
     * Allowed Pattern: [a-zA-Z0-9_]+
     * 
     * @param string $column Column name to validate
     * @return bool True if valid and safe to use, false if contains invalid characters
     * 
     * @example
     * Validator::validateColumnName('email');         // true
     * Validator::validateColumnName('created_at');    // true
     * Validator::validateColumnName('user_id');       // true
     * Validator::validateColumnName('email-address'); // false (hyphen)
     * Validator::validateColumnName('COUNT(*)');      // false (function call)
     * Validator::validateColumnName('id; DELETE');    // false (SQL injection)
     */
    public static function validateColumnName(string $column): bool
    {
        // Allow alphanumeric and underscores only
        return preg_match('/^[a-zA-Z0-9_]+$/', $column) === 1;
    }

    /**
     * Validate page number
     * 
     * Validates and sanitizes pagination page number to ensure it's a positive integer.
     * Returns 1 (first page) for invalid inputs to provide graceful fallback behavior.
     * 
     * @param mixed $page Page number from user input (string, int, or other)
     * @return int Valid page number >= 1 (defaults to 1 for invalid input)
     * 
     * @example
     * Validator::validatePage(1);       // 1
     * Validator::validatePage('5');     // 5
     * Validator::validatePage('999');   // 999
     * Validator::validatePage(0);       // 1 (invalid, returns default)
     * Validator::validatePage(-5);      // 1 (invalid, returns default)
     * Validator::validatePage('abc');   // 1 (invalid, returns default)
     * Validator::validatePage(null);    // 1 (invalid, returns default)
     */
    public static function validatePage($page): int
    {
        $pageInt = filter_var($page, FILTER_VALIDATE_INT);
        return ($pageInt !== false && $pageInt > 0) ? $pageInt : 1;
    }

    /**
     * Validate page size
     * 
     * Validates and sanitizes pagination page size with configurable maximum and default.
     * Enforces upper limit to prevent memory exhaustion attacks and ensures positive values.
     * 
     * @param mixed $pageSize Page size from user input (string, int, or other)
     * @param int $max Maximum allowed page size (default: 100)
     * @param int $default Default page size for invalid input (default: 20)
     * @return int Valid page size between 1 and $max (defaults to $default for invalid input)
     * 
     * @example
     * Validator::validatePageSize(10);           // 10
     * Validator::validatePageSize('50');         // 50
     * Validator::validatePageSize(200);          // 100 (capped at max)
     * Validator::validatePageSize(0);            // 20 (invalid, returns default)
     * Validator::validatePageSize(-10);          // 20 (invalid, returns default)
     * Validator::validatePageSize('all');        // 20 (invalid, returns default)
     * 
     * // Custom limits
     * Validator::validatePageSize(250, 500, 50); // 250 (within custom max)
     * Validator::validatePageSize(600, 500, 50); // 500 (capped at custom max)
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
     * 
     * Validates record identifiers supporting both integer IDs and UUID formats.
     * Ensures safe ID values for database queries and prevents injection attacks.
     * 
     * Supported Formats:
     * - Integers: Any positive or negative integer
     * - UUIDs: RFC 4122 format (8-4-4-4-12 hexadecimal)
     * 
     * @param mixed $id ID value to validate (int, string, or other)
     * @return bool True if valid integer or UUID, false otherwise
     * 
     * @example
     * // Integer IDs
     * Validator::validateId(123);                  // true
     * Validator::validateId('456');                // true
     * Validator::validateId(0);                    // true (valid integer)
     * 
     * // UUID IDs
     * Validator::validateId('550e8400-e29b-41d4-a716-446655440000'); // true
     * Validator::validateId('123e4567-e89b-12d3-a456-426614174000'); // true
     * 
     * // Invalid IDs
     * Validator::validateId('abc');                // false
     * Validator::validateId('123-456');            // false (not UUID format)
     * Validator::validateId('1; DROP TABLE');      // false (SQL injection)
     * Validator::validateId('not-a-uuid');         // false
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
     * 
     * Validates that filter operator is in the whitelist of allowed operators.
     * Prevents SQL injection through custom operators and ensures only supported
     * comparison operations are used in filter expressions.
     * 
     * Allowed Operators:
     * - eq, neq, ne: Equality/inequality
     * - gt, gte, ge: Greater than (or equal)
     * - lt, lte, le: Less than (or equal)
     * - like: Pattern matching (SQL LIKE)
     * - in, notin, nin: IN/NOT IN list
     * - null, notnull: NULL checks
     * 
     * @param string $operator Filter operator to validate (case-insensitive)
     * @return bool True if operator is in whitelist, false otherwise
     * 
     * @example
     * Validator::validateOperator('eq');      // true
     * Validator::validateOperator('gt');      // true
     * Validator::validateOperator('like');    // true
     * Validator::validateOperator('in');      // true
     * Validator::validateOperator('null');    // true
     * Validator::validateOperator('EQ');      // true (case-insensitive)
     * 
     * Validator::validateOperator('equals');  // false (not in whitelist)
     * Validator::validateOperator('=');       // false (SQL syntax)
     * Validator::validateOperator('DROP');    // false (SQL injection)
     */
    public static function validateOperator(string $operator): bool
    {
        $validOperators = ['eq', 'neq', 'ne', 'gt', 'gte', 'ge', 'lt', 'lte', 'le', 'like', 'in', 'notin', 'nin', 'null', 'notnull'];
        return in_array(strtolower($operator), $validOperators, true);
    }

    /**
     * Sanitize and validate field list
     * 
     * Parses comma-separated field list, validates each field name, and returns
     * only safe field names. Filters out invalid fields to prevent SQL injection
     * in SELECT clause while allowing valid partial field selections.
     * 
     * @param string $fields Comma-separated list of field names
     * @return array Array of validated field names (invalid fields removed)
     * 
     * @example
     * Validator::sanitizeFields('id,name,email');
     * // Returns: ['id', 'name', 'email']
     * 
     * Validator::sanitizeFields('user_id, created_at, status');
     * // Returns: ['user_id', 'created_at', 'status'] (whitespace trimmed)
     * 
     * Validator::sanitizeFields('name,COUNT(*),email');
     * // Returns: ['name', 'email'] (COUNT(*) filtered out)
     * 
     * Validator::sanitizeFields('id; DROP TABLE users');
     * // Returns: ['id'] (SQL injection filtered out)
     * 
     * Validator::sanitizeFields('');
     * // Returns: [] (empty array)
     */
    public static function sanitizeFields(string $fields): array
    {
        $fieldList = array_map('trim', explode(',', $fields));
        return array_filter($fieldList, fn($f) => self::validateColumnName($f));
    }

    /**
     * Validate sort format
     * 
     * Validates sort parameter format to ensure safe column names in ORDER BY clause.
     * Supports multiple sort fields with optional direction prefix (- for DESC).
     * Prevents SQL injection in sorting operations.
     * 
     * Format: "column1,column2,-column3" (- prefix = descending)
     * 
     * @param string $sort Comma-separated sort specification
     * @return bool True if all column names are valid, false if any are invalid
     * 
     * @example
     * Validator::validateSort('name');              // true (single field)
     * Validator::validateSort('created_at');        // true
     * Validator::validateSort('-created_at');       // true (descending)
     * Validator::validateSort('name,-created_at');  // true (multiple fields)
     * Validator::validateSort('user_id,-status');   // true
     * 
     * Validator::validateSort('name-asc');          // false (invalid format)
     * Validator::validateSort('COUNT(*)');          // false (function call)
     * Validator::validateSort('id; DROP');          // false (SQL injection)
     * Validator::validateSort('users.name');        // false (dot notation)
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
