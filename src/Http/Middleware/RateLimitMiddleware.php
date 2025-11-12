<?php
namespace App\Http\Middleware;

use App\Security\RateLimiter;
use App\Observability\RequestLogger;
use App\Observability\Monitor;

/**
 * RateLimitMiddleware centralizes rate limiting checks and responses.
 *
 * Responsibilities:
 * - Check limit for an identifier (user/apikey/ip)
 * - Log rate limit hits
 * - Record security events
 * - Emit rate limit headers
 * - Send 429 response if exceeded
 */
class RateLimitMiddleware
{
    public function __construct(
        private RateLimiter $rateLimiter,
        private RequestLogger $logger,
        private ?Monitor $monitor = null
    ) {}

    /**
     * Perform rate limit check and respond if exceeded.
     * Returns false if request should terminate (429 already sent),
     * otherwise true and ensures headers are emitted.
     */
    public function checkAndRespond(string $identifier): bool
    {
        if (!$this->rateLimiter->checkLimit($identifier)) {
            // Log the rate limit hit
            $this->logger->logRateLimit(
                $identifier,
                $this->rateLimiter->getRequestCount($identifier),
                $this->rateLimiter->getRemainingRequests($identifier) + $this->rateLimiter->getRequestCount($identifier)
            );

            // Record security event
            if ($this->monitor) {
                $this->monitor->recordSecurityEvent('rate_limit_hit', [
                    'identifier' => $identifier,
                    'requests' => $this->rateLimiter->getRequestCount($identifier),
                ]);
            }

            // Send 429 with headers
            $this->rateLimiter->sendRateLimitResponse($identifier);
            return false;
        }

        // Add rate limit headers for allowed requests
        foreach ($this->rateLimiter->getHeaders($identifier) as $name => $value) {
            header($name . ': ' . $value);
        }
        return true;
    }
}
