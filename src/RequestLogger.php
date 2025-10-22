<?php
declare(strict_types=1);

namespace App;

/**
 * Request Logger
 * 
 * Comprehensive logging system for API requests, responses, errors, and security events.
 * Provides detailed audit trails with sensitive data redaction, automatic log rotation,
 * and statistics collection for monitoring and debugging purposes.
 * 
 * Features:
 * - Multi-level logging (debug, info, warning, error)
 * - Automatic sensitive data redaction (passwords, tokens, API keys)
 * - Request/response body logging with size limits
 * - HTTP headers and query parameter logging
 * - Authentication attempt tracking
 * - Rate limit violation logging
 * - Automatic log file rotation by size
 * - Old log file cleanup (retention policy)
 * - Daily statistics aggregation
 * - Execution time tracking
 * - Configurable output formats
 * - Thread-safe file operations (LOCK_EX)
 * 
 * @package App
 * @author Adrian D
 * @copyright 2025 BitHost
 * @license MIT
 * @version 1.3.0
 * @link https://upmvc.com
 * 
 * @example
 * // Basic usage
 * $logger = new RequestLogger([
 *     'enabled' => true,
 *     'log_dir' => __DIR__ . '/logs',
 *     'log_level' => RequestLogger::LEVEL_INFO
 * ]);
 * 
 * // Log a complete request/response cycle
 * $request = [
 *     'method' => 'POST',
 *     'action' => 'create',
 *     'table' => 'users',
 *     'body' => ['name' => 'John', 'password' => 'secret123'],
 *     'ip' => '192.168.1.100'
 * ];
 * $response = ['status_code' => 201, 'body' => ['id' => 42]];
 * $logger->logRequest($request, $response, 0.125);
 * 
 * // Log authentication attempts
 * $logger->logAuth('jwt', true, 'user@example.com');
 * 
 * // Get daily statistics
 * $stats = $logger->getStats(); // ['total_requests' => 150, 'errors' => 3, ...]
 */
class RequestLogger
{
    private bool $enabled;
    private string $logDir;
    private string $logLevel;
    private bool $logHeaders;
    private bool $logBody;
    private bool $logQueryParams;
    private bool $logResponseBody;
    private int $maxBodyLength;
    private array $sensitiveKeys;
    private string $dateFormat;
    private int $rotationSize;
    private int $maxFiles;

    // Log levels
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';

    /**
     * Initialize the request logger
     *
     * @param array $config Configuration options:
     *   - enabled: bool Enable logging (default: true)
     *   - log_dir: string Directory for log files (default: logs/)
     *   - log_level: string Minimum log level (default: 'info')
     *   - log_headers: bool Log request headers (default: true)
     *   - log_body: bool Log request body (default: true)
     *   - log_query_params: bool Log query parameters (default: true)
     *   - log_response_body: bool Log response body (default: false)
     *   - max_body_length: int Max length of logged body (default: 1000)
     *   - sensitive_keys: array Keys to redact (default: ['password', 'token', 'secret', 'api_key'])
     *   - date_format: string Date format (default: 'Y-m-d H:i:s')
     *   - rotation_size: int Size in bytes before rotation (default: 10MB)
     *   - max_files: int Maximum log files to keep (default: 30)
     */
    public function __construct(array $config = [])
    {
        $this->enabled = $config['enabled'] ?? true;
        $this->logDir = $config['log_dir'] ?? __DIR__ . '/../logs';
        $this->logLevel = $config['log_level'] ?? self::LEVEL_INFO;
        $this->logHeaders = $config['log_headers'] ?? true;
        $this->logBody = $config['log_body'] ?? true;
        $this->logQueryParams = $config['log_query_params'] ?? true;
        $this->logResponseBody = $config['log_response_body'] ?? false;
        $this->maxBodyLength = $config['max_body_length'] ?? 1000;
        $this->sensitiveKeys = $config['sensitive_keys'] ?? ['password', 'token', 'secret', 'api_key', 'apikey'];
        $this->dateFormat = $config['date_format'] ?? 'Y-m-d H:i:s';
        $this->rotationSize = $config['rotation_size'] ?? 10485760; // 10MB
        $this->maxFiles = $config['max_files'] ?? 30;

        // Create log directory if it doesn't exist
        if ($this->enabled && !is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * Log an API request with its response
     * 
     * Creates a comprehensive log entry including request method, action, table,
     * headers, body, query parameters, response status, execution time, and response body.
     * Automatically redacts sensitive data based on configured keys.
     *
     * @param array $request Request data containing:
     *   - method: string HTTP method (GET, POST, PUT, DELETE)
     *   - action: string API action (list, read, create, update, delete)
     *   - table?: string Database table name
     *   - body?: array Request payload
     *   - query?: array Query parameters
     *   - headers?: array HTTP headers
     *   - ip?: string Client IP address
     *   - user?: string Authenticated user identifier
     * @param array $response Response data containing:
     *   - status_code: int HTTP status code
     *   - body?: mixed Response payload
     *   - size?: int Response size in bytes
     * @param float $executionTime Execution time in seconds (use microtime)
     * @return bool True if logged successfully, false if logging disabled
     * 
     * @example
     * $start = microtime(true);
     * // ... API processing ...
     * $executionTime = microtime(true) - $start;
     * 
     * $logger->logRequest(
     *     ['method' => 'GET', 'action' => 'list', 'table' => 'products'],
     *     ['status_code' => 200, 'body' => ['records' => [...]]],
     *     $executionTime
     * );
     */
    public function logRequest(array $request, array $response, float $executionTime): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $logEntry = $this->buildLogEntry($request, $response, $executionTime);
        
        // Determine log level based on response status
        $statusCode = $response['status_code'] ?? 200;
        $level = $this->determineLogLevel($statusCode);

        return $this->writeLog($level, $logEntry);
    }

    /**
     * Log a quick request (before response is ready)
     * 
     * Lightweight logging method for capturing requests immediately without waiting
     * for response completion. Useful for tracking incoming requests in real-time
     * or logging requests that may fail before generating a response.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH)
     * @param string $action API action (list, read, create, update, delete, count)
     * @param string|null $table Table name if database operation (null for system actions)
     * @param string|null $identifier User ID, API key hash, or IP address for tracking
     * @return bool True if logged successfully, false if logging disabled
     * 
     * @example
     * // Log at request start
     * $logger->logQuickRequest('POST', 'create', 'orders', 'user_123');
     * 
     * // Log system action without table
     * $logger->logQuickRequest('GET', 'openapi', null, '192.168.1.100');
     */
    public function logQuickRequest(
        string $method,
        string $action,
        ?string $table = null,
        ?string $identifier = null
    ): bool {
        if (!$this->enabled) {
            return false;
        }

        $logEntry = sprintf(
            "[%s] %s %s%s%s",
            date($this->dateFormat),
            $method,
            $action,
            $table ? " (table: $table)" : '',
            $identifier ? " [user: $identifier]" : ''
        );

        return $this->writeLog(self::LEVEL_INFO, $logEntry);
    }

    /**
     * Log an error
     * 
     * Records error messages with contextual information for debugging and monitoring.
     * Automatically sanitizes sensitive data in context array. Errors are written
     * at ERROR log level regardless of configured minimum level.
     *
     * @param string $message Error message describing what went wrong
     * @param array $context Additional context data (exceptions, request details, etc.)
     *   Supports nested arrays. Sensitive keys will be redacted automatically.
     * @return bool True if logged successfully, false if logging disabled
     * 
     * @example
     * // Log database error
     * $logger->logError('Database connection failed', [
     *     'host' => 'localhost',
     *     'error' => $e->getMessage(),
     *     'trace' => $e->getTraceAsString()
     * ]);
     * 
     * // Log validation error with request context
     * $logger->logError('Invalid input data', [
     *     'field' => 'email',
     *     'value' => 'invalid-email',
     *     'rule' => 'email format'
     * ]);
     */
    public function logError(string $message, array $context = []): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $logEntry = sprintf(
            "[%s] ERROR: %s\nContext: %s",
            date($this->dateFormat),
            $message,
            json_encode($this->sanitizeSensitiveData($context), JSON_PRETTY_PRINT)
        );

        return $this->writeLog(self::LEVEL_ERROR, $logEntry);
    }

    /**
     * Log authentication attempts
     * 
     * Tracks successful and failed authentication attempts for security auditing.
     * Failed attempts are logged at WARNING level to facilitate intrusion detection.
     * Successful attempts are logged at INFO level for audit trails.
     *
     * @param string $method Auth method used (apikey, basic, jwt, oauth)
     * @param bool $success Whether authentication succeeded (true) or failed (false)
     * @param string|null $identifier User identifier, username, email, or API key hash
     * @param string|null $reason Failure reason for debugging (e.g., 'invalid token', 'expired JWT')
     * @return bool True if logged successfully, false if logging disabled
     * 
     * @example
     * // Log successful JWT authentication
     * $logger->logAuth('jwt', true, 'user@example.com');
     * 
     * // Log failed API key attempt
     * $logger->logAuth('apikey', false, '192.168.1.100', 'invalid API key');
     * 
     * // Log failed basic auth with reason
     * $logger->logAuth('basic', false, 'admin', 'incorrect password');
     */
    public function logAuth(
        string $method,
        bool $success,
        ?string $identifier = null,
        ?string $reason = null
    ): bool {
        if (!$this->enabled) {
            return false;
        }

        $status = $success ? '✅ SUCCESS' : '❌ FAILED';
        $logEntry = sprintf(
            "[%s] AUTH %s: method=%s, user=%s%s",
            date($this->dateFormat),
            $status,
            $method,
            $identifier ?? 'unknown',
            $reason ? ", reason=$reason" : ''
        );

        $level = $success ? self::LEVEL_INFO : self::LEVEL_WARNING;
        return $this->writeLog($level, $logEntry);
    }

    /**
     * Log rate limit hits
     * 
     * Records when a client exceeds their rate limit threshold. Helps identify
     * abusive behavior, misconfigured clients, or need for rate limit adjustments.
     * Logged at WARNING level.
     *
     * @param string $identifier User ID, API key, or IP address that hit the limit
     * @param int $requestCount Current number of requests in the time window
     * @param int $limit Maximum allowed requests in the time window
     * @return bool True if logged successfully, false if logging disabled
     * 
     * @example
     * // Log rate limit violation
     * $logger->logRateLimit('api_key_abc123', 1050, 1000);
     * // Output: "RATE LIMIT EXCEEDED: api_key_abc123 (requests: 1050/1000)"
     * 
     * // Track IP-based violations
     * $logger->logRateLimit('192.168.1.100', 155, 100);
     */
    public function logRateLimit(string $identifier, int $requestCount, int $limit): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $logEntry = sprintf(
            "[%s] RATE LIMIT EXCEEDED: %s (requests: %d/%d)",
            date($this->dateFormat),
            $identifier,
            $requestCount,
            $limit
        );

        return $this->writeLog(self::LEVEL_WARNING, $logEntry);
    }

    /**
     * Get log statistics
     * 
     * Analyzes log files to extract key metrics for monitoring and reporting.
     * Provides counts of total requests, errors, warnings, authentication failures,
     * and rate limit violations. Returns zero values if log file doesn't exist.
     *
     * @param string|null $date Date in Y-m-d format (e.g., '2025-01-15'). 
     *   If null, uses today's date.
     * @return array Statistics array with keys:
     *   - total_requests: int Total API requests logged
     *   - errors: int Number of ERROR level entries
     *   - warnings: int Number of WARNING level entries
     *   - auth_failures: int Failed authentication attempts
     *   - rate_limits: int Rate limit violations
     * 
     * @example
     * // Get today's statistics
     * $stats = $logger->getStats();
     * echo "Total: {$stats['total_requests']}, Errors: {$stats['errors']}";
     * 
     * // Get statistics for specific date
     * $yesterdayStats = $logger->getStats('2025-01-14');
     * if ($yesterdayStats['errors'] > 100) {
     *     alert('High error rate yesterday!');
     * }
     */
    public function getStats(?string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $logFile = $this->getLogFilePath($date);

        if (!file_exists($logFile)) {
            return [
                'total_requests' => 0,
                'errors' => 0,
                'warnings' => 0,
                'auth_failures' => 0,
                'rate_limits' => 0,
            ];
        }

        $content = file_get_contents($logFile);
        
        return [
            'total_requests' => substr_count($content, '] INFO:') + substr_count($content, '] ERROR:'),
            'errors' => substr_count($content, '] ERROR:'),
            'warnings' => substr_count($content, '] WARNING:'),
            'auth_failures' => substr_count($content, 'AUTH ❌ FAILED'),
            'rate_limits' => substr_count($content, 'RATE LIMIT EXCEEDED'),
        ];
    }

    /**
     * Clean up old log files
     * 
     * Automatically removes oldest log files when total count exceeds configured
     * max_files limit. Preserves most recent files based on modification time.
     * Should be called periodically (e.g., daily cron job) to manage disk space.
     *
     * @return int Number of files deleted (0 if under limit or no files found)
     * 
     * @example
     * // Run daily cleanup (e.g., in cron job)
     * $deleted = $logger->cleanup();
     * echo "Cleaned up $deleted old log files";
     * 
     * // Manual cleanup with custom retention
     * $logger = new RequestLogger(['max_files' => 7]); // Keep only 1 week
     * $logger->cleanup();
     */
    public function cleanup(): int
    {
        if (!is_dir($this->logDir)) {
            return 0;
        }

        $files = glob($this->logDir . '/api_*.log');
        if (count($files) <= $this->maxFiles) {
            return 0;
        }

        // Sort by modification time (oldest first)
        usort($files, fn($a, $b) => filemtime($a) - filemtime($b));

        $toDelete = array_slice($files, 0, count($files) - $this->maxFiles);
        $deleted = 0;

        foreach ($toDelete as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Rotate log file if it exceeds size limit
     * 
     * Automatically renames log files that exceed configured rotation_size by
     * appending a timestamp. This prevents individual log files from growing
     * too large and makes them easier to manage and archive.
     *
     * @param string $logFile Absolute path to log file to check
     * @return bool True if rotation occurred, false if under size limit or file doesn't exist
     * 
     * @example
     * // Automatic rotation on write (handled internally)
     * $logger->logRequest(...); // Rotates if api_2025-01-15.log > 10MB
     * 
     * // Manual rotation for maintenance
     * if ($logger->rotateIfNeeded('/path/to/logs/api_2025-01-15.log')) {
     *     echo "Log file rotated successfully";
     * }
     * // Creates: api_2025-01-15_20250115143022.log
     */
    public function rotateIfNeeded(string $logFile): bool
    {
        if (!file_exists($logFile)) {
            return false;
        }

        if (filesize($logFile) < $this->rotationSize) {
            return false;
        }

        $timestamp = date('YmdHis');
        $rotatedFile = str_replace('.log', "_$timestamp.log", $logFile);
        
        return rename($logFile, $rotatedFile);
    }

    // ==================================================================================
    // PRIVATE METHODS
    // ==================================================================================

    /**
     * Build a comprehensive log entry
     *
     * @param array $request Request data
     * @param array $response Response data
     * @param float $executionTime Execution time in seconds
     * @return string Formatted log entry
     */
    private function buildLogEntry(array $request, array $response, float $executionTime): string
    {
        $lines = [];
        $lines[] = str_repeat('=', 80);
        $lines[] = sprintf("[%s] API REQUEST", date($this->dateFormat));
        $lines[] = str_repeat('-', 80);

        // Request info
        $lines[] = sprintf("Method: %s", $request['method'] ?? 'UNKNOWN');
        $lines[] = sprintf("Action: %s", $request['action'] ?? 'UNKNOWN');
        
        if (isset($request['table'])) {
            $lines[] = sprintf("Table: %s", $request['table']);
        }

        if (isset($request['ip'])) {
            $lines[] = sprintf("IP: %s", $request['ip']);
        }

        if (isset($request['user'])) {
            $lines[] = sprintf("User: %s", $request['user']);
        }

        // Query parameters
        if ($this->logQueryParams && !empty($request['query'])) {
            $lines[] = sprintf("Query: %s", json_encode($this->sanitizeSensitiveData($request['query'])));
        }

        // Headers
        if ($this->logHeaders && !empty($request['headers'])) {
            $lines[] = "Headers:";
            foreach ($this->sanitizeSensitiveData($request['headers']) as $key => $value) {
                $lines[] = "  $key: $value";
            }
        }

        // Request body
        if ($this->logBody && !empty($request['body'])) {
            $body = $this->sanitizeSensitiveData($request['body']);
            $bodyJson = json_encode($body, JSON_PRETTY_PRINT);
            $truncated = strlen($bodyJson) > $this->maxBodyLength;
            $bodyJson = substr($bodyJson, 0, $this->maxBodyLength);
            
            $lines[] = "Request Body:" . ($truncated ? " (truncated)" : "");
            $lines[] = $bodyJson;
        }

        $lines[] = str_repeat('-', 80);

        // Response info
        $lines[] = sprintf("Status: %d", $response['status_code'] ?? 200);
        $lines[] = sprintf("Execution Time: %.3fms", $executionTime * 1000);
        
        if (isset($response['size'])) {
            $lines[] = sprintf("Response Size: %s", $this->formatBytes($response['size']));
        }

        // Response body (if enabled)
        if ($this->logResponseBody && !empty($response['body'])) {
            $bodyJson = json_encode($response['body'], JSON_PRETTY_PRINT);
            $truncated = strlen($bodyJson) > $this->maxBodyLength;
            $bodyJson = substr($bodyJson, 0, $this->maxBodyLength);
            
            $lines[] = "Response Body:" . ($truncated ? " (truncated)" : "");
            $lines[] = $bodyJson;
        }

        $lines[] = str_repeat('=', 80);
        $lines[] = ""; // Empty line

        return implode("\n", $lines);
    }

    /**
     * Sanitize sensitive data (redact passwords, tokens, etc.)
     *
     * @param mixed $data Data to sanitize
     * @return mixed Sanitized data
     */
    private function sanitizeSensitiveData($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($key) && $this->isSensitiveKey($key)) {
                    $data[$key] = '***REDACTED***';
                } elseif (is_array($value)) {
                    $data[$key] = $this->sanitizeSensitiveData($value);
                }
            }
        }

        return $data;
    }

    /**
     * Check if a key is sensitive
     *
     * @param string $key Key to check
     * @return bool True if sensitive
     */
    private function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);
        foreach ($this->sensitiveKeys as $sensitive) {
            if (str_contains($key, strtolower($sensitive))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine log level based on status code
     *
     * @param int $statusCode HTTP status code
     * @return string Log level
     */
    private function determineLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return self::LEVEL_ERROR;
        } elseif ($statusCode >= 400) {
            return self::LEVEL_WARNING;
        }
        return self::LEVEL_INFO;
    }

    /**
     * Write log entry to file
     *
     * @param string $level Log level
     * @param string $message Log message
     * @return bool True if written successfully
     */
    private function writeLog(string $level, string $message): bool
    {
        $date = date('Y-m-d');
        $logFile = $this->getLogFilePath($date);

        // Check rotation
        $this->rotateIfNeeded($logFile);

        // Prepare log entry
        $levelMap = [
            self::LEVEL_DEBUG => 'DEBUG',
            self::LEVEL_INFO => 'INFO',
            self::LEVEL_WARNING => 'WARNING',
            self::LEVEL_ERROR => 'ERROR',
        ];

        $levelLabel = $levelMap[$level] ?? 'INFO';
        
        // Add level prefix if not already in message
        if (!str_contains($message, '] ' . $levelLabel)) {
            $message = "[" . date($this->dateFormat) . "] $levelLabel: $message";
        }

        // Write to file
        $result = @file_put_contents($logFile, $message . "\n", FILE_APPEND | LOCK_EX);

        return $result !== false;
    }

    /**
     * Get log file path for a date
     *
     * @param string $date Date in Y-m-d format
     * @return string Log file path
     */
    private function getLogFilePath(string $date): string
    {
        return $this->logDir . '/api_' . $date . '.log';
    }

    /**
     * Format bytes to human-readable size
     *
     * @param int $bytes Bytes
     * @return string Formatted size
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string)$bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
