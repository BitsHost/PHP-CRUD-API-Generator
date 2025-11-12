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
class Response extends \App\Http\Response {}
