<?php
namespace App;

/**
 * @deprecated Use \App\Http\Middleware\CorsMiddleware instead.
 * Backward compatibility wrapper allowing existing code calling
 * Cors::sendHeaders() to still function after migration to middleware.
 */
class Cors extends \App\Http\Middleware\CorsMiddleware
{
    /**
     * Preserve old static API: Cors::sendHeaders()
     * Calls instance apply() and preflight handling like before.
     */
    public static function sendHeaders(): void
    {
        $instance = new self();
        if ($instance->handlePreflight()) {
            return;
        }
        $instance->apply();
    }
}