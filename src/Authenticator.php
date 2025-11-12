<?php
declare(strict_types=1);

namespace App;

/**
 * Legacy BC wrapper. Canonical class moved to App\Auth\Authenticator.
 * This file intentionally contains no logic beyond extending the new location.
 */
class Authenticator extends \App\Auth\Authenticator {}
