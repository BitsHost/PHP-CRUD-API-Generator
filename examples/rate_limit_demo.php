<?php
/**
 * Rate Limiting Demo Script
 * 
 * This script demonstrates how rate limiting works with the PHP CRUD API Generator.
 * Run this script to see rate limiting in action.
 * 
 * Usage: php examples/rate_limit_demo.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\RateLimiter;

echo "==============================================\n";
echo "  Rate Limiting Demo\n";
echo "==============================================\n\n";

// Create a rate limiter with strict limits for demo
$limiter = new RateLimiter([
    'enabled' => true,
    'max_requests' => 5,
    'window_seconds' => 10,
    'storage_dir' => __DIR__ . '/../storage/rate_limits'
]);

$identifier = 'demo_user_' . uniqid();

echo "Configuration:\n";
echo "- Max Requests: 5\n";
echo "- Window: 10 seconds\n";
echo "- Identifier: $identifier\n\n";

echo "Making requests...\n\n";

// Make 10 requests (should hit rate limit at request 6)
for ($i = 1; $i <= 10; $i++) {
    $allowed = $limiter->checkLimit($identifier);
    $count = $limiter->getRequestCount($identifier);
    $remaining = $limiter->getRemainingRequests($identifier);
    $resetTime = $limiter->getResetTime($identifier);
    
    $status = $allowed ? "✅ ALLOWED" : "❌ RATE LIMITED";
    
    echo "Request #$i: $status\n";
    echo "  - Count: $count\n";
    echo "  - Remaining: $remaining\n";
    echo "  - Reset in: {$resetTime}s\n";
    
    if (!$allowed) {
        echo "  - Headers:\n";
        foreach ($limiter->getHeaders($identifier) as $name => $value) {
            echo "    - $name: $value\n";
        }
    }
    
    echo "\n";
    
    // Small delay between requests
    usleep(100000); // 0.1 seconds
}

echo "\nWaiting for rate limit to reset...\n";
echo "Sleeping for 10 seconds...\n\n";
sleep(10);

echo "After reset:\n";
$allowed = $limiter->checkLimit($identifier);
$remaining = $limiter->getRemainingRequests($identifier);
echo "Request #11: " . ($allowed ? "✅ ALLOWED" : "❌ RATE LIMITED") . "\n";
echo "  - Remaining: $remaining\n\n";

// Cleanup
echo "Cleaning up demo data...\n";
$limiter->reset($identifier);
echo "Done!\n\n";

echo "==============================================\n";
echo "  Tips for Production:\n";
echo "==============================================\n";
echo "1. Set max_requests to reasonable limits (100-1000)\n";
echo "2. Use 60 second windows for most APIs\n";
echo "3. Monitor rate limit headers in responses\n";
echo "4. Implement exponential backoff in clients\n";
echo "5. Consider Redis for high-traffic APIs\n";
echo "6. Set up automated cleanup (cron job)\n";
echo "7. Log 429 responses for monitoring\n\n";
