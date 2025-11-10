<?php
/**
 * Debug JWT Login
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Router;
use App\Authenticator;

echo "=== JWT Login Debug ===\n\n";

// Load configs
$dbConfig = require __DIR__ . '/../config/db.php';
$apiConfig = require __DIR__ . '/../config/api.php';

echo "Config Check:\n";
echo "-------------\n";
echo "Auth method: " . $apiConfig['auth_method'] . "\n";
echo "Use DB auth: " . ($apiConfig['use_database_auth'] ? 'YES' : 'NO') . "\n";
echo "Config users: " . implode(', ', array_keys($apiConfig['basic_users'])) . "\n\n";

// Test 1: Simulate JSON POST
echo "Test 1: Simulating JSON POST\n";
echo "-----------------------------\n";

$_GET = ['action' => 'login'];
$_POST = [
    'username' => 'admin',
    'password' => 'secret'
];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

$db = new Database($dbConfig);
$auth = new Authenticator($apiConfig, $db->getPdo());
$router = new Router($db, $auth);

ob_start();
$router->route($_GET);
$response = ob_get_clean();

echo "Response: " . $response . "\n\n";

// Test 2: Check what Router sees
echo "Test 2: What Router receives\n";
echo "----------------------------\n";
echo "\$_POST contents:\n";
print_r($_POST);
echo "\n";

// Test 3: Try reading php://input (for JSON body)
echo "Test 3: Check php://input\n";
echo "-------------------------\n";
$jsonInput = file_get_contents('php://input');
echo "php://input: " . ($jsonInput ?: '(empty)') . "\n\n";

// Test 4: Manual authentication check
echo "Test 4: Manual Auth Check\n";
echo "-------------------------\n";
$user = 'admin';
$pass = 'secret';
$users = $apiConfig['basic_users'] ?? [];

if (isset($users[$user]) && $users[$user] === $pass) {
    echo "✓ Config file credentials are VALID\n";
    echo "  Username: $user\n";
    echo "  Password matches: YES\n";
    echo "  Role: " . ($apiConfig['user_roles'][$user] ?? 'readonly') . "\n";
} else {
    echo "✗ Config file credentials FAILED\n";
    echo "  Username exists: " . (isset($users[$user]) ? 'YES' : 'NO') . "\n";
    echo "  Password matches: " . (isset($users[$user]) && $users[$user] === $pass ? 'YES' : 'NO') . "\n";
}
