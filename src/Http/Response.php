<?php
/**
 * Minimal JSON response helper.
 *
 * @package   PHP-CRUD-API-Generator
 * @author    BitsHost
 * @copyright 2025 BitsHost
 * @license   MIT License
 * @link      https://bitshost.biz/
 * @created   2025-11-12
 */
namespace App\Http;

/**
 * Response helper (Phase 1 minimal). Centralizes JSON output so later we can
 * add content negotiation, envelopes, correlation IDs, etc.
 */
class Response
{
    /**
     * @param mixed $payload
     * @param array<string,string> $headers
     */
    public static function json($payload, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        foreach ($headers as $k => $v) {
            header($k . ': ' . $v);
        }
        echo json_encode($payload);
    }

    public static function error(string $message, int $status): void
    {
        self::json(['error' => $message], $status);
    }
}
