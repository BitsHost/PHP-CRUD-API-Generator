<?php
/**
 * Request Logging Demo Script
 * 
 * This script demonstrates request logging capabilities.
 * 
 * Usage: php examples/logging_demo.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Observability\RequestLogger;

echo "==============================================\n";
echo "  Request Logging Demo\n";
echo "==============================================\n\n";

// Create a logger
$logger = new RequestLogger([
    'enabled' => true,
    'log_dir' => __DIR__ . '/../logs',
    'log_level' => 'info',
    'log_headers' => true,
    'log_body' => true,
]);

echo "Configuration:\n";
echo "- Log Directory: " . __DIR__ . "/../logs\n";
echo "- Log Level: info\n";
echo "- Headers Logging: enabled\n";
echo "- Body Logging: enabled\n\n";

// Demo 1: Log a successful request
echo "Demo 1: Logging a successful request\n";
echo "--------------------------------------\n";

$request = [
    'method' => 'GET',
    'action' => 'list',
    'table' => 'users',
    'ip' => '127.0.0.1',
    'user' => 'admin',
    'query' => ['page' => 1, 'limit' => 20],
    'headers' => [
        'User-Agent' => 'Mozilla/5.0',
        'Accept' => 'application/json'
    ]
];

$response = [
    'status_code' => 200,
    'body' => [
        'data' => [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob']
        ],
        'meta' => ['total' => 2]
    ],
    'size' => 150
];

$logger->logRequest($request, $response, 0.045);
echo "✅ Logged successful GET /list request (45ms)\n\n";

// Demo 2: Log with sensitive data
echo "Demo 2: Logging with sensitive data redaction\n";
echo "-----------------------------------------------\n";

$request = [
    'method' => 'POST',
    'action' => 'create',
    'table' => 'users',
    'body' => [
        'username' => 'newuser',
        'email' => 'user@example.com',
        'password' => 'supersecret123',  // Will be redacted
        'api_key' => 'sk_live_abc123'     // Will be redacted
    ]
];

$response = [
    'status_code' => 201,
    'body' => ['id' => 3, 'username' => 'newuser'],
    'size' => 50
];

$logger->logRequest($request, $response, 0.012);
echo "✅ Logged POST /create request with redacted sensitive data\n\n";

// Demo 3: Log authentication attempts
echo "Demo 3: Logging authentication\n";
echo "--------------------------------\n";

$logger->logAuth('jwt', true, 'admin');
echo "✅ Logged successful JWT authentication\n";

$logger->logAuth('basic', false, 'hacker', 'Invalid credentials');
echo "❌ Logged failed Basic Auth attempt\n\n";

// Demo 4: Log rate limit hit
echo "Demo 4: Logging rate limit\n";
echo "----------------------------\n";

$logger->logRateLimit('ip:192.168.1.100', 100, 100);
echo "⚠️  Logged rate limit exceeded\n\n";

// Demo 5: Log error
echo "Demo 5: Logging an error\n";
echo "-------------------------\n";

$logger->logError('Database connection timeout', [
    'host' => 'db.example.com',
    'port' => 3306,
    'timeout' => 30,
    'query' => 'SELECT * FROM users'
]);
echo "❌ Logged database error\n\n";

// Demo 6: Quick request logging
echo "Demo 6: Quick request logging\n";
echo "------------------------------\n";

$logger->logQuickRequest('DELETE', 'delete', 'products', 'user:admin');
echo "✅ Logged quick DELETE request\n\n";

// Demo 7: Get statistics
echo "Demo 7: Log statistics\n";
echo "-----------------------\n";

$stats = $logger->getStats();
echo "Today's Statistics:\n";
echo "  - Total Requests: " . $stats['total_requests'] . "\n";
echo "  - Errors: " . $stats['errors'] . "\n";
echo "  - Warnings: " . $stats['warnings'] . "\n";
echo "  - Auth Failures: " . $stats['auth_failures'] . "\n";
echo "  - Rate Limits: " . $stats['rate_limits'] . "\n\n";

// Demo 8: Check log file
echo "Demo 8: Log file location\n";
echo "--------------------------\n";

$logFile = __DIR__ . '/../logs/api_' . date('Y-m-d') . '.log';
if (file_exists($logFile)) {
    $size = filesize($logFile);
    echo "✅ Log file created: $logFile\n";
    echo "   Size: " . round($size / 1024, 2) . " KB\n";
    echo "   Lines: " . count(file($logFile)) . "\n\n";
    
    echo "Last 5 lines of log:\n";
    echo str_repeat('-', 80) . "\n";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -5);
    foreach ($lastLines as $line) {
        echo $line;
    }
    echo str_repeat('-', 80) . "\n\n";
} else {
    echo "❌ Log file not found\n\n";
}

echo "==============================================\n";
echo "  Logging Demo Complete!\n";
echo "==============================================\n\n";

echo "Tips for Production:\n";
echo "1. Set log_level to 'warning' or 'error' in production\n";
echo "2. Disable log_response_body to reduce log size\n";
echo "3. Set up log rotation (automated cleanup)\n";
echo "4. Monitor log files for errors and suspicious activity\n";
echo "5. Use log aggregation tools (ELK, Splunk, etc.)\n";
echo "6. Set up alerts for critical errors\n";
echo "7. Regularly review authentication failures\n\n";

echo "View your logs: \n";
echo "  Log directory: " . __DIR__ . "/../logs/\n";
echo "  Today's log: $logFile\n\n";
