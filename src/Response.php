<?php

namespace App;

/**
 * HTTP Response Helper
 * 
 * Static utility class for sending standardized JSON API responses with proper
 * HTTP status codes and headers. Provides convenient methods for common response
 * types (success, error, created, not found, etc.) with consistent formatting.
 * 
 * Features:
 * - Standardized JSON response format
 * - Automatic Content-Type header setting
 * - HTTP status code management
 * - Error response with optional details
 * - RESTful response shortcuts (201, 204, 401, 403, 404, 405, 422, 500)
 * - Validation error support with field-level details
 * 
 * Response Formats:
 * - Success: {"field": "value", ...} or [...]
 * - Error: {"error": "message", "details": {...}}
 * 
 * @package App
 * @author Adrian D
 * @copyright 2025 BitHost
 * @license MIT
 * @version 1.4.0
 * @link https://upmvc.com
 * 
 * @example
 * // Success response (200 OK)
 * Response::success(['id' => 123, 'name' => 'John Doe']);
 * // Output: HTTP 200, {"id": 123, "name": "John Doe"}
 * 
 * // Created response (201 Created)
 * Response::created(['id' => 456]);
 * // Output: HTTP 201, {"id": 456}
 * 
 * // Error response (400 Bad Request)
 * Response::error('Invalid input', 400, ['field' => 'email']);
 * // Output: HTTP 400, {"error": "Invalid input", "details": {"field": "email"}}
 * 
 * // Not found (404)
 * Response::notFound('User not found');
 * // Output: HTTP 404, {"error": "User not found"}
 * 
 * // Validation error (422)
 * Response::validationError('Validation failed', [
 *     'email' => 'Invalid email format',
 *     'age' => 'Must be at least 18'
 * ]);
 * // Output: HTTP 422, {"error": "Validation failed", "details": {...}}
 */
class Response
{
    /**
     * Send a success response
     * 
     * Sends JSON-encoded success response with specified HTTP status code.
     * Automatically sets Content-Type header to application/json.
     * 
     * @param mixed $data Response data (array, object, or scalar value)
     * @param int $statusCode HTTP status code (default: 200)
     * @return void Outputs response and exits
     * 
     * @example
     * // Simple success
     * Response::success(['message' => 'Operation successful']);
     * 
     * // List of records
     * Response::success([
     *     'records' => [...],
     *     'pagination' => ['page' => 1, 'total' => 100]
     * ]);
     * 
     * // Custom status code
     * Response::success(['accepted' => true], 202);
     */
    public static function success($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Send an error response
     * 
     * Sends JSON-encoded error response with error message, status code, and optional
     * additional details. Standard format: {"error": "message", "details": {...}}
     * 
     * @param string $message Human-readable error message
     * @param int $statusCode HTTP status code (default: 400 Bad Request)
     * @param array $details Optional additional error details (field errors, context, etc.)
     * @return void Outputs response and exits
     * 
     * @example
     * // Simple error
     * Response::error('Invalid request', 400);
     * // Output: {"error": "Invalid request"}
     * 
     * // Error with details
     * Response::error('Database error', 500, [
     *     'code' => 'DB_CONNECTION_FAILED',
     *     'retry_after' => 30
     * ]);
     * // Output: {"error": "Database error", "details": {...}}
     * 
     * // Validation error
     * Response::error('Validation failed', 422, [
     *     'email' => 'Invalid format',
     *     'age' => 'Must be numeric'
     * ]);
     */
    public static function error(string $message, int $statusCode = 400, array $details = []): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        $response = ['error' => $message];
        if (!empty($details)) {
            $response['details'] = $details;
        }
        echo json_encode($response);
    }

    /**
     * Send a created response (201)
     * 
     * Convenience method for 201 Created responses, typically used after
     * successful resource creation (POST requests).
     * 
     * @param mixed $data Created resource data (usually includes new ID)
     * @return void Outputs response and exits
     * 
     * @example
     * Response::created(['id' => 123]);
     * // Output: HTTP 201, {"id": 123}
     */
    public static function created($data): void
    {
        self::success($data, 201);
    }

    /**
     * Send a no content response (204)
     * 
     * Sends 204 No Content response for successful operations that return no data,
     * typically used for DELETE operations or updates with no response body.
     * 
     * @return void Outputs response (empty) and exits
     * 
     * @example
     * Response::noContent();
     * // Output: HTTP 204 (no body)
     */
    public static function noContent(): void
    {
        http_response_code(204);
        header('Content-Type: application/json');
    }

    /**
     * Send a not found response (404)
     * 
     * Convenience method for 404 Not Found errors when requested resource doesn't exist.
     * 
     * @param string $message Error message (default: 'Resource not found')
     * @return void Outputs response and exits
     * 
     * @example
     * Response::notFound('User not found');
     * // Output: HTTP 404, {"error": "User not found"}
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, 404);
    }

    /**
     * Send an unauthorized response (401)
     * 
     * Convenience method for 401 Unauthorized errors when authentication is required
     * but missing or invalid.
     * 
     * @param string $message Error message (default: 'Unauthorized')
     * @return void Outputs response and exits
     * 
     * @example
     * Response::unauthorized('Invalid API key');
     * // Output: HTTP 401, {"error": "Invalid API key"}
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    /**
     * Send a forbidden response (403)
     * 
     * Convenience method for 403 Forbidden errors when user is authenticated but
     * lacks permission for the requested operation.
     * 
     * @param string $message Error message (default: 'Forbidden')
     * @return void Outputs response and exits
     * 
     * @example
     * Response::forbidden('Insufficient permissions');
     * // Output: HTTP 403, {"error": "Insufficient permissions"}
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    /**
     * Send a method not allowed response (405)
     * 
     * Convenience method for 405 Method Not Allowed errors when HTTP method
     * is not supported for the endpoint (e.g., POST on read-only resource).
     * 
     * @param string $message Error message (default: 'Method Not Allowed')
     * @return void Outputs response and exits
     * 
     * @example
     * Response::methodNotAllowed('Only GET and POST are allowed');
     * // Output: HTTP 405, {"error": "Only GET and POST are allowed"}
     */
    public static function methodNotAllowed(string $message = 'Method Not Allowed'): void
    {
        self::error($message, 405);
    }

    /**
     * Send a server error response (500)
     * 
     * Convenience method for 500 Internal Server Error when unexpected server-side
     * error occurs (exceptions, database errors, etc.).
     * 
     * @param string $message Error message (default: 'Internal Server Error')
     * @return void Outputs response and exits
     * 
     * @example
     * Response::serverError('Database connection failed');
     * // Output: HTTP 500, {"error": "Database connection failed"}
     */
    public static function serverError(string $message = 'Internal Server Error'): void
    {
        self::error($message, 500);
    }

    /**
     * Send a validation error response (422)
     * 
     * Convenience method for 422 Unprocessable Entity errors when request is
     * well-formed but contains invalid data. Supports field-level error details.
     * 
     * @param string $message Main validation error message
     * @param array $errors Field-level validation errors (field => error message)
     * @return void Outputs response and exits
     * 
     * @example
     * Response::validationError('Validation failed', [
     *     'email' => 'Invalid email format',
     *     'password' => 'Must be at least 8 characters',
     *     'age' => 'Must be a positive integer'
     * ]);
     * // Output: HTTP 422, {"error": "Validation failed", "details": {...}}
     */
    public static function validationError(string $message, array $errors = []): void
    {
        self::error($message, 422, $errors);
    }
}
