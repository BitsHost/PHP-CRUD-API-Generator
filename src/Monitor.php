<?php
declare(strict_types=1);

namespace App;

/**
 * Legacy namespace wrapper. Canonical class moved to App\\Observability\\Monitor.
 *
 * @deprecated Use \App\Observability\Monitor instead. This wrapper will be removed in a future major release.
 */
class Monitor extends \App\Observability\Monitor {}
// Runtime deprecation notice
$__dep = getenv('API_GEN_DEPRECATIONS') ?: ($_ENV['API_GEN_DEPRECATIONS'] ?? '');
if ($__dep !== '' && $__dep !== '0' && strtolower((string)$__dep) !== 'off') {
	$__msg = 'Deprecated class App\\Monitor (root wrapper). Use App\\Observability\\Monitor instead.';
	if ($__dep === 'log') { @error_log($__msg); } else { @trigger_error($__msg, E_USER_DEPRECATED); }
}
unset($__dep, $__msg);
