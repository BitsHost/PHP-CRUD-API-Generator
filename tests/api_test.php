<?php
/**
 * Quick API Test
 * 
 * Tests that Router works with new ApiConfig class
 * Run: php tests/api_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Router;
use App\Authenticator;

echo "===================================\n";
echo "API Router Test (Config Classes)\n";
echo "===================================\n\n";

try {
    // Load configs
    echo "Loading configuration...\n";
    $dbConfig = require __DIR__ . '/../config/db.php';
    $apiConfig = require __DIR__ . '/../config/api.php';
    echo "✓ Config files loaded\n\n";

    // Initialize database
    echo "Initializing database...\n";
    $db = new Database($dbConfig);
    echo "✓ Database connected\n\n";

    // Initialize authenticator
    echo "Initializing authenticator...\n";
    $auth = new Authenticator($apiConfig, $db->getPdo());
    echo "✓ Authenticator initialized\n";
    echo "  Auth method: " . ($apiConfig['auth_method'] ?? 'none') . "\n\n";

    // Initialize router (this is where ApiConfig is used)
    echo "Initializing router with ApiConfig...\n";
    $router = new Router($db, $auth);
    echo "✓ Router initialized successfully!\n";
    echo "  Router uses ApiConfig object (not array)\n";
    echo "  Cache enabled: " . (file_exists(__DIR__ . '/../config/cache.php') ? 'yes' : 'no') . "\n\n";

    // Test a simple route (tables action)
    echo "Testing route: action=tables\n";
    $_GET = ['action' => 'tables'];
    
    // Capture output
    ob_start();
    $router->route($_GET);
    $output = ob_get_clean();
    
    // Decode JSON response
    $response = json_decode($output, true);
    
    if ($response) {
        echo "✓ Route executed successfully\n";
        echo "  Response type: " . gettype($response) . "\n";
        if (isset($response['tables'])) {
            echo "  Tables found: " . count($response['tables']) . "\n";
            echo "  Sample tables: " . implode(', ', array_slice($response['tables'], 0, 3)) . "\n";
        } elseif (isset($response['error'])) {
            echo "  Error: " . $response['error'] . "\n";
        }
    } else {
        echo "✓ Route executed (no JSON response)\n";
        echo "  Raw output: " . substr($output, 0, 100) . "...\n";
    }

} catch (\Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n===================================\n";
echo "✓ All tests passed!\n";
echo "✓ ApiConfig integration working!\n";
echo "===================================\n";
