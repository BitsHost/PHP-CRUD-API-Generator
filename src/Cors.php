<?php
namespace App;

/**
 * CORS (Cross-Origin Resource Sharing) Handler
 * 
 * Static utility class for handling CORS headers and preflight requests.
 * Enables secure cross-origin API access from web browsers by setting
 * appropriate Access-Control-* headers.
 * 
 * Features:
 * - Origin whitelist configuration
 * - HTTP method whitelist (GET, POST, PUT, DELETE, OPTIONS)
 * - Custom header support (Authorization, X-API-Key, etc.)
 * - Automatic preflight (OPTIONS) request handling
 * - Security-focused default configuration
 * 
 * CORS Headers Set:
 * - Access-Control-Allow-Origin: Allowed origins
 * - Access-Control-Allow-Methods: Allowed HTTP methods
 * - Access-Control-Allow-Headers: Allowed request headers
 * 
 * Security Notes:
 * - Default origin is localhost:5173 (Vite dev server)
 * - Change to production domains before deployment
 * - Use specific origins instead of "*" for security
 * - Consider Access-Control-Allow-Credentials for cookies
 * 
 * @package App
 * @author Adrian D
 * @copyright 2025 BitHost
 * @license MIT
 * @version 1.4.0
 * @link https://upmvc.com
 * 
 * @example
 * // Basic usage in index.php/api.php (before routing)
 * Cors::sendHeaders();
 * 
 * // Now browser can make cross-origin requests:
 * // fetch('http://api.example.com/api.php?action=list&table=users', {
 * //   headers: { 'Authorization': 'Bearer token123' }
 * // })
 * 
 * @example
 * // Production configuration (modify sendHeaders method):
 * header("Access-Control-Allow-Origin: https://app.example.com");
 * 
 * // Multiple origins (requires dynamic logic):
 * $allowedOrigins = [
 *     'https://app.example.com',
 *     'https://admin.example.com'
 * ];
 * $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
 * if (in_array($origin, $allowedOrigins)) {
 *     header("Access-Control-Allow-Origin: $origin");
 * }
 */
class Cors
{
    /**
     * Send CORS headers
     * 
     * Sets Access-Control-* headers to allow cross-origin requests from browsers.
     * Automatically handles OPTIONS preflight requests by returning 200 OK and exiting.
     * Must be called before any other output or headers are sent.
     * 
     * Headers Set:
     * - Access-Control-Allow-Origin: http://localhost:5173 (development default)
     * - Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
     * - Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key
     * 
     * @return void Sets headers and may exit for OPTIONS requests
     * 
     * @example
     * // At the top of index.php or api.php
     * Cors::sendHeaders();
     * 
     * // Router and other logic follow...
     * $router = new Router($db, $auth);
     * $router->route($_GET);
     * 
     * @example
     * // Customize for production (edit this method):
     * header("Access-Control-Allow-Origin: https://yourdomain.com");
     * header("Access-Control-Allow-Credentials: true"); // If using cookies
     * 
     * @example
     * // Dynamic origin validation:
     * $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
     * $allowedOrigins = ['https://app.example.com', 'https://admin.example.com'];
     * if (in_array($origin, $allowedOrigins)) {
     *     header("Access-Control-Allow-Origin: $origin");
     *     header("Access-Control-Allow-Credentials: true");
     * }
     */
    public static function sendHeaders()
    {
        // Allow from your frontend (adjust as needed)
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key");
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}