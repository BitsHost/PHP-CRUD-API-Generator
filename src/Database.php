<?php
namespace App;

/**
 * Backward-compatibility stub for moved Database class.
 * New location: App\Database\Database
 *
 * @deprecated Use \App\Database\Database instead. This wrapper will be removed in a future major release.
 */
// Optional runtime deprecation notice (dev only): set API_GEN_DEPRECATIONS=1|trigger|log
$__dep = getenv('API_GEN_DEPRECATIONS') ?: ($_ENV['API_GEN_DEPRECATIONS'] ?? '');
if ($__dep !== '' && $__dep !== '0' && strtolower((string)$__dep) !== 'off') {
	$__msg = 'Deprecated class App\\Database (root wrapper). Use App\\Database\\Database instead.';
	if ($__dep === 'log') { @error_log($__msg); } else { @trigger_error($__msg, E_USER_DEPRECATED); }
}
unset($__dep, $__msg);
class Database extends \App\Database\Database {}