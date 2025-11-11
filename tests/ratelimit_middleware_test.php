<?php
// Minimal test for RateLimitMiddleware allowed path

// Stubs for missing observability classes must be declared before any namespace includes
namespace App {
    if (!class_exists('App\\RequestLogger')) {
        class RequestLogger { public function __construct($cfg = []){} public function logRateLimit(){ } }
    }
    if (!class_exists('App\\Monitor')) {
        class Monitor { public function recordSecurityEvent(){} }
    }
}

namespace {
    require __DIR__ . '/../src/Http/Response.php';
    require __DIR__ . '/../src/RateLimiter.php';
    require __DIR__ . '/../src/Http/Middleware/RateLimitMiddleware.php';

    use App\RateLimiter;
    use App\Http\Middleware\RateLimitMiddleware;
    use App\RequestLogger;
    use App\Monitor;

    $limiter = new RateLimiter(['enabled' => true, 'max_requests' => 100, 'window_seconds' => 60]);
    $middleware = new RateLimitMiddleware($limiter, new RequestLogger(), new Monitor());

    $ok = $middleware->checkAndRespond('test:cli');
    if ($ok !== true) {
        fwrite(STDERR, "expected true on first request\n");
        exit(1);
    }
    echo "ratelimit allowed: PASS\n";
}
