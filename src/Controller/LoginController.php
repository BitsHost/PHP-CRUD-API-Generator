<?php
namespace App\Controller;

/**
 * Backward-compatibility stub for moved LoginController.
 * New location: App\Http\Controllers\LoginController
 *
 * @deprecated Use \App\Http\Controllers\LoginController instead. This wrapper will be removed in a future major release.
 */
class LoginController extends \App\Http\Controllers\LoginController {}
// Runtime deprecation notice
$__dep = getenv('API_GEN_DEPRECATIONS') ?: ($_ENV['API_GEN_DEPRECATIONS'] ?? '');
if ($__dep !== '' && $__dep !== '0' && strtolower((string)$__dep) !== 'off') {
	$__msg = 'Deprecated class App\\Controller\\LoginController (root wrapper). Use App\\Http\\Controllers\\LoginController instead.';
	if ($__dep === 'log') { @error_log($__msg); } else { @trigger_error($__msg, E_USER_DEPRECATED); }
}
unset($__dep, $__msg);
