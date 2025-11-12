<?php
declare(strict_types=1);

namespace App\Observability;

/**
 * API Monitoring System (Canonical)
 */
class Monitor
{
	/** @var array<string,mixed> */
	private array $config;
	private string $metricsDir;
	private string $alertsDir;
	/** @var array<int,array<string,mixed>> */
	private array $metrics = [];
	/** @var array<int,array<string,mixed>> */
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
	 * @param array<string,mixed> $config
	 */
	public function __construct(array $config = [])
	{
		$this->config = array_merge([
			'enabled' => true,
			// Adjusted defaults to keep same project_root/storage path from nested dir
			'metrics_dir' => __DIR__ . '/../../storage/metrics',
			'alerts_dir' => __DIR__ . '/../../storage/alerts',
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
	 * @param array<string,mixed> $data
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
	 * @param array<string,mixed> $request
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
	 * @param array<string,mixed> $context
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
	 * @param array<string,mixed> $data
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
	 * @return array<string,mixed>
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
			'in_memory' => [
				'metrics_count' => count($this->metrics),
				'alerts_count' => count($this->alerts),
			],
		];
	}
    
	/**
	 * @return array<string,mixed>
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
					if (($metric['data']['event'] ?? '') === 'auth_failure') {
						$authFailures++;
					}
					if (($metric['data']['event'] ?? '') === 'rate_limit_hit') {
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
	 * @return array<string,mixed>
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
			if ($load !== false) {
				$metrics['cpu_load'] = [
					'1min' => $load[0],
					'5min' => $load[1],
					'15min' => $load[2],
				];
			}
		}
        
		// Disk space
		$metrics['disk_free'] = @disk_free_space($this->metricsDir) ?: 0;
		$metrics['disk_total'] = @disk_total_space($this->metricsDir) ?: 0;
		$metrics['disk_usage_percent'] = $metrics['disk_total'] > 0
			? round((1 - ($metrics['disk_free'] / $metrics['disk_total'])) * 100, 2)
			: 0;
        
		return $metrics;
	}
    
	private function getUptime(): string
	{
		$files = glob($this->metricsDir . '/metrics_*.log') ?: [];
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
	 * @param array<string,mixed> $context
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
				@call_user_func($handler, $alert);
			}
		}
        
		return true;
	}
    
	/**
	 * @return array<int,array<string,mixed>>
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
            
			if (($metric['type'] ?? '') === self::METRIC_SECURITY 
				&& ($metric['data']['event'] ?? '') === 'auth_failure') {
				$failures++;
			}
		}
        
		fclose($handle);
        
		return $failures;
	}
    
	/**
	 * @param array<string,mixed> $metric
	 */
	private function writeMetric(array $metric): bool
	{
		$file = $this->getMetricsFile(date('Y-m-d'));
			$line = (string)json_encode($metric) . PHP_EOL;
        
		return file_put_contents($file, $line, FILE_APPEND | LOCK_EX) !== false;
	}
    
	/**
	 * @param array<string,mixed> $alert
	 */
	private function writeAlert(array $alert): bool
	{
		$file = $this->getAlertsFile(date('Y-m-d'));
			$line = (string)json_encode($alert) . PHP_EOL;
        
		return file_put_contents($file, $line, FILE_APPEND | LOCK_EX) !== false;
	}
    
	private function getMetricsFile(string $date): string
	{
		return $this->metricsDir . '/metrics_' . $date . '.log';
	}
    
	private function getAlertsFile(string $date): string
	{
		return $this->alertsDir . '/alerts_' . $date . '.log';
	}
    
	/**
	 * @return array{
	 *   total_requests:int,
	 *   total_errors:int,
	 *   error_rate:int|float,
	 *   avg_response_time:int|float,
	 *   min_response_time:int|float,
	 *   max_response_time:int|float,
	 *   auth_failures:int,
	 *   rate_limit_hits:int,
	 *   status_code_distribution:array<int,int>,
	 *   time_window:int
	 * }
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
    
	public function cleanup(): int
	{
		$deleted = 0;
		$cutoff = time() - ($this->config['retention_days'] * 86400);
        
		// Clean up metrics
	$files = glob($this->metricsDir . '/metrics_*.log') ?: [];
		foreach ($files as $file) {
			if (filemtime($file) < $cutoff) {
				if (unlink($file)) {
					$deleted++;
				}
			}
		}
        
		// Clean up alerts
	$files = glob($this->alertsDir . '/alerts_*.log') ?: [];
		foreach ($files as $file) {
			if (filemtime($file) < $cutoff) {
				if (unlink($file)) {
					$deleted++;
				}
			}
		}
        
		return $deleted;
	}
    
	public function exportMetrics(string $format = 'json'): string
	{
		$stats = $this->getStats();
		$health = $this->getHealthStatus();
        
		if ($format === 'prometheus') {
			return $this->exportPrometheus($stats, $health);
		}
        
		return (string) json_encode([
			'health' => $health,
			'stats' => $stats,
		], JSON_PRETTY_PRINT);
	}
    
	/**
	 * @param array<string,mixed> $stats
	 * @param array<string,mixed> $health
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
