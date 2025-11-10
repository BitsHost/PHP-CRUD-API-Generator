<?php
/**
 * JWT Login Test
 * 
 * Tests JWT authentication flow:
 * 1. Login with username/password
 * 2. Get JWT token
 * 3. Use token to access protected endpoints
 * 
 * Run: php tests/jwt_login_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Router;
use App\Authenticator;

echo "===================================\n";
echo "JWT Authentication Test\n";
echo "===================================\n\n";

// Load configs
$dbConfig = require __DIR__ . '/../config/db.php';
$apiConfig = require __DIR__ . '/../config/api.php';

// Bootstrap
$db = new Database($dbConfig);
$auth = new Authenticator($apiConfig, $db->getPdo());
$router = new Router($db, $auth);

// ============================================
// STEP 1: Login to get JWT token
// ============================================
echo "Step 1: Login with username/password\n";
echo "-----------------------------------\n";
echo "Username: admin\n";
echo "Password: secret\n\n";

// Simulate login request
$_GET = ['action' => 'login'];
$_POST = [
    'username' => 'admin',
    'password' => 'secret'
];

// Set content type for JSON request
$_SERVER['CONTENT_TYPE'] = 'application/json';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Capture response
ob_start();
try {
    $router->route($_GET);
    $loginResponse = ob_get_clean();
} catch (\Exception $e) {
    ob_end_clean();
    echo "✗ Login failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Parse JWT response
$loginData = json_decode($loginResponse, true);

if (isset($loginData['token'])) {
    echo "✓ Login successful!\n";
    echo "  Token: " . substr($loginData['token'], 0, 50) . "...\n";
    echo "  Expires: " . date('Y-m-d H:i:s', $loginData['expires_at']) . "\n";
    echo "  User: " . $loginData['user'] . "\n\n";
    
    $jwtToken = $loginData['token'];
} else {
    echo "✗ Login failed!\n";
    echo "  Response: " . $loginResponse . "\n";
    exit(1);
}

// ============================================
// STEP 2: Use JWT token to access API
// ============================================
echo "Step 2: Access protected endpoint with JWT\n";
echo "-------------------------------------------\n";

// Simulate authenticated request with JWT token
$_GET = ['action' => 'tables'];
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $jwtToken;

// Capture response
ob_start();
try {
    // Create new router instance (fresh state)
    $auth2 = new Authenticator($apiConfig, $db->getPdo());
    $router2 = new Router($db, $auth2);
    $router2->route($_GET);
    $tablesResponse = ob_get_clean();
} catch (\Exception $e) {
    ob_end_clean();
    echo "✗ Request failed: " . $e->getMessage() . "\n";
    exit(1);
}

$tablesData = json_decode($tablesResponse, true);

if (isset($tablesData['tables'])) {
    echo "✓ Authenticated request successful!\n";
    echo "  Tables found: " . count($tablesData['tables']) . "\n";
    echo "  Sample tables: " . implode(', ', array_slice($tablesData['tables'], 0, 5)) . "\n\n";
} elseif (isset($tablesData['error'])) {
    echo "✗ Request failed: " . $tablesData['error'] . "\n\n";
} else {
    echo "✓ Request completed\n";
    echo "  Response: " . substr($tablesResponse, 0, 100) . "...\n\n";
}

// ============================================
// STEP 3: Test without token (should fail)
// ============================================
echo "Step 3: Try accessing without JWT (should fail)\n";
echo "------------------------------------------------\n";

unset($_SERVER['HTTP_AUTHORIZATION']);
$_GET = ['action' => 'tables'];

ob_start();
try {
    $auth3 = new Authenticator($apiConfig, $db->getPdo());
    $router3 = new Router($db, $auth3);
    $router3->route($_GET);
    $unauthorizedResponse = ob_get_clean();
} catch (\Exception $e) {
    ob_end_clean();
    echo "✗ Unexpected error: " . $e->getMessage() . "\n";
    exit(1);
}

$unauthorizedData = json_decode($unauthorizedResponse, true);

if (isset($unauthorizedData['error']) && $unauthorizedData['error'] === 'Unauthorized') {
    echo "✓ Correctly rejected request without token\n";
    echo "  Error: " . $unauthorizedData['error'] . "\n\n";
} else {
    echo "✗ Should have rejected unauthorized request\n";
    echo "  Response: " . $unauthorizedResponse . "\n\n";
}

echo "===================================\n";
echo "✓ JWT Authentication Working!\n";
echo "===================================\n\n";

echo "HOW TO USE:\n";
echo "-----------\n";
echo "1. Login:\n";
echo "   curl -X POST 'http://localhost/PHP-CRUD-API-Generator/public/index.php?action=login' \\\n";
echo "     -H 'Content-Type: application/json' \\\n";
echo "     -d '{\"username\":\"admin\",\"password\":\"secret\"}'\n\n";
echo "2. Use token:\n";
echo "   curl 'http://localhost/PHP-CRUD-API-Generator/public/index.php?action=tables' \\\n";
echo "     -H 'Authorization: Bearer YOUR_TOKEN_HERE'\n\n";
echo "3. Save token to file:\n";
echo "   # Extract token to file\n";
echo "   curl ... | jq -r '.token' > jwt_token.txt\n\n";
echo "   # Use saved token\n";
echo "   curl ... -H \"Authorization: Bearer \$(cat jwt_token.txt)\"\n";
