<?php
namespace App\Support;

/**
 * QueryValidator consolidates common query parameter validation logic
 * formerly scattered via static Validator calls.
 *
 * Acceptance (Phase 1, point 4): All validation paths call this class;
 * messages consistent with previous behavior.
 */
class QueryValidator
{
    public static function table(string $table): bool
    {
        // Simple whitelist pattern (alphanumeric + underscore); adjust as needed.
        return (bool)preg_match('/^[A-Za-z0-9_]+$/', $table);
    }

    public static function id(int|string $id): bool
    {
        // Accept numeric string or int >= 0
        return (is_numeric($id) && (int)$id >= 0);
    }

    public static function page(int|string $page): int
    {
        $page = (int)$page;
        return $page > 0 ? $page : 1;
    }

    public static function pageSize(int|string $size): int
    {
        $size = (int)$size;
        if ($size < 1) $size = 1;
        if ($size > 100) $size = 100; // enforce max page size
        return $size;
    }

    public static function sort(string $sort): bool
    {
        // Basic validation: comma-separated pairs field:dir where dir in (asc,desc)
        // Fields: alnum + underscore
        $parts = explode(',', $sort);
        foreach ($parts as $part) {
            if (!str_contains($part, ':')) return false;
            [$field, $dir] = explode(':', $part, 2);
            if (!preg_match('/^[A-Za-z0-9_]+$/', $field)) return false;
            if (!in_array(strtolower($dir), ['asc', 'desc'], true)) return false;
        }
        return true;
    }
}
