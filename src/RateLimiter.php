<?php
declare(strict_types=1);

namespace App;

/**
 * Legacy namespace wrapper. Canonical class moved to App\\Security\\RateLimiter.
 *
 * @deprecated Use \App\Security\RateLimiter instead. This wrapper will be removed in a future major release.
 */
class RateLimiter extends \App\Security\RateLimiter {}
