<?php
namespace App;

/**
 * @deprecated Use \App\Http\Middleware\CorsMiddleware instead.
 * Backward compatibility wrapper allowing existing code calling
 * Cors::sendHeaders() to still function after migration to middleware.
 */
// Optional runtime deprecation notice (dev only): set API_GEN_DEPRECATIONS=1|trigger|log
$__dep = getenv('API_GEN_DEPRECATIONS') ?: ($_ENV['API_GEN_DEPRECATIONS'] ?? '');
if ($__dep !== '' && $__dep !== '0' && strtolower((string)$__dep) !== 'off') {
    $__msg = 'Deprecated class App\\Cors (root wrapper). Use App\\Http\\Middleware\\CorsMiddleware instead.';
    if ($__dep === 'log') { @error_log($__msg); } else { @trigger_error($__msg, E_USER_DEPRECATED); }
}
unset($__dep, $__msg);
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