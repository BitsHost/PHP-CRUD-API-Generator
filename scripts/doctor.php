<?php
/**
 * Configuration doctor — flags unsafe defaults before production.
 *
 * Usage: php scripts/doctor.php
 */
declare(strict_types=1);

$packageRoot = dirname(__DIR__);
require_once $packageRoot . '/vendor/autoload.php';

use App\Config\ApiConfig;
use App\Config\ConfigPaths;

$configDir = ConfigPaths::resolveDir(
    is_dir($packageRoot . '/config') ? $packageRoot . '/config' : null
);

$apiFile = ConfigPaths::api($configDir);
$dbFile = ConfigPaths::db($configDir);

echo "PHP CRUD API Generator — doctor\n";
echo "Config dir: {$configDir}\n\n";

$errors = 0;
$warnings = 0;

if (!is_file($apiFile)) {
    echo "[ERROR] Missing {$apiFile}. Run: php scripts/install-config.php\n";
    exit(1);
}
if (!is_file($dbFile)) {
    echo "[ERROR] Missing {$dbFile}. Run: php scripts/install-config.php\n";
    exit(1);
}

$api = ApiConfig::fromFile($apiFile);
/** @var array<string,mixed> $raw */
$raw = require $apiFile;

$weakSecrets = [
    'changeme123',
    'dev-change-me',
    'YourSuperSecretKey',
    'YourSuperSecretKeyChangeMe',
    'your-secret-key-change-this-in-production',
    'secret',
    'userpass',
];

if (!$api->isAuthEnabled()) {
    echo "[WARN] auth_enabled is false — entire schema is public.\n";
    $warnings++;
} else {
    echo "[OK]   Authentication enabled ({$api->getAuthMethod()})\n";
}

foreach ($api->getApiKeys() as $key) {
    if (in_array($key, $weakSecrets, true) || strlen($key) < 16) {
        echo "[WARN] Weak or example API key detected. Set API_KEYS / rotate keys.\n";
        $warnings++;
        break;
    }
}

$jwt = $api->getJwtSecret();
if (in_array($jwt, $weakSecrets, true) || strlen($jwt) < 32) {
    echo "[WARN] Weak JWT secret. Generate with: php -r \"echo bin2hex(random_bytes(32));\"\n";
    $warnings++;
} else {
    echo "[OK]   JWT secret length looks reasonable\n";
}

if ($api->getApiKeyRole() === 'admin') {
    echo "[WARN] api_key_role=admin — machine keys get full write access. Prefer readonly/editor.\n";
    $warnings++;
}

$allowed = $api->getAllowedTables();
if ($allowed === []) {
    echo "[WARN] allowed_tables is empty (all non-denied tables exposed). Set an explicit allowlist for production.\n";
    $warnings++;
} else {
    echo "[OK]   allowed_tables whitelist active (" . count($allowed) . " tables)\n";
}

$denied = $api->getDeniedTables();
echo $denied !== []
    ? "[OK]   denied_tables: " . implode(', ', $denied) . "\n"
    : "[WARN] denied_tables is empty\n";

if (($raw['auth_method'] ?? '') === 'oauth') {
    echo "[ERROR] auth_method=oauth is not implemented. Use jwt, basic, or apikey.\n";
    $errors++;
}

$dashboard = $packageRoot . '/dashboard.html';
$health = $packageRoot . '/health.php';
if (is_file($dashboard) || is_file($health)) {
    echo "[WARN] dashboard.html / health.php expose ops data — protect via IP allowlist or remove from public web root.\n";
    $warnings++;
}

$cacheFile = ConfigPaths::cache($configDir);
if (is_file($cacheFile)) {
    /** @var array<string,mixed> $cache */
    $cache = require $cacheFile;
    if (($cache['driver'] ?? '') === 'redis') {
        echo "[WARN] cache driver=redis — RedisCache is still a stub and will throw at runtime. Use file driver.\n";
        $warnings++;
    }
}

echo "\nSummary: {$errors} error(s), {$warnings} warning(s)\n";
exit($errors > 0 ? 1 : 0);
