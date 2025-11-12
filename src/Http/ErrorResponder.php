<?php
namespace App\Http;

use Throwable;
use App\Observability\RequestLogger;
use App\Observability\Monitor;

/**
 * ErrorResponder: centralizes exception-to-response mapping with logging.
 *
 * By default, it preserves the exception message in the response to avoid
 * behavior changes. Toggle $exposeDetails to false for sanitized payloads.
 */
class ErrorResponder
{
    public function __construct(
        private RequestLogger $logger,
        private ?Monitor $monitor = null,
        private bool $exposeDetails = true
    ) {}

    /**
     * Handle an exception: log, record metrics, and send JSON error response.
     * Returns the payload and status for callers that also need to log response.
     *
     * @param array<string,mixed> $context
     * @return array{0: array{error: string}, 1: int}
     */
    public function fromException(Throwable $e, array $context = [], int $status = 500): array
    {
        // Log full details
        $this->logger->logError($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'context' => $context,
        ]);

        // Record error metric
        if ($this->monitor) {
            $this->monitor->recordError($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'action' => $context['action'] ?? null,
                'table'  => $context['table'] ?? null,
            ]);
        }

        // Build safe payload
        $payload = [
            'error' => $this->exposeDetails ? $e->getMessage() : 'Internal Server Error'
        ];

        Response::json($payload, $status);
        return [$payload, $status];
    }
}
