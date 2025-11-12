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
// Runtime deprecation notice
$__dep = getenv('API_GEN_DEPRECATIONS') ?: ($_ENV['API_GEN_DEPRECATIONS'] ?? '');
if ($__dep !== '' && $__dep !== '0' && strtolower((string)$__dep) !== 'off') {
	$__msg = 'Deprecated class App\\Authenticator (root wrapper). Use App\\Auth\\Authenticator instead.';
	if ($__dep === 'log') { @error_log($__msg); } else { @trigger_error($__msg, E_USER_DEPRECATED); }
}
unset($__dep, $__msg);
