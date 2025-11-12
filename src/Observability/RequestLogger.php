<?php
declare(strict_types=1);

namespace App\Observability;

/**
 * Canonical RequestLogger implementation.
 *
 * Writes structured JSON lines to storage/logs for:
 * - requests (with response summary)
 * - authentication events
 * - errors
 * - rate limiting events
 */
class RequestLogger
{
	private string $logDir;
	private bool $enabled;
	private bool $logRequests;
	private bool $logResponses;
	private bool $logErrors;

	public function __construct(array $config = [])
	{
		$config = array_merge([
			'enabled' => true,
			'log_requests' => true,
			'log_responses' => false,
			'log_errors' => true,
			'dir' => __DIR__ . '/../../storage/logs',
		], $config);

		$this->enabled = (bool)$config['enabled'];
		$this->logRequests = (bool)$config['log_requests'];
		$this->logResponses = (bool)$config['log_responses'];
		$this->logErrors = (bool)$config['log_errors'];
		$this->logDir = rtrim($config['dir'], '/\\');

		if (!is_dir($this->logDir)) {
			@mkdir($this->logDir, 0755, true);
		}
	}

	public function logRequest(array $request, array $response, float $executionTime): void
	{
		if (!$this->enabled || !$this->logRequests) return;
		$entry = [
			'type' => 'request',
			'time' => date('c'),
			'execution_ms' => (int)round($executionTime * 1000),
			'request' => [
				'method' => $request['method'] ?? 'GET',
				'action' => $request['action'] ?? null,
				'table' => $request['table'] ?? null,
				'ip' => $request['ip'] ?? null,
				'user' => $request['user'] ?? null,
				'query' => $request['query'] ?? null,
			],
			'response' => [
				'status' => $response['status_code'] ?? 0,
				'size' => $response['size'] ?? 0,
			],
		];
		if ($this->logResponses) {
			$entry['response']['body'] = $response['body'] ?? null;
		}
		$this->write('requests', $entry);
	}

	public function logAuth(string $method, bool $success, $userOrIdentifier, ?string $message = null): void
	{
		if (!$this->enabled) return;
		$entry = [
			'type' => 'auth',
			'time' => date('c'),
			'method' => $method,
			'success' => $success,
			'subject' => $userOrIdentifier,
		];
		if ($message) { $entry['message'] = $message; }
		$this->write('auth', $entry);
	}

	public function logError(string $message, array $context = []): void
	{
		if (!$this->enabled || !$this->logErrors) return;
		$entry = [
			'type' => 'error',
			'time' => date('c'),
			'message' => $message,
			'context' => $context,
		];
		$this->write('errors', $entry);
	}

	public function logRateLimit(string $identifier, int $count, int $limit): void
	{
		if (!$this->enabled) return;
		$entry = [
			'type' => 'ratelimit',
			'time' => date('c'),
			'identifier' => $identifier,
			'count' => $count,
			'limit' => $limit,
		];
		$this->write('ratelimit', $entry);
	}

	private function write(string $channel, array $entry): void
	{
		$file = $this->logDir . '/' . $channel . '_' . date('Y-m-d') . '.log';
		@file_put_contents($file, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
	}
}
