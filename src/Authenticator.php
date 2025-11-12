<?php
declare(strict_types=1);

namespace App;

/**
 * Legacy BC wrapper. Canonical class moved to App\Auth\Authenticator.
 * This file intentionally contains no logic beyond extending the new location.
 *
 * @deprecated Use \App\Auth\Authenticator instead. This wrapper will be removed in a future major release.
 */
class Authenticator extends \App\Auth\Authenticator {}
