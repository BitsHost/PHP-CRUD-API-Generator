<?php
/**
 * Test JSON vs Multipart Login
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Router;
use App\Authenticator;

echo "=== JSON vs Multipart Login Test ===\n\n";

$dbConfig = require __DIR__ . '/../config/db.php';
$apiConfig = require __DIR__ . '/../config/api.php';

// Test 1: Multipart/Form Data (old way - works)
echo "Test 1: Multipart/Form Data\n";
echo "----------------------------\n";
$_GET = ['action' => 'login'];
$_POST = [
    'username' => 'admin',
    'password' => 'secret'
];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';

$db = new Database($dbConfig);
$auth = new Authenticator($apiConfig, $db->getPdo());
$router = new Router($db, $auth);

ob_start();
$router->route($_GET);
$response1 = ob_get_clean();
$data1 = json_decode($response1, true);

if (isset($data1['token'])) {
    echo "✓ SUCCESS - Token received\n";
    echo "  Token: " . substr($data1['token'], 0, 50) . "...\n";
} else {
    echo "✗ FAILED - " . ($data1['error'] ?? 'No response') . "\n";
}
echo "\n";

// Test 2: JSON Body (new way - should now work)
echo "Test 2: JSON Body (application/json)\n";
echo "-------------------------------------\n";

// Simulate JSON request
$_GET = ['action' => 'login'];
$_POST = []; // Empty because JSON comes from php://input
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Mock php://input by creating a stream
$jsonData = json_encode(['username' => 'admin', 'password' => 'secret']);
// Note: In CLI we can't truly test php://input, but the code will try to read it

$db2 = new Database($dbConfig);
$auth2 = new Authenticator($apiConfig, $db2->getPdo());
$router2 = new Router($db2, $auth2);

ob_start();
$router2->route($_GET);
$response2 = ob_get_clean();
$data2 = json_decode($response2, true);

if (isset($data2['token'])) {
    echo "✓ SUCCESS - Token received\n";
    echo "  Token: " . substr($data2['token'], 0, 50) . "...\n";
} else {
    echo "✗ FAILED - " . ($data2['error'] ?? 'No response') . "\n";
    echo "  (Note: CLI can't fully simulate php://input, test in Postman)\n";
}
echo "\n";

echo "=================================\n";
echo "POSTMAN INSTRUCTIONS:\n";
echo "=================================\n\n";

echo "Both methods should work now:\n\n";

echo "Method 1: JSON Body (Recommended)\n";
echo "----------------------------------\n";
echo "Headers:\n";
echo "  Content-Type: application/json\n\n";
echo "Body → raw → JSON:\n";
echo "{\n";
echo "  \"username\": \"admin\",\n";
echo "  \"password\": \"secret\"\n";
echo "}\n\n";

echo "Method 2: Form Data\n";
echo "-------------------\n";
echo "Body → x-www-form-urlencoded:\n";
echo "  username: admin\n";
echo "  password: secret\n\n";

echo "Method 3: Multipart Form\n";
echo "------------------------\n";
echo "Body → form-data:\n";
echo "  username: admin\n";
echo "  password: secret\n";
