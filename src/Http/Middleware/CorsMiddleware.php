<?php
/**
 * CORS middleware for access control headers.
 *
 * @package   PHP-CRUD-API-Generator
 * @author    BitsHost
 * @copyright 2025 BitsHost
 * @license   MIT License
 * @link      https://bitshost.biz/
 * @created   2025-11-12
 */
namespace App\Http\Middleware;

/**
 * Simple CORS middleware.
 *
 * Config keys (all optional):
 * - enabled: bool (default true)
 * - allow_origin: string (default "*")
 * - allow_methods: string (default "GET, POST, PUT, PATCH, DELETE, OPTIONS")
 * - allow_headers: string (default "Content-Type, Authorization, X-Requested-With")
 * - allow_credentials: bool (default false)
 * - max_age: int seconds (default 86400)
 */
class CorsMiddleware
{
    private bool $enabled;
    private string $allowOrigin;
    private string $allowMethods;
    private string $allowHeaders;
    private bool $allowCredentials;
    private int $maxAge;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->enabled = $config['enabled'] ?? true;
        $this->allowOrigin = $config['allow_origin'] ?? '*';
        $this->allowMethods = $config['allow_methods'] ?? 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
        $this->allowHeaders = $config['allow_headers'] ?? 'Content-Type, Authorization, X-Requested-With';
        $this->allowCredentials = $config['allow_credentials'] ?? false;
        $this->maxAge = $config['max_age'] ?? 86400;
    }

    public function apply(): void
    {
        if (!$this->enabled) return;
        header('Vary: Origin');
        header('Vary: Access-Control-Request-Method');
        header('Vary: Access-Control-Request-Headers');
        header('Access-Control-Allow-Origin: ' . $this->allowOrigin);
        header('Access-Control-Allow-Methods: ' . $this->allowMethods);
        header('Access-Control-Allow-Headers: ' . $this->allowHeaders);
        header('Access-Control-Max-Age: ' . (string)$this->maxAge);
        if ($this->allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }
    }

    /**
     * Handle preflight OPTIONS requests. Returns true if handled and request should stop.
     */
    public function handlePreflight(): bool
    {
        if (!$this->enabled) return false;
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'OPTIONS') return false;
        $this->apply();
        http_response_code(204);
        header('Content-Length: 0');
        return true;
    }
}
