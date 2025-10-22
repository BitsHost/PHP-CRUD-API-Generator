
    // ========================================
    // MONITORING CONFIGURATION
    // ========================================
    'monitoring' => [
        'enabled' => true,                          // Enable/disable monitoring
        'metrics_dir' => __DIR__ . '/../storage/metrics', // Metrics storage directory
        'alerts_dir' => __DIR__ . '/../storage/alerts',   // Alerts storage directory
        'retention_days' => 30,                     // Keep metrics for X days
        'check_interval' => 60,                     // Health check interval (seconds)
        
        // Alert thresholds
        'thresholds' => [
            'error_rate' => 5.0,                   // Alert if error rate > 5%
            'response_time' => 1000,               // Alert if response time > 1000ms
            'rate_limit' => 90,                    // Alert if rate limit usage > 90%
            'auth_failures' => 10,                 // Alert if > 10 auth failures per minute
        ],
        
        // Alert handlers (callables that receive alert data)
        'alert_handlers' => [
            // Example: function($alert) { error_log("ALERT: " . $alert['message']); }
            // Example: function($alert) { mail('admin@example.com', 'API Alert', $alert['message']); }
            // Example: function($alert) { /* Send to Slack/Discord webhook */ }
        ],
        
        // Collect system metrics (CPU, memory, disk)
        'collect_system_metrics' => true,
    ],
