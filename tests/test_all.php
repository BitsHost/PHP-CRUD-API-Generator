<?php
/**
 * Comprehensive Test Suite
 * Tests all critical functionality before merge
 */

echo "===================================\n";
echo "COMPREHENSIVE TEST SUITE\n";
echo "===================================\n\n";

$passed = 0;
$failed = 0;

require_once __DIR__ . '/../vendor/autoload.php';
// Preload configs so variables are always defined
$apiConfig = \App\Config\ApiConfig::fromFile(__DIR__ . '/../config/api.php');
$cacheConfig = \App\Config\CacheConfig::fromFile(__DIR__ . '/../config/cache.php');

// Test 1: Config Classes
echo "Test 1: Config Classes Loading\n";
echo "------------------------\n";
try {
    echo "  ApiConfig loaded ✓\n";
    echo "  Auth method: " . $apiConfig->getAuthMethod() . "\n";
    echo "  Auth enabled: " . ($apiConfig->isAuthEnabled() ? "yes" : "no") . "\n";
    echo "  CacheConfig loaded ✓\n";
    echo "  Cache enabled: " . ($cacheConfig->isEnabled() ? "yes" : "no") . "\n";
    echo "  Cache driver: " . $cacheConfig->getDriver() . "\n";
    $passed++;
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Test 2: Database Connection
echo "Test 2: Database Connection\n";
echo "------------------------\n";
try {
    $dbConfig = require __DIR__ . '/../config/db.php';
    $db = new \App\Database\Database($dbConfig);
    $pdo = $db->getPdo();
    
    echo "  Database connected: ✓\n";
    echo "  Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    $passed++;
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Test 3: Cache System
echo "Test 3: Cache System\n";
echo "------------------------\n";
try {
    $cache = new \App\Cache\CacheManager($cacheConfig->toArray());
    $stats = $cache->getStats();
    
    echo "  Cache initialized: ✓\n";
    echo "  Driver: " . $stats['driver'] . "\n";
    
    // Test write/read
    $testKey = 'test:' . time();
    $testData = ['test' => 'data', 'time' => time()];
    $cache->set($testKey, $testData, null);
    $retrieved = $cache->get($testKey);
    
    if ($retrieved === $testData) {
        echo "  Write/Read test: ✓\n";
        $passed++;
    } else {
        echo "  ✗ Write/Read test FAILED\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Test 4: Authenticator
echo "Test 4: Authenticator\n";
echo "------------------------\n";
try {
    $auth = new \App\Auth\Authenticator($apiConfig->toArray(), $db->getPdo());
    echo "  Authenticator created: ✓\n";
    
    // Test Basic Auth with config file users
    $_SERVER['PHP_AUTH_USER'] = 'admin';
    $_SERVER['PHP_AUTH_PW'] = 'secret';
    
    $result = $auth->authenticate();
    if ($result) {
        echo "  Basic auth test: ✓ (admin/secret)\n";
        $passed++;
    } else {
        echo "  ✗ Basic auth test FAILED\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Test 5: Router Initialization
echo "Test 5: Router Initialization\n";
echo "------------------------\n";
try {
    $router = new \App\Application\Router($db, new \App\Auth\Authenticator($apiConfig->toArray(), $db->getPdo()));
    echo "  Router created: ✓\n";
    $passed++;
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Test 6: File Structure
echo "Test 6: File Structure\n";
echo "------------------------\n";
try {
    $cacheDir = __DIR__ . '/../storage/cache';
    $cacheDirExists = is_dir($cacheDir);
    $cacheDirWritable = is_writable($cacheDir);
    
    echo "  Cache directory exists: " . ($cacheDirExists ? "✓" : "✗") . "\n";
    echo "  Cache directory writable: " . ($cacheDirWritable ? "✓" : "✗") . "\n";
    
    if ($cacheDirExists && $cacheDirWritable) {
        $passed++;
    } else {
        $failed++;
    }
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Summary
echo "===================================\n";
echo "TEST SUMMARY\n";
echo "===================================\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Total:  " . ($passed + $failed) . "\n";
echo "\n";

if ($failed === 0) {
    echo "✓ All tests passed! Ready for merge.\n";
    exit(0);
} else {
    echo "✗ Some tests failed. Please fix before merge.\n";
    exit(1);
}
