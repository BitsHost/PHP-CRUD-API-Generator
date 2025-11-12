<?php
/**
 * Request Logger with redaction, rotation, and simple stats.
 *
 * @package   PHP-CRUD-API-Generator
 * @author    BitsHost
 * @copyright 2025 BitsHost
 * @license   MIT License
 * @link      https://bitshost.biz/
 * @created   2025-11-12
 */
declare(strict_types=1);

namespace App\Observability;

/**
 * RequestLogger (canonical)
 * - Human-readable single-file logs: api_YYYY-mm-dd.log
 * - Levels: INFO, WARNING, ERROR (derived from status code)
 * - Redacts sensitive fields in request body
 * - Optional rotation and cleanup
 */
class RequestLogger
{
	// Levels used in tests
	public const LEVEL_INFO = 'INFO';
	public const LEVEL_WARNING = 'WARNING';
	public const LEVEL_ERROR = 'ERROR';

	private string $logDir;
	private bool $enabled;
	private bool $logHeaders;
	private bool $logBody;
	private int $maxBodyLength;
	private int $rotationSize; // bytes; 0=disabled
	private int $maxFiles;     // keep last N files on cleanup (0=disabled)

	/**
	 * @param array{
	 *   enabled?: bool,
	 *   log_dir?: string,
	 *   log_headers?: bool,
	 *   log_body?: bool,
	 *   max_body_length?: int,
	 *   rotation_size?: int,
	 *   max_files?: int
	 * } $config
	 */
	public function __construct(array $config = [])
	{
		$config = array_merge([
			'enabled' => true,
			'log_dir' => __DIR__ . '/../../storage/logs',
			'log_headers' => false,
			'log_body' => false,
			'max_body_length' => 1000,
			'rotation_size' => 0,
			'max_files' => 0,
		], $config);

		$this->enabled = (bool)$config['enabled'];
		$this->logDir = rtrim((string)$config['log_dir'], '/\\');
		$this->logHeaders = (bool)$config['log_headers'];
		$this->logBody = (bool)$config['log_body'];
		$this->maxBodyLength = (int)$config['max_body_length'];
		$this->rotationSize = (int)$config['rotation_size'];
		$this->maxFiles = (int)$config['max_files'];

		if (!is_dir($this->logDir)) {
			@mkdir($this->logDir, 0755, true);
		}
	}

	/**
	 * @param array<string,mixed> $request
	 * @param array<string,mixed> $response
	 */
	public function logRequest(array $request, array $response, float $executionTime): bool
	{
		if (!$this->enabled) { return false; }

		$method = $request['method'] ?? 'GET';
		$action = $request['action'] ?? '';
		$table = $request['table'] ?? '';
		$user = $request['user'] ?? '';
		$status = (int)($response['status_code'] ?? 0);
		$size = (int)($response['size'] ?? 0);
		$ms = (int)round($executionTime * 1000);
		$level = $this->levelFromStatus($status);

		$parts = [
			'REQUEST',
			"[$level]",
			$method,
			$action,
			$table,
			$user,
			'status=' . $status,
			'time=' . $ms . 'ms',
			'size=' . $size,
		];

		if ($this->logHeaders && !empty($request['headers'])) {
			$parts[] = 'headers=' . json_encode($request['headers']);
		}
		if ($this->logBody && !empty($request['body'])) {
			$sanitized = $this->redactSensitive($request['body']);
			$body = (string)json_encode($sanitized);
			if (strlen($body) > $this->maxBodyLength) {
				$body = substr($body, 0, $this->maxBodyLength) . '...';
			}
			$parts[] = 'body=' . $body;
		}

		$line = sprintf('[%s] %s', date('Y-m-d H:i:s'), implode(' ', array_filter($parts)));
		$ok = $this->writeLine($line);
		if ($ok) { $this->maybeRotate(); }
		return $ok;
	}

	public function logAuth(string $method, bool $success, string $userOrIdentifier, ?string $message = null): bool
	{
		if (!$this->enabled) { return false; }
		$icon = $success ? '✅ SUCCESS' : '❌ FAILED';
		$parts = [
			'AUTH', $icon, $method, (string)$userOrIdentifier,
		];
		if ($message) { $parts[] = '-'; $parts[] = $message; }
		$line = sprintf('[%s] %s', date('Y-m-d H:i:s'), implode(' ', $parts));
		$ok = $this->writeLine($line);
		if ($ok) { $this->maybeRotate(); }
		return $ok;
	}

	/**
	 * @param array<string,mixed> $context
	 */
	public function logError(string $message, array $context = []): bool
	{
		if (!$this->enabled) { return false; }
		$ctx = $context ? ' ' . json_encode($context) : '';
		$line = sprintf('[%s] ERROR %s%s', date('Y-m-d H:i:s'), $message, $ctx);
		$ok = $this->writeLine($line);
		if ($ok) { $this->maybeRotate(); }
		return $ok;
	}

	public function logRateLimit(string $identifier, int $count, int $limit): bool
	{
		if (!$this->enabled) { return false; }
		$line = sprintf('[%s] RATE LIMIT EXCEEDED %s %d/%d', date('Y-m-d H:i:s'), $identifier, $count, $limit);
		$ok = $this->writeLine($line);
		if ($ok) { $this->maybeRotate(); }
		return $ok;
	}

	public function logQuickRequest(string $method, string $action, string $table, string $subject = ''): bool
	{
		return $this->logRequest([
			'method' => $method,
			'action' => $action,
			'table' => $table,
			'user' => $subject,
		], ['status_code' => 200, 'size' => 0], 0.0);
	}

	/**
	 * @return array{total_requests:int,errors:int,warnings:int,auth_failures:int,rate_limits:int}
	 */
	public function getStats(): array
	{
		$file = $this->currentLogFile();
		if (!file_exists($file)) {
			return [
				'total_requests' => 0,
				'errors' => 0,
				'warnings' => 0,
				'auth_failures' => 0,
				'rate_limits' => 0,
			];
		}
		$content = (string)@file_get_contents($file);
		return [
			'total_requests' => substr_count($content, ' REQUEST '),
			'errors' => substr_count($content, ' ERROR '),
			'warnings' => substr_count($content, ' [WARNING] '),
			'auth_failures' => substr_count($content, 'AUTH ❌ FAILED'),
			'rate_limits' => substr_count($content, 'RATE LIMIT EXCEEDED'),
		];
	}

	public function cleanup(): int
	{
		if ($this->maxFiles <= 0) { return 0; }
		$files = glob($this->logDir . '/api_*.log') ?: [];
		usort($files, fn($a, $b) => filemtime($a) <=> filemtime($b));
		$toDelete = max(0, count($files) - $this->maxFiles);
		$deleted = 0;
		for ($i = 0; $i < $toDelete; $i++) {
			if (@unlink($files[$i])) { $deleted++; }
		}
		return $deleted;
	}

	/**
	 * @param array<string,mixed> $body
	 * @return array<string,mixed>
	 */
	private function redactSensitive(array $body): array
	{
		$sensitive = ['password', 'pass', 'api_key', 'apikey', 'token', 'secret'];
		$redacted = $body;
		foreach ($sensitive as $key) {
			if (array_key_exists($key, $redacted)) {
				$redacted[$key] = '***REDACTED***';
			}
		}
		return $redacted;
	}

	private function levelFromStatus(int $status): string
	{
		if ($status >= 500) return self::LEVEL_ERROR;
		if ($status >= 400) return self::LEVEL_WARNING;
		return self::LEVEL_INFO;
	}

	private function writeLine(string $line): bool
	{
		$file = $this->currentLogFile();
		return @file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
	}

	private function currentLogFile(): string
	{
		return $this->logDir . '/api_' . date('Y-m-d') . '.log';
	}

	private function maybeRotate(): void
	{
		if ($this->rotationSize <= 0) { return; }
		$file = $this->currentLogFile();
		clearstatcache(true, $file);
		$size = @filesize($file) ?: 0;
		if ($size > $this->rotationSize) {
			$rotated = $this->logDir . '/api_' . date('Y-m-d_His') . '.log';
			@rename($file, $rotated);
		}
	}
}
