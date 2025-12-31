<?php

namespace App\Config;

/**
 * Lightweight .env loader for PHP-CRUD-API-Generator.
 *
 * This avoids adding external dependencies while still allowing
 * configuration via environment variables or a project-level .env file.
 */
class Env
{
    /**
     * Load key=value pairs from a .env-style file into getenv()/$_ENV/$_SERVER.
     */
    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $name = trim($parts[0]);
            $value = trim($parts[1]);

            if ($name === '') {
                continue;
            }

            // Strip simple surrounding quotes
            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $quote = $value[0];
                if (substr($value, -1) === $quote) {
                    $value = substr($value, 1, -1);
                } else {
                    $value = substr($value, 1);
                }
            }

            // Do not override explicitly set environment variables
            if (getenv($name) === false) {
                putenv($name . '=' . $value);
            }

            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
            }

            if (!array_key_exists($name, $_SERVER)) {
                $_SERVER[$name] = $value;
            }
        }
    }
}
