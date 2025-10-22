<?php

namespace App;

class Response
{
    /**
     * Send a success response
     */
    public static function success($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Send an error response
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
     */
    public static function created($data): void
    {
        self::success($data, 201);
    }

    /**
     * Send a no content response (204)
     */
    public static function noContent(): void
    {
        http_response_code(204);
        header('Content-Type: application/json');
    }

    /**
     * Send a not found response (404)
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, 404);
    }

    /**
     * Send an unauthorized response (401)
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    /**
     * Send a forbidden response (403)
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    /**
     * Send a method not allowed response (405)
     */
    public static function methodNotAllowed(string $message = 'Method Not Allowed'): void
    {
        self::error($message, 405);
    }

    /**
     * Send a server error response (500)
     */
    public static function serverError(string $message = 'Internal Server Error'): void
    {
        self::error($message, 500);
    }

    /**
     * Send a validation error response (422)
     */
    public static function validationError(string $message, array $errors = []): void
    {
        self::error($message, 422, $errors);
    }
}
