<?php

namespace App;

/**
 * API Monitoring System
 * 
 * Comprehensive monitoring and alerting system for tracking API health, performance,
 * security events, and system resources. Provides real-time metrics collection,
 * automated threshold-based alerting, and health status reporting.
 * 
 * Features:
 * - Real-time metrics collection (requests, responses, errors, security events)
 * - Performance tracking (response times, throughput, request rates)
 * - Error monitoring with automatic critical alerts
 * - Security event tracking (auth failures, rate limits, suspicious activity)
 * - Health checks with 0-100 scoring algorithm
 * - Threshold-based alerting system (info, warning, critical)
 * - System resource monitoring (CPU, memory, disk)
 * - Metric aggregation and statistical analysis
 * - Time-series data storage with configurable retention
 * - Multiple export formats (JSON, Prometheus)
 * - Customizable alert handlers (email, webhook, Slack, etc.)
 * - Automatic cleanup of old metrics
 * - Dashboard-ready JSON API
 * 
 * @package App
 * @author Adrian D
 * @copyright 2025 BitHost
 * @license MIT
 * @version 1.4.0
 * @link https://upmvc.com
 * 
 * @example
 * // Basic setup
 * $monitor = new Monitor([
 *     'enabled' => true,
 *     'metrics_dir' => __DIR__ . '/storage/metrics',
 *     'thresholds' => [
 *         'error_rate' => 5.0,        // 5% errors triggers alert
 *         'response_time' => 1000,    // 1 second response time
 *         'auth_failures' => 10       // 10 failures per minute
 *     ]
 * ]);
 * 
 * // Record metrics
 * $monitor->recordRequest(['method' => 'GET', 'action' => 'list', 'table' => 'products']);
 * $monitor->recordResponse(200, 45.2, 1024); // status, time(ms), size(bytes)
 * $monitor->recordSecurityEvent('auth_failure', ['ip' => '192.168.1.100']);
 * 
 * // Get health status for dashboard
 * $health = $monitor->getHealthStatus();
 * // Returns: ['status' => 'healthy', 'health_score' => 95, 'statistics' => [...]]
 * 
 * // Export metrics for Prometheus
 * echo $monitor->exportPrometheus();
 */
class Monitor
{
    private array $config;
    private string $metricsDir;
    private string $alertsDir;
    private array $metrics = [];
    private array $alerts = [];
    
    // Metric types
    const METRIC_REQUEST = 'request';
    const METRIC_RESPONSE = 'response';
    const METRIC_ERROR = 'error';
    const METRIC_PERFORMANCE = 'performance';
    const METRIC_SECURITY = 'security';
    
    // Alert levels
    const ALERT_INFO = 'info';
    const ALERT_WARNING = 'warning';
    const ALERT_CRITICAL = 'critical';
    
    // Thresholds
    const DEFAULT_ERROR_RATE_THRESHOLD = 5.0; // 5% error rate
    const DEFAULT_RESPONSE_TIME_THRESHOLD = 1000; // 1 second
    const DEFAULT_RATE_LIMIT_THRESHOLD = 90; // 90% of limit
    const DEFAULT_AUTH_FAILURE_THRESHOLD = 10; // 10 failures per minute
    
    /**
     * Initialize Monitor
     * 
     * Sets up monitoring system with configurable thresholds, storage paths,
     * and alert handlers. Creates necessary directories for metrics and alerts.
     * 
     * @param array $config Configuration options:
     *   - enabled: bool Enable monitoring (default: true)
     *   - metrics_dir: string Directory for metric files (default: storage/metrics)
     *   - alerts_dir: string Directory for alert files (default: storage/alerts)
     *   - retention_days: int Days to keep metrics (default: 30)
     *   - check_interval: int Health check interval in seconds (default: 60)
     *   - thresholds: array Alert thresholds:
     *       * error_rate: float Max error percentage (default: 5.0)
     *       * response_time: int Max response time in ms (default: 1000)
     *       * rate_limit: int Rate limit usage percentage (default: 90)
     *       * auth_failures: int Max auth failures per minute (default: 10)
     *   - alert_handlers: array Callable functions for alert notifications
     *   - collect_system_metrics: bool Collect CPU/memory stats (default: true)
     * 
     * @example
     * $monitor = new Monitor([
     *     'thresholds' => [
     *         'error_rate' => 3.0,    // Stricter error threshold
     *         'response_time' => 500  // Faster response requirement
     *     ],
     *     'alert_handlers' => [
     *         function($alert) {
     *             // Send to Slack, email, etc.
     *             mail('admin@example.com', 'Alert: ' . $alert['message'], json_encode($alert));
     *         }
     *     ]
     * ]);
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enabled' => true,
            'metrics_dir' => __DIR__ . '/../storage/metrics',
            'alerts_dir' => __DIR__ . '/../storage/alerts',
            'retention_days' => 30,
            'check_interval' => 60, // seconds
            'thresholds' => [
                'error_rate' => self::DEFAULT_ERROR_RATE_THRESHOLD,
                'response_time' => self::DEFAULT_RESPONSE_TIME_THRESHOLD,
                'rate_limit' => self::DEFAULT_RATE_LIMIT_THRESHOLD,
                'auth_failures' => self::DEFAULT_AUTH_FAILURE_THRESHOLD,
            ],
            'alert_handlers' => [], // Callbacks for alerts
            'collect_system_metrics' => true,
        ], $config);
        
        $this->metricsDir = $this->config['metrics_dir'];
        $this->alertsDir = $this->config['alerts_dir'];
        
        $this->ensureDirectories();
    }
    
    /**
     * Ensure required directories exist
     */
    private function ensureDirectories(): void
    {
        if (!is_dir($this->metricsDir)) {
            mkdir($this->metricsDir, 0755, true);
        }
        if (!is_dir($this->alertsDir)) {
            mkdir($this->alertsDir, 0755, true);
        }
    }
    
    /**
     * Record a metric
     * 
     * Core method for recording any type of metric. Stores metric in memory for
     * aggregation and writes to daily metric file for persistence. Automatically
     * adds timestamp and datetime fields.
     * 
     * @param string $type Metric type constant (METRIC_REQUEST, METRIC_RESPONSE, 
     *   METRIC_ERROR, METRIC_PERFORMANCE, METRIC_SECURITY)
     * @param array $data Metric-specific data (varies by type)
     * @return bool True if recorded successfully, false if monitoring disabled
     * 
     * @example
     * // Record custom performance metric
     * $monitor->recordMetric(Monitor::METRIC_PERFORMANCE, [
     *     'operation' => 'database_query',
     *     'duration' => 125.5,
     *     'rows' => 1000
     * ]);
     */
    public function recordMetric(string $type, array $data): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }
        
        $metric = [
            'type' => $type,
            'timestamp' => microtime(true),
            'datetime' => date('Y-m-d H:i:s'),
            'data' => $data,
        ];
        
        // Store in memory for aggregation
        $this->metrics[] = $metric;
        
        // Write to file
        return $this->writeMetric($metric);
    }
    
    /**
     * Record request metric
     * 
     * Tracks incoming API requests for throughput analysis and pattern detection.
     * Records HTTP method, action, table, client IP, and authenticated user.
     * 
     * @param array $request Request data containing:
     *   - method: string HTTP method (GET, POST, PUT, DELETE)
     *   - action?: string API action (list, read, create, etc.)
     *   - table?: string Database table name
     *   - ip?: string Client IP address
     *   - user?: string Authenticated user identifier
     * @return bool True if recorded successfully, false if monitoring disabled
     * 
     * @example
     * $monitor->recordRequest([
     *     'method' => 'POST',
     *     'action' => 'create',
     *     'table' => 'orders',
     *     'ip' => '192.168.1.100',
     *     'user' => 'user_123'
     * ]);
     */
    public function recordRequest(array $request): bool
    {
        return $this->recordMetric(self::METRIC_REQUEST, [
            'method' => $request['method'] ?? 'UNKNOWN',
            'action' => $request['action'] ?? null,
            'table' => $request['table'] ?? null,
            'ip' => $request['ip'] ?? null,
            'user' => $request['user'] ?? null,
        ]);
    }
    
    /**
     * Record response metric
     * 
     * Tracks API response metrics including status code, timing, and size.
     * Automatically triggers alert if response time exceeds configured threshold.
     * Flags errors (4xx) and server errors (5xx) for statistical analysis.
     * 
     * @param int $statusCode HTTP status code (200, 404, 500, etc.)
     * @param float $responseTime Response time in milliseconds (use microtime for precision)
     * @param int $responseSize Response payload size in bytes (default: 0)
     * @return bool True if recorded successfully, false if monitoring disabled
     * 
     * @example
     * $start = microtime(true);
     * // ... process request ...
     * $responseTime = (microtime(true) - $start) * 1000; // Convert to ms
     * $responseBody = json_encode($data);
     * 
     * $monitor->recordResponse(200, $responseTime, strlen($responseBody));
     * // Triggers WARNING alert if $responseTime > threshold
     */
    public function recordResponse(int $statusCode, float $responseTime, int $responseSize = 0): bool
    {
        $data = [
            'status_code' => $statusCode,
            'response_time' => $responseTime,
            'response_size' => $responseSize,
            'is_error' => $statusCode >= 400,
            'is_server_error' => $statusCode >= 500,
        ];
        
        // Check for slow response
        if ($responseTime > $this->config['thresholds']['response_time']) {
            $this->triggerAlert(
                self::ALERT_WARNING,
                'Slow response detected',
                [
                    'response_time' => $responseTime,
                    'threshold' => $this->config['thresholds']['response_time'],
                ]
            );
        }
        
        return $this->recordMetric(self::METRIC_RESPONSE, $data);
    }
    
    /**
     * Record error metric
     * 
     * Tracks application errors and automatically triggers CRITICAL alert for
     * immediate notification. Use for exceptions, database errors, validation
     * failures, and other error conditions that require attention.
     * 
     * @param string $message Error message describing the issue
     * @param array $context Additional error context (stack trace, request data, etc.)
     * @return bool True if recorded successfully, false if monitoring disabled
     * 
     * @example
     * try {
     *     // Database operation
     * } catch (\PDOException $e) {
     *     $monitor->recordError('Database connection failed', [
     *         'error' => $e->getMessage(),
     *         'code' => $e->getCode(),
     *         'trace' => $e->getTraceAsString()
     *     ]);
     * }
     */
    public function recordError(string $message, array $context = []): bool
    {
        $data = [
            'message' => $message,
            'context' => $context,
        ];
        
        $this->triggerAlert(
            self::ALERT_CRITICAL,
            'Error occurred: ' . $message,
            $context
        );
        
        return $this->recordMetric(self::METRIC_ERROR, $data);
    }
    
    /**
     * Record security event
     * 
     * Tracks security-related events for intrusion detection and audit trails.
     * Automatically monitors authentication failure rates and rate limit violations,
     * triggering alerts when thresholds are exceeded.
     * 
     * @param string $event Event type (auth_failure, rate_limit_hit, suspicious_activity, etc.)
     * @param array $data Event-specific data:
     *   - For auth_failure: ip, username, reason
     *   - For rate_limit_hit: identifier, count, limit
     *   - For custom events: any relevant context
     * @return bool True if recorded successfully, false if monitoring disabled
     * 
     * @example
     * // Record authentication failure
     * $monitor->recordSecurityEvent('auth_failure', [
     *     'ip' => '192.168.1.100',
     *     'username' => 'admin',
     *     'reason' => 'invalid password'
     * ]);
     * // Triggers CRITICAL alert if failures > threshold in 1 minute
     * 
     * // Record rate limit hit
     * $monitor->recordSecurityEvent('rate_limit_hit', [
     *     'identifier' => 'api_key_abc123',
     *     'count' => 1050,
     *     'limit' => 1000
     * ]);
     * // Triggers WARNING alert
     */
    public function recordSecurityEvent(string $event, array $data = []): bool
    {
        $data['event'] = $event;
        
        // Alert on authentication failures
        if ($event === 'auth_failure') {
            $recentFailures = $this->getRecentAuthFailures();
            if ($recentFailures >= $this->config['thresholds']['auth_failures']) {
                $this->triggerAlert(
                    self::ALERT_CRITICAL,
                    'High authentication failure rate',
                    [
                        'failures' => $recentFailures,
                        'threshold' => $this->config['thresholds']['auth_failures'],
                        'ip' => $data['ip'] ?? 'unknown',
                    ]
                );
            }
        }
        
        // Alert on rate limit hits
        if ($event === 'rate_limit_hit') {
            $this->triggerAlert(
                self::ALERT_WARNING,
                'Rate limit exceeded',
                $data
            );
        }
        
        return $this->recordMetric(self::METRIC_SECURITY, $data);
    }
    
    /**
     * Get health status
     * 
     * Calculates comprehensive health score (0-100) based on error rates, response times,
     * recent alerts, and system metrics. Returns 'healthy', 'degraded', or 'critical'
     * status for dashboard display and uptime monitoring.
     * 
     * Scoring Algorithm:
     * - Start at 100 points
     * - High error rate: -30 points
     * - Slow response time: -20 points
     * - Recent critical alerts: -25 points per alert
     * - Status: healthy (80-100), degraded (50-79), critical (0-49)
     * 
     * @return array Health status containing:
     *   - status: string 'healthy', 'degraded', or 'critical'
     *   - health_score: int 0-100 health score
     *   - timestamp: string Current datetime
     *   - uptime: string System uptime
     *   - statistics: array Request/error/performance stats
     *   - system_metrics: array CPU, memory, disk usage
     *   - issues: array List of detected problems
     *   - recent_alerts: array Last 5 minutes of alerts
     * 
     * @example
     * $health = $monitor->getHealthStatus();
     * 
     * if ($health['status'] === 'critical') {
     *     sendAlert('API health critical!', $health);
     * }
     * 
     * echo "Health Score: {$health['health_score']}/100\n";
     * echo "Error Rate: {$health['statistics']['error_rate']}%\n";
     * foreach ($health['issues'] as $issue) {
     *     echo "⚠️  $issue\n";
     * }
     */
    public function getHealthStatus(): array
    {
        $stats = $this->getStats();
        $systemMetrics = $this->config['collect_system_metrics'] 
            ? $this->getSystemMetrics() 
            : [];
        
        // Calculate health score (0-100)
        $healthScore = 100;
        $issues = [];
        
        // Check error rate
        if ($stats['error_rate'] > $this->config['thresholds']['error_rate']) {
            $healthScore -= 30;
            $issues[] = "High error rate: {$stats['error_rate']}%";
        }
        
        // Check average response time
        if ($stats['avg_response_time'] > $this->config['thresholds']['response_time']) {
            $healthScore -= 20;
            $issues[] = "Slow response time: {$stats['avg_response_time']}ms";
        }
        
        // Check for recent critical alerts
        $recentAlerts = $this->getRecentAlerts(5);
        $criticalAlerts = array_filter($recentAlerts, fn($a) => $a['level'] === self::ALERT_CRITICAL);
        if (count($criticalAlerts) > 0) {
            $healthScore -= 25;
            $issues[] = count($criticalAlerts) . " critical alert(s) in last 5 minutes";
        }
        
        // Determine status
        $status = 'healthy';
        if ($healthScore < 50) {
            $status = 'critical';
        } elseif ($healthScore < 80) {
            $status = 'degraded';
        }
        
        return [
            'status' => $status,
            'health_score' => max(0, $healthScore),
            'timestamp' => date('Y-m-d H:i:s'),
            'uptime' => $this->getUptime(),
            'statistics' => $stats,
            'system_metrics' => $systemMetrics,
            'issues' => $issues,
            'recent_alerts' => $recentAlerts,
        ];
    }
    
    /**
     * Get aggregated statistics
     * 
     * Calculates statistical analysis of metrics within specified time window.
     * Provides request counts, error rates, performance metrics, and trends
     * for dashboard visualization and capacity planning.
     * 
     * @param int $minutes Time window in minutes for analysis (default: 60)
     *   Use 5 for real-time, 60 for hourly, 1440 for daily analysis
     * @return array Statistics containing:
     *   - total_requests: int Total requests in window
     *   - successful_requests: int 2xx/3xx responses
     *   - error_count: int 4xx/5xx responses
     *   - error_rate: float Percentage of errors
     *   - avg_response_time: float Average response time in ms
     *   - min_response_time: float Fastest response in ms
     *   - max_response_time: float Slowest response in ms
     *   - requests_per_minute: float Request throughput
     *   - auth_failures: int Failed authentications
     *   - rate_limit_hits: int Rate limit violations
     *   - top_endpoints: array Most frequently accessed
     * 
     * @example
     * // Get last hour statistics
     * $stats = $monitor->getStats(60);
     * 
     * // Get real-time stats (5 minutes)
     * $realtimeStats = $monitor->getStats(5);
     * 
     * // Display on dashboard
     * echo "Request Rate: {$stats['requests_per_minute']}/min\n";
     * echo "Error Rate: {$stats['error_rate']}%\n";
     * echo "Avg Response: {$stats['avg_response_time']}ms\n";
     */
    public function getStats(int $minutes = 60): array
    {
        $cutoff = time() - ($minutes * 60);
        $metricsFile = $this->getMetricsFile(date('Y-m-d'));
        
        if (!file_exists($metricsFile)) {
            return $this->getEmptyStats();
        }
        
        $totalRequests = 0;
        $totalErrors = 0;
        $responseTimes = [];
        $statusCodes = [];
        $authFailures = 0;
        $rateLimitHits = 0;
        
        $handle = fopen($metricsFile, 'r');
        if (!$handle) {
            return $this->getEmptyStats();
        }
        
        while (($line = fgets($handle)) !== false) {
            $metric = json_decode(trim($line), true);
            if (!$metric || $metric['timestamp'] < $cutoff) {
                continue;
            }
            
            switch ($metric['type']) {
                case self::METRIC_REQUEST:
                    $totalRequests++;
                    break;
                    
                case self::METRIC_RESPONSE:
                    $responseTimes[] = $metric['data']['response_time'];
                    $statusCodes[] = $metric['data']['status_code'];
                    if ($metric['data']['is_error']) {
                        $totalErrors++;
                    }
                    break;
                    
                case self::METRIC_SECURITY:
                    if ($metric['data']['event'] === 'auth_failure') {
                        $authFailures++;
                    }
                    if ($metric['data']['event'] === 'rate_limit_hit') {
                        $rateLimitHits++;
                    }
                    break;
            }
        }
        
        fclose($handle);
        
        $avgResponseTime = !empty($responseTimes) 
            ? array_sum($responseTimes) / count($responseTimes) 
            : 0;
        
        $errorRate = $totalRequests > 0 
            ? ($totalErrors / $totalRequests) * 100 
            : 0;
        
        return [
            'total_requests' => $totalRequests,
            'total_errors' => $totalErrors,
            'error_rate' => round($errorRate, 2),
            'avg_response_time' => round($avgResponseTime, 2),
            'min_response_time' => !empty($responseTimes) ? round(min($responseTimes), 2) : 0,
            'max_response_time' => !empty($responseTimes) ? round(max($responseTimes), 2) : 0,
            'auth_failures' => $authFailures,
            'rate_limit_hits' => $rateLimitHits,
            'status_code_distribution' => array_count_values($statusCodes),
            'time_window' => $minutes,
        ];
    }
    
    /**
     * Get system metrics
     * 
     * @return array System metrics
     */
    private function getSystemMetrics(): array
    {
        $metrics = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit'),
        ];
        
        // CPU load (Unix/Linux only)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $metrics['cpu_load'] = [
                '1min' => $load[0],
                '5min' => $load[1],
                '15min' => $load[2],
            ];
        }
        
        // Disk space
        $metrics['disk_free'] = disk_free_space($this->metricsDir);
        $metrics['disk_total'] = disk_total_space($this->metricsDir);
        $metrics['disk_usage_percent'] = round((1 - ($metrics['disk_free'] / $metrics['disk_total'])) * 100, 2);
        
        return $metrics;
    }
    
    /**
     * Get uptime
     * 
     * @return string Uptime string
     */
    private function getUptime(): string
    {
        $files = glob($this->metricsDir . '/metrics_*.log');
        if (empty($files)) {
            return 'Unknown';
        }
        
        $oldestFile = min($files);
        $startTime = filemtime($oldestFile);
        $uptime = time() - $startTime;
        
        $days = floor($uptime / 86400);
        $hours = floor(($uptime % 86400) / 3600);
        $minutes = floor(($uptime % 3600) / 60);
        
        return sprintf('%d days, %d hours, %d minutes', $days, $hours, $minutes);
    }
    
    /**
     * Trigger an alert
     * 
     * @param string $level Alert level
     * @param string $message Alert message
     * @param array $context Additional context
     * @return bool
     */
    public function triggerAlert(string $level, string $message, array $context = []): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }
        
        $alert = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => microtime(true),
            'datetime' => date('Y-m-d H:i:s'),
        ];
        
        // Store alert
        $this->alerts[] = $alert;
        $this->writeAlert($alert);
        
        // Execute alert handlers
        foreach ($this->config['alert_handlers'] as $handler) {
            if (is_callable($handler)) {
                call_user_func($handler, $alert);
            }
        }
        
        return true;
    }
    
    /**
     * Get recent alerts
     * 
     * @param int $minutes Time window in minutes
     * @return array Recent alerts
     */
    public function getRecentAlerts(int $minutes = 60): array
    {
        $cutoff = time() - ($minutes * 60);
        $alertsFile = $this->getAlertsFile(date('Y-m-d'));
        
        if (!file_exists($alertsFile)) {
            return [];
        }
        
        $alerts = [];
        $handle = fopen($alertsFile, 'r');
        if (!$handle) {
            return [];
        }
        
        while (($line = fgets($handle)) !== false) {
            $alert = json_decode(trim($line), true);
            if ($alert && $alert['timestamp'] >= $cutoff) {
                $alerts[] = $alert;
            }
        }
        
        fclose($handle);
        
        return $alerts;
    }
    
    /**
     * Get recent authentication failures
     * 
     * @param int $minutes Time window in minutes (default: 1)
     * @return int Number of failures
     */
    private function getRecentAuthFailures(int $minutes = 1): int
    {
        $cutoff = time() - ($minutes * 60);
        $metricsFile = $this->getMetricsFile(date('Y-m-d'));
        
        if (!file_exists($metricsFile)) {
            return 0;
        }
        
        $failures = 0;
        $handle = fopen($metricsFile, 'r');
        if (!$handle) {
            return 0;
        }
        
        while (($line = fgets($handle)) !== false) {
            $metric = json_decode(trim($line), true);
            if (!$metric || $metric['timestamp'] < $cutoff) {
                continue;
            }
            
            if ($metric['type'] === self::METRIC_SECURITY 
                && ($metric['data']['event'] ?? '') === 'auth_failure') {
                $failures++;
            }
        }
        
        fclose($handle);
        
        return $failures;
    }
    
    /**
     * Write metric to file
     * 
     * @param array $metric Metric data
     * @return bool
     */
    private function writeMetric(array $metric): bool
    {
        $file = $this->getMetricsFile(date('Y-m-d'));
        $line = json_encode($metric) . PHP_EOL;
        
        return file_put_contents($file, $line, FILE_APPEND | LOCK_EX) !== false;
    }
    
    /**
     * Write alert to file
     * 
     * @param array $alert Alert data
     * @return bool
     */
    private function writeAlert(array $alert): bool
    {
        $file = $this->getAlertsFile(date('Y-m-d'));
        $line = json_encode($alert) . PHP_EOL;
        
        return file_put_contents($file, $line, FILE_APPEND | LOCK_EX) !== false;
    }
    
    /**
     * Get metrics file path
     * 
     * @param string $date Date (Y-m-d format)
     * @return string File path
     */
    private function getMetricsFile(string $date): string
    {
        return $this->metricsDir . '/metrics_' . $date . '.log';
    }
    
    /**
     * Get alerts file path
     * 
     * @param string $date Date (Y-m-d format)
     * @return string File path
     */
    private function getAlertsFile(string $date): string
    {
        return $this->alertsDir . '/alerts_' . $date . '.log';
    }
    
    /**
     * Get empty statistics array
     * 
     * @return array
     */
    private function getEmptyStats(): array
    {
        return [
            'total_requests' => 0,
            'total_errors' => 0,
            'error_rate' => 0,
            'avg_response_time' => 0,
            'min_response_time' => 0,
            'max_response_time' => 0,
            'auth_failures' => 0,
            'rate_limit_hits' => 0,
            'status_code_distribution' => [],
            'time_window' => 0,
        ];
    }
    
    /**
     * Clean up old metric and alert files
     * 
     * @return int Number of files deleted
     */
    public function cleanup(): int
    {
        $deleted = 0;
        $cutoff = time() - ($this->config['retention_days'] * 86400);
        
        // Clean up metrics
        $files = glob($this->metricsDir . '/metrics_*.log');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        // Clean up alerts
        $files = glob($this->alertsDir . '/alerts_*.log');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Export metrics for external monitoring tools
     * 
     * @param string $format Export format (json, prometheus)
     * @return string Exported data
     */
    public function exportMetrics(string $format = 'json'): string
    {
        $stats = $this->getStats();
        $health = $this->getHealthStatus();
        
        if ($format === 'prometheus') {
            return $this->exportPrometheus($stats, $health);
        }
        
        return json_encode([
            'health' => $health,
            'stats' => $stats,
        ], JSON_PRETTY_PRINT);
    }
    
    /**
     * Export metrics in Prometheus format
     * 
     * @param array $stats Statistics
     * @param array $health Health status
     * @return string Prometheus metrics
     */
    private function exportPrometheus(array $stats, array $health): string
    {
        $lines = [];
        
        // Health score
        $lines[] = '# HELP api_health_score API health score (0-100)';
        $lines[] = '# TYPE api_health_score gauge';
        $lines[] = 'api_health_score ' . $health['health_score'];
        
        // Request count
        $lines[] = '# HELP api_requests_total Total number of API requests';
        $lines[] = '# TYPE api_requests_total counter';
        $lines[] = 'api_requests_total ' . $stats['total_requests'];
        
        // Error count
        $lines[] = '# HELP api_errors_total Total number of errors';
        $lines[] = '# TYPE api_errors_total counter';
        $lines[] = 'api_errors_total ' . $stats['total_errors'];
        
        // Error rate
        $lines[] = '# HELP api_error_rate Error rate percentage';
        $lines[] = '# TYPE api_error_rate gauge';
        $lines[] = 'api_error_rate ' . $stats['error_rate'];
        
        // Response times
        $lines[] = '# HELP api_response_time_ms Response time in milliseconds';
        $lines[] = '# TYPE api_response_time_ms gauge';
        $lines[] = 'api_response_time_ms{type="avg"} ' . $stats['avg_response_time'];
        $lines[] = 'api_response_time_ms{type="min"} ' . $stats['min_response_time'];
        $lines[] = 'api_response_time_ms{type="max"} ' . $stats['max_response_time'];
        
        // Auth failures
        $lines[] = '# HELP api_auth_failures_total Total authentication failures';
        $lines[] = '# TYPE api_auth_failures_total counter';
        $lines[] = 'api_auth_failures_total ' . $stats['auth_failures'];
        
        // Rate limit hits
        $lines[] = '# HELP api_rate_limit_hits_total Total rate limit hits';
        $lines[] = '# TYPE api_rate_limit_hits_total counter';
        $lines[] = 'api_rate_limit_hits_total ' . $stats['rate_limit_hits'];
        
        return implode("\n", $lines) . "\n";
    }
}
