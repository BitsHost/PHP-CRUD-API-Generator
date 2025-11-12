<?php
// Minimal test for RateLimitMiddleware allowed path

require_once __DIR__ . '/../vendor/autoload.php';

use App\Security\RateLimiter;
use App\Http\Middleware\RateLimitMiddleware;
use App\Observability\RequestLogger;
use App\Observability\Monitor;

$limiter = new RateLimiter(['enabled' => true, 'max_requests' => 100, 'window_seconds' => 60]);
$middleware = new RateLimitMiddleware($limiter, new RequestLogger(), new Monitor());

$ok = $middleware->checkAndRespond('test:cli');
if ($ok !== true) {
    fwrite(STDERR, "expected true on first request\n");
    exit(1);
}
echo "ratelimit allowed: PASS\n";
