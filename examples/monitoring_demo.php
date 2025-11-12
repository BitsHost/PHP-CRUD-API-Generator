<?php

/**
 * Monitoring System Demo
 * 
 * Demonstrates the monitoring capabilities of the API
 * 
 * Usage: php examples/monitoring_demo.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Observability\Monitor;

echo "=========================================\n";
echo "  API MONITORING SYSTEM DEMO\n";
echo "=========================================\n\n";

// Initialize monitor with demo configuration
$config = [
    'enabled' => true,
    'metrics_dir' => __DIR__ . '/../storage/metrics',
    'alerts_dir' => __DIR__ . '/../storage/alerts',
    'retention_days' => 7,
    'thresholds' => [
        'error_rate' => 5.0,
        'response_time' => 500, // Lowered for demo
        'auth_failures' => 3,   // Lowered for demo
    ],
    'alert_handlers' => [
        function($alert) {
            echo "  📢 ALERT: [{$alert['level']}] {$alert['message']}\n";
        }
    ],
    'collect_system_metrics' => true,
];

$monitor = new Monitor($config);

// ===========================================
// DEMO 1: Record Normal Requests
// ===========================================
echo "DEMO 1: Recording Normal Requests\n";
echo "-----------------------------------\n";

for ($i = 0; $i < 10; $i++) {
    $monitor->recordRequest([
        'method' => 'GET',
        'action' => 'list',
        'table' => 'users',
        'ip' => '192.168.1.' . rand(1, 100),
        'user' => 'testuser',
    ]);
    
    $monitor->recordResponse(200, rand(50, 200), rand(500, 5000));
}

echo "✅ Recorded 10 successful requests\n\n";

// ===========================================
// DEMO 2: Record Error Responses
// ===========================================
echo "DEMO 2: Recording Error Responses\n";
echo "-----------------------------------\n";

for ($i = 0; $i < 3; $i++) {
    $monitor->recordRequest([
        'method' => 'POST',
        'action' => 'create',
        'table' => 'products',
        'ip' => '192.168.1.50',
        'user' => 'testuser',
    ]);
    
    $monitor->recordResponse(500, rand(100, 300), 100);
    $monitor->recordError('Database connection failed', [
        'host' => 'localhost',
        'database' => 'test_db',
    ]);
}

echo "✅ Recorded 3 error responses\n\n";

// ===========================================
// DEMO 3: Record Slow Responses
// ===========================================
echo "DEMO 3: Recording Slow Responses (Triggers Alert)\n";
echo "---------------------------------------------------\n";

$monitor->recordRequest([
    'method' => 'GET',
    'action' => 'read',
    'table' => 'orders',
    'ip' => '192.168.1.100',
    'user' => 'slowuser',
]);

$monitor->recordResponse(200, 1500, 10000); // Slow response
echo "✅ Recorded slow response (should trigger alert above)\n\n";

// ===========================================
// DEMO 4: Record Authentication Failures
// ===========================================
echo "DEMO 4: Recording Authentication Failures\n";
echo "-------------------------------------------\n";

for ($i = 0; $i < 5; $i++) {
    $monitor->recordSecurityEvent('auth_failure', [
        'method' => 'basic',
        'ip' => '192.168.1.200',
        'reason' => 'Invalid credentials',
    ]);
}

echo "✅ Recorded 5 authentication failures (may trigger alert)\n\n";

// ===========================================
// DEMO 5: Record Rate Limit Hits
// ===========================================
echo "DEMO 5: Recording Rate Limit Hits\n";
echo "-----------------------------------\n";

for ($i = 0; $i < 3; $i++) {
    $monitor->recordSecurityEvent('rate_limit_hit', [
        'identifier' => 'ip:192.168.1.150',
        'requests' => 100,
        'limit' => 100,
    ]);
}

echo "✅ Recorded 3 rate limit hits\n\n";

// ===========================================
// DEMO 6: Get Health Status
// ===========================================
echo "DEMO 6: Checking Health Status\n";
echo "--------------------------------\n";

$health = $monitor->getHealthStatus();

echo "Status: " . strtoupper($health['status']) . "\n";
echo "Health Score: {$health['health_score']}/100\n";
echo "Uptime: {$health['uptime']}\n";

if (!empty($health['issues'])) {
    echo "\n⚠️  Active Issues:\n";
    foreach ($health['issues'] as $issue) {
        echo "  - $issue\n";
    }
}

echo "\n";

// ===========================================
// DEMO 7: Get Statistics
// ===========================================
echo "DEMO 7: Viewing Statistics\n";
echo "---------------------------\n";

$stats = $monitor->getStats(60);

echo "Total Requests: {$stats['total_requests']}\n";
echo "Total Errors: {$stats['total_errors']}\n";
echo "Error Rate: {$stats['error_rate']}%\n";
echo "Avg Response Time: {$stats['avg_response_time']}ms\n";
echo "Min Response Time: {$stats['min_response_time']}ms\n";
echo "Max Response Time: {$stats['max_response_time']}ms\n";
echo "Auth Failures: {$stats['auth_failures']}\n";
echo "Rate Limit Hits: {$stats['rate_limit_hits']}\n";

if (!empty($stats['status_code_distribution'])) {
    echo "\nStatus Code Distribution:\n";
    foreach ($stats['status_code_distribution'] as $code => $count) {
        echo "  {$code}: {$count} requests\n";
    }
}

echo "\n";

// ===========================================
// DEMO 8: View Recent Alerts
// ===========================================
echo "DEMO 8: Viewing Recent Alerts\n";
echo "-------------------------------\n";

$alerts = $monitor->getRecentAlerts(60);

if (empty($alerts)) {
    echo "No recent alerts\n";
} else {
    echo "Found " . count($alerts) . " alert(s):\n\n";
    foreach ($alerts as $alert) {
        $levelIcon = match($alert['level']) {
            'critical' => '🚨',
            'warning' => '⚠️',
            default => 'ℹ️'
        };
        echo "{$levelIcon} [{$alert['level']}] {$alert['message']}\n";
        echo "   Time: {$alert['datetime']}\n";
        if (!empty($alert['context'])) {
            echo "   Context: " . json_encode($alert['context']) . "\n";
        }
        echo "\n";
    }
}

// ===========================================
// DEMO 9: Export Metrics (JSON)
// ===========================================
echo "DEMO 9: Exporting Metrics (JSON)\n";
echo "----------------------------------\n";

$jsonExport = $monitor->exportMetrics('json');
$jsonData = json_decode($jsonExport, true);

echo "Exported JSON data:\n";
echo "  - Health Status: {$jsonData['health']['status']}\n";
echo "  - Health Score: {$jsonData['health']['health_score']}\n";
echo "  - Total Requests: {$jsonData['stats']['total_requests']}\n\n";

// ===========================================
// DEMO 10: Export Metrics (Prometheus)
// ===========================================
echo "DEMO 10: Exporting Metrics (Prometheus)\n";
echo "----------------------------------------\n";

$prometheusExport = $monitor->exportMetrics('prometheus');
$lines = explode("\n", trim($prometheusExport));

echo "Prometheus format (first 10 lines):\n";
foreach (array_slice($lines, 0, 10) as $line) {
    if (!empty(trim($line))) {
        echo "  $line\n";
    }
}

echo "\n";

// ===========================================
// DEMO 11: Cleanup Old Files
// ===========================================
echo "DEMO 11: Cleanup Old Files\n";
echo "---------------------------\n";

$deleted = $monitor->cleanup();
echo "✅ Cleaned up $deleted old file(s)\n\n";

// ===========================================
// DEMO 12: System Metrics
// ===========================================
echo "DEMO 12: System Metrics\n";
echo "------------------------\n";

if (!empty($health['system_metrics'])) {
    $metrics = $health['system_metrics'];
    
    echo "Memory Usage: " . round($metrics['memory_usage'] / 1024 / 1024, 2) . " MB\n";
    echo "Memory Peak: " . round($metrics['memory_peak'] / 1024 / 1024, 2) . " MB\n";
    echo "Memory Limit: {$metrics['memory_limit']}\n";
    echo "Disk Free: " . round($metrics['disk_free'] / 1024 / 1024 / 1024, 2) . " GB\n";
    echo "Disk Usage: {$metrics['disk_usage_percent']}%\n";
    
    if (isset($metrics['cpu_load'])) {
        echo "CPU Load (1/5/15 min): {$metrics['cpu_load']['1min']} / {$metrics['cpu_load']['5min']} / {$metrics['cpu_load']['15min']}\n";
    }
} else {
    echo "System metrics not available\n";
}

echo "\n";

// ===========================================
// SUMMARY
// ===========================================
echo "=========================================\n";
echo "  DEMO COMPLETE!\n";
echo "=========================================\n\n";

echo "📊 What was demonstrated:\n";
echo "  ✅ Recording requests and responses\n";
echo "  ✅ Recording errors\n";
echo "  ✅ Recording slow responses (with alerts)\n";
echo "  ✅ Recording authentication failures\n";
echo "  ✅ Recording rate limit hits\n";
echo "  ✅ Checking health status\n";
echo "  ✅ Viewing statistics\n";
echo "  ✅ Viewing recent alerts\n";
echo "  ✅ Exporting metrics (JSON & Prometheus)\n";
echo "  ✅ Cleanup old files\n";
echo "  ✅ System metrics collection\n\n";

echo "🎯 Next Steps:\n";
echo "  1. Integrate Monitor into Router.php (see MONITOR_INTEGRATION_GUIDE.php)\n";
echo "  2. Configure alert handlers in config/api.php\n";
echo "  3. Set up health check endpoint (health.php)\n";
echo "  4. Open dashboard.html in browser for real-time monitoring\n";
echo "  5. Set up external monitoring (Prometheus, Grafana, etc.)\n\n";

echo "📁 Files Created:\n";
echo "  - Metrics: storage/metrics/metrics_" . date('Y-m-d') . ".log\n";
echo "  - Alerts: storage/alerts/alerts_" . date('Y-m-d') . ".log\n\n";

echo "🔗 Access Points:\n";
echo "  - Health Check: http://your-api/health.php\n";
echo "  - Dashboard: http://your-api/dashboard.html\n";
echo "  - Prometheus: http://your-api/health.php?format=prometheus\n\n";
