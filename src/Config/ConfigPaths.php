<?php
/**
 * Resolves config directory for standalone and Composer library installs.
 *
 * @package   PHP-CRUD-API-Generator
 * @author    BitsHost
 * @copyright 2025 BitsHost
 * @license   MIT License
 */
declare(strict_types=1);

namespace App\Config;

/**
 * Lookup order:
 * 1. Explicit directory argument
 * 2. PHPCRUD_CONFIG_DIR environment variable
 * 3. ./config relative to current working directory (project root when using the package as a library)
 * 4. Package-shipped config/ directory
 */
class ConfigPaths
{
    public static function resolveDir(?string $explicit = null): string
    {
        if ($explicit !== null && $explicit !== '' && is_dir($explicit)) {
            return rtrim($explicit, "/\\");
        }

        $env = getenv('PHPCRUD_CONFIG_DIR');
        if (is_string($env) && $env !== '' && is_dir($env)) {
            return rtrim($env, "/\\");
        }

        $cwdConfig = getcwd() . DIRECTORY_SEPARATOR . 'config';
        if (is_dir($cwdConfig) && is_file($cwdConfig . DIRECTORY_SEPARATOR . 'api.php')) {
            return $cwdConfig;
        }

        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config';
    }

    public static function file(string $name, ?string $dir = null): string
    {
        return self::resolveDir($dir) . DIRECTORY_SEPARATOR . $name;
    }

    public static function api(?string $dir = null): string
    {
        return self::file('api.php', $dir);
    }

    public static function db(?string $dir = null): string
    {
        return self::file('db.php', $dir);
    }

    public static function cache(?string $dir = null): string
    {
        return self::file('cache.php', $dir);
    }
}
