<?php

/**
 * Cache System Test
 * 
 * This script tests the file cache implementation to ensure:
 * - Cache writes and reads work
 * - TTL expiration works
 * - Pattern-based deletion works
 * - Cache statistics are accurate
 * 
 * Run: php tests/cache_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Cache\CacheManager;

echo "===================================\n";
echo "Cache System Test\n";
echo "===================================\n\n";

// Load cache config
$config = require __DIR__ . '/../config/cache.php';

// Initialize cache manager
$cache = new CacheManager($config);

echo "✓ Cache manager initialized\n";
echo "  Driver: " . $config['driver'] . "\n";
echo "  Enabled: " . ($config['enabled'] ? 'yes' : 'no') . "\n\n";

// Test 1: Write and Read
echo "Test 1: Write and Read\n";
echo "------------------------\n";

$testData = [
    'id' => 123,
    'name' => 'John Doe',
    'email' => 'john@example.com'
];

$key = $cache->generateKey('users', ['page' => 1]);
echo "  Cache key: $key\n";

$result = $cache->set($key, $testData, 'users');
echo "  Write: " . ($result ? '✓ Success' : '✗ Failed') . "\n";

$retrieved = $cache->get($key);
echo "  Read: " . ($retrieved === $testData ? '✓ Success' : '✗ Failed') . "\n";
echo "  Data matches: " . ($retrieved === $testData ? 'yes' : 'no') . "\n\n";

// Test 2: Cache Miss
echo "Test 2: Cache Miss\n";
echo "------------------------\n";

$nonExistent = $cache->get('nonexistent:key:12345');
echo "  Non-existent key: " . ($nonExistent === null ? '✓ Returns null' : '✗ Should return null') . "\n\n";

// Test 3: TTL Configuration
echo "Test 3: TTL Configuration\n";
echo "------------------------\n";

$usersTtl = $cache->getTtl('users');
$productsTtl = $cache->getTtl('products');
$defaultTtl = $cache->getTtl('unknown_table');

echo "  Users TTL: $usersTtl seconds\n";
echo "  Products TTL: $productsTtl seconds\n";
echo "  Default TTL: $defaultTtl seconds\n\n";

// Test 4: Should Cache Check
echo "Test 4: Should Cache Check\n";
echo "------------------------\n";

$shouldCacheUsers = $cache->shouldCache('users');
$shouldCacheSessions = $cache->shouldCache('sessions');

echo "  Should cache 'users': " . ($shouldCacheUsers ? 'yes ✓' : 'no') . "\n";
echo "  Should cache 'sessions': " . ($shouldCacheSessions ? 'no ✓' : 'yes ✗') . "\n\n";

// Test 5: Multiple Cache Entries
echo "Test 5: Multiple Cache Entries\n";
echo "------------------------\n";

// Cache multiple pages of users
for ($i = 1; $i <= 3; $i++) {
    $key = $cache->generateKey('users', ['page' => $i]);
    $data = ['page' => $i, 'users' => ["user{$i}"]];
    $cache->set($key, $data, 'users');
    echo "  Cached users page $i\n";
}

// Cache some products
for ($i = 1; $i <= 2; $i++) {
    $key = $cache->generateKey('products', ['page' => $i]);
    $data = ['page' => $i, 'products' => ["product{$i}"]];
    $cache->set($key, $data, 'products');
    echo "  Cached products page $i\n";
}

echo "\n";

// Test 6: Cache Statistics
echo "Test 6: Cache Statistics\n";
echo "------------------------\n";

$stats = $cache->getStats();
echo "  Driver: " . $stats['driver'] . "\n";
echo "  Total files: " . $stats['total_files'] . "\n";
echo "  Valid files: " . $stats['valid_files'] . "\n";
echo "  Total size: " . $stats['total_size_human'] . "\n";
echo "  Hit ratio: " . ($stats['hit_ratio'] * 100) . "%\n\n";

// Test 7: Table Invalidation
echo "Test 7: Table Invalidation\n";
echo "------------------------\n";

echo "  Before invalidation:\n";
$key1 = $cache->generateKey('users', ['page' => 1]);
$beforeInvalidation = $cache->get($key1);
echo "    Users page 1 exists: " . ($beforeInvalidation !== null ? 'yes' : 'no') . "\n";

// Invalidate all users cache
$cache->invalidateTable('users');
echo "  ✓ Invalidated 'users' table cache\n";

$afterInvalidation = $cache->get($key1);
echo "    Users page 1 exists: " . ($afterInvalidation !== null ? 'yes' : 'no') . "\n";

// Products should still be cached
$productsKey = $cache->generateKey('products', ['page' => 1]);
$productsCache = $cache->get($productsKey);
echo "    Products page 1 exists: " . ($productsCache !== null ? 'yes ✓' : 'no') . "\n\n";

// Test 8: Cache Clear
echo "Test 8: Cache Clear\n";
echo "------------------------\n";

echo "  Before clear:\n";
$stats = $cache->getStats();
echo "    Total files: " . $stats['total_files'] . "\n";

$cache->clear();
echo "  ✓ Cleared entire cache\n";

$stats = $cache->getStats();
echo "  After clear:\n";
echo "    Total files: " . $stats['total_files'] . "\n\n";

// Test 9: TTL Expiration (Short TTL)
echo "Test 9: TTL Expiration\n";
echo "------------------------\n";

$shortKey = 'test:expiration:' . time();
$cache->getDriver()->set($shortKey, ['data' => 'expires soon'], 2); // 2 second TTL
echo "  Set cache with 2 second TTL\n";

$immediate = $cache->get($shortKey);
echo "  Immediate read: " . ($immediate !== null ? '✓ Found' : '✗ Not found') . "\n";

echo "  Waiting 3 seconds for expiration...\n";
sleep(3);

$afterExpiration = $cache->get($shortKey);
echo "  After expiration: " . ($afterExpiration === null ? '✓ Expired correctly' : '✗ Should be expired') . "\n\n";

// Final Statistics
echo "===================================\n";
echo "Final Cache Statistics\n";
echo "===================================\n";

$finalStats = $cache->getStats();
echo "Driver: " . $finalStats['driver'] . "\n";
echo "Cache Path: " . $finalStats['cache_path'] . "\n";
echo "Total Files: " . $finalStats['total_files'] . "\n";
echo "Valid Files: " . $finalStats['valid_files'] . "\n";
echo "Expired Files: " . $finalStats['expired_files'] . "\n";
echo "Total Size: " . $finalStats['total_size_human'] . "\n";
echo "Cache Hits: " . $finalStats['hits'] . "\n";
echo "Cache Misses: " . $finalStats['misses'] . "\n";
echo "Hit Ratio: " . ($finalStats['hit_ratio'] * 100) . "%\n";

echo "\n===================================\n";
echo "✓ All cache tests completed!\n";
echo "===================================\n";
