# Caching Implementation - Technical Analysis

Complete breakdown of what's needed to add response caching to PHP-CRUD-API-Generator.

---

## 📊 Implementation Overview

**Total Code:** ~500 lines (new Cache class + Router integration)  
**Files Modified:** 2 files  
**Files Created:** 2 files  
**Complexity:** Medium  
**Time Estimate:** 4-6 hours for full implementation + testing

---

## 📁 Files Affected

### **Files to CREATE:**

1. **`src/Cache.php`** (~350 lines)
   - Main cache abstraction class
   - Supports multiple drivers (Redis, Memcached, APCu, File)
   - Cache key generation
   - TTL management
   - Auto-invalidation logic

2. **`config/cache.php`** (~50 lines)
   - Cache configuration file
   - Driver settings
   - Per-table TTL settings
   - Exclusion rules

### **Files to MODIFY:**

1. **`src/Router.php`** (~50 lines added)
   - Add cache checks before database queries
   - Add cache invalidation on write operations
   - Add cache-related HTTP headers

2. **`composer.json`** (~10 lines added)
   - Add optional cache driver dependencies
   - Suggest: predis/predis, php-memcached extension

---

## 🔧 Code Examples

### 1. New Cache Class (`src/Cache.php`)

```php
<?php

namespace App;

/**
 * Response Cache Manager
 * 
 * Provides caching abstraction with support for multiple backends
 * (Redis, Memcached, APCu, File). Automatically invalidates cache
 * on write operations.
 */
class Cache
{
    private $driver;
    private array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->initializeDriver($config['driver'] ?? 'file');
    }
    
    /**
     * Initialize cache driver
     */
    private function initializeDriver(string $driver): void
    {
        switch ($driver) {
            case 'redis':
                $this->driver = new CacheDrivers\RedisDriver($this->config);
                break;
            case 'memcached':
                $this->driver = new CacheDrivers\MemcachedDriver($this->config);
                break;
            case 'apcu':
                $this->driver = new CacheDrivers\ApcuDriver($this->config);
                break;
            case 'file':
            default:
                $this->driver = new CacheDrivers\FileDriver($this->config);
                break;
        }
    }
    
    /**
     * Get cached response
     * 
     * @param string $key Cache key
     * @return mixed|null Cached data or null if not found/expired
     */
    public function get(string $key)
    {
        if (!$this->config['enabled']) {
            return null;
        }
        
        return $this->driver->get($key);
    }
    
    /**
     * Store response in cache
     * 
     * @param string $key Cache key
     * @param mixed $value Data to cache
     * @param int|null $ttl Time to live in seconds (null = use default)
     * @return bool Success status
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }
        
        $ttl = $ttl ?? $this->config['ttl'] ?? 300;
        return $this->driver->set($key, $value, $ttl);
    }
    
    /**
     * Invalidate cache for specific table
     * 
     * @param string $table Table name
     * @return bool Success status
     */
    public function invalidateTable(string $table): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }
        
        // Delete all cache keys matching table pattern
        $pattern = $this->getTableCachePattern($table);
        return $this->driver->deletePattern($pattern);
    }
    
    /**
     * Generate cache key for request
     * 
     * @param string $table Table name
     * @param array $params Request parameters
     * @return string Unique cache key
     */
    public function generateKey(string $table, array $params): string
    {
        // Sort params for consistent keys
        ksort($params);
        
        // Include user/api key if configured to vary cache by user
        $varyBy = $this->config['varyBy'] ?? [];
        $userKey = '';
        
        if (in_array('api_key', $varyBy)) {
            $headers = getallheaders();
            $apiKey = $headers['X-API-Key'] ?? ($_GET['api_key'] ?? '');
            $userKey .= ':' . hash('sha256', $apiKey);
        }
        
        if (in_array('user_id', $varyBy)) {
            // Would get from auth context
            $userKey .= ':user_' . ($_SESSION['user_id'] ?? 'anonymous');
        }
        
        return sprintf(
            'api:table:%s:params:%s%s',
            $table,
            md5(json_encode($params)),
            $userKey
        );
    }
    
    /**
     * Get TTL for specific table
     * 
     * @param string $table Table name
     * @return int TTL in seconds
     */
    public function getTtl(string $table): int
    {
        // Check per-table config
        if (isset($this->config['perTable'][$table])) {
            return $this->config['perTable'][$table];
        }
        
        // Return default TTL
        return $this->config['ttl'] ?? 300;
    }
    
    /**
     * Check if table should be cached
     * 
     * @param string $table Table name
     * @return bool True if cacheable
     */
    public function shouldCache(string $table): bool
    {
        // Check if caching is enabled
        if (!$this->config['enabled']) {
            return false;
        }
        
        // Check exclusion list
        $excludeTables = $this->config['excludeTables'] ?? [];
        if (in_array($table, $excludeTables)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get cache pattern for table
     */
    private function getTableCachePattern(string $table): string
    {
        return 'api:table:' . $table . ':*';
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache stats
     */
    public function getStats(): array
    {
        return $this->driver->getStats();
    }
}
```

**Lines:** ~200 lines for main Cache class

---

### 2. Redis Driver (`src/CacheDrivers/RedisDriver.php`)

```php
<?php

namespace App\CacheDrivers;

use Predis\Client as RedisClient;

class RedisDriver implements CacheDriverInterface
{
    private RedisClient $redis;
    
    public function __construct(array $config)
    {
        $this->redis = new RedisClient([
            'scheme' => 'tcp',
            'host'   => $config['redis']['host'] ?? '127.0.0.1',
            'port'   => $config['redis']['port'] ?? 6379,
            'password' => $config['redis']['password'] ?? null,
            'database' => $config['redis']['database'] ?? 0,
        ]);
    }
    
    public function get(string $key)
    {
        $value = $this->redis->get($key);
        return $value ? json_decode($value, true) : null;
    }
    
    public function set(string $key, $value, int $ttl): bool
    {
        return (bool) $this->redis->setex($key, $ttl, json_encode($value));
    }
    
    public function deletePattern(string $pattern): bool
    {
        $keys = $this->redis->keys($pattern);
        if (!empty($keys)) {
            $this->redis->del($keys);
        }
        return true;
    }
    
    public function getStats(): array
    {
        $info = $this->redis->info('stats');
        return [
            'driver' => 'redis',
            'total_commands' => $info['total_commands_processed'] ?? 0,
            'used_memory' => $info['used_memory_human'] ?? 'N/A',
            'connected_clients' => $info['connected_clients'] ?? 0,
        ];
    }
}
```

**Lines:** ~60 lines per driver  
**Total for all drivers:** ~240 lines (Redis, Memcached, APCu, File)

---

### 3. Configuration File (`config/cache.php`)

```php
<?php

return [
    // Enable/disable caching
    'enabled' => true,
    
    // Cache driver: redis, memcached, apcu, file
    'driver' => 'redis',
    
    // Default TTL (time to live) in seconds
    'ttl' => 300,  // 5 minutes
    
    // Per-table TTL overrides
    'perTable' => [
        'users' => 60,         // Cache users for 1 minute
        'products' => 300,     // Cache products for 5 minutes
        'posts' => 600,        // Cache posts for 10 minutes
        'categories' => 3600,  // Cache categories for 1 hour
        'settings' => 86400,   // Cache settings for 24 hours
    ],
    
    // Tables to exclude from caching (never cache these)
    'excludeTables' => [
        'sessions',           // Session data (always fresh)
        'logs',              // Log entries (always fresh)
        'audit_trail',       // Audit logs
        'rate_limits',       // Rate limit counters
        'active_users',      // Real-time data
    ],
    
    // Vary cache by these identifiers
    // 'api_key' = different cache per API key
    // 'user_id' = different cache per user
    'varyBy' => ['api_key'],
    
    // Redis configuration
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0,
    ],
    
    // Memcached configuration
    'memcached' => [
        'host' => '127.0.0.1',
        'port' => 11211,
    ],
    
    // File cache configuration
    'file' => [
        'path' => __DIR__ . '/../storage/cache',
        'permissions' => 0755,
    ],
];
```

**Lines:** ~60 lines

---

### 4. Router Integration (`src/Router.php` modifications)

**ADD to Router constructor:**

```php
private ?Cache $cache = null;

public function __construct(Database $db, Authenticator $auth)
{
    // ... existing code ...
    
    // Initialize cache if enabled
    $cacheConfig = require __DIR__ . '/../config/cache.php';
    if ($cacheConfig['enabled']) {
        $this->cache = new Cache($cacheConfig);
    }
}
```

**MODIFY the 'list' action to use cache:**

```php
case 'list':
    if (isset($query['table'])) {
        if (!Validator::validateTableName($query['table'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid table name']);
            break;
        }
        
        $this->enforceRbac('list', $query['table']);
        
        $opts = [
            'filter' => $query['filter'] ?? null,
            'sort' => $query['sort'] ?? null,
            'page' => Validator::validatePage($query['page'] ?? 1),
            'page_size' => Validator::validatePageSize($query['page_size'] ?? 20),
            'fields' => $query['fields'] ?? null,
        ];
        
        // ========================================
        // CACHE CHECK - NEW CODE
        // ========================================
        $cacheHit = false;
        $result = null;
        
        if ($this->cache && $this->cache->shouldCache($query['table'])) {
            $cacheKey = $this->cache->generateKey($query['table'], $opts);
            $result = $this->cache->get($cacheKey);
            
            if ($result !== null) {
                $cacheHit = true;
                // Add cache headers
                header('X-Cache-Hit: true');
                header('X-Cache-Key: ' . $cacheKey);
                
                // Get remaining TTL
                $ttl = $this->cache->getTtl($query['table']);
                header('X-Cache-TTL: ' . $ttl);
            }
        }
        
        // If not cached, fetch from database
        if ($result === null) {
            // Validate sort if provided
            if (isset($opts['sort']) && !Validator::validateSort($opts['sort'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid sort parameter']);
                break;
            }
            
            $result = $this->api->list($query['table'], $opts);
            
            // Store in cache
            if ($this->cache && $this->cache->shouldCache($query['table'])) {
                $ttl = $this->cache->getTtl($query['table']);
                $this->cache->set($cacheKey, $result, $ttl);
                
                // Add cache headers
                header('X-Cache-Hit: false');
                header('X-Cache-Stored: true');
                header('X-Cache-TTL: ' . $ttl);
            }
        }
        // ========================================
        // END CACHE CHECK
        // ========================================
        
        $this->logResponse($result, 200, $query);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Missing table parameter']);
    }
    break;
```

**ADD cache invalidation to 'create', 'update', 'delete':**

```php
case 'create':
    // ... existing code ...
    $result = $this->api->create($query['table'], $data);
    
    // ========================================
    // INVALIDATE CACHE - NEW CODE
    // ========================================
    if ($this->cache) {
        $this->cache->invalidateTable($query['table']);
    }
    // ========================================
    
    $this->logResponse($result, 201, $query);
    echo json_encode($result);
    break;

case 'update':
    // ... existing code ...
    $result = $this->api->update($query['table'], $query['id'], $data);
    
    // Invalidate cache
    if ($this->cache) {
        $this->cache->invalidateTable($query['table']);
    }
    
    $this->logResponse($result, 200, $query);
    echo json_encode($result);
    break;

case 'delete':
    // ... existing code ...
    $result = $this->api->delete($query['table'], $query['id']);
    
    // Invalidate cache
    if ($this->cache) {
        $this->cache->invalidateTable($query['table']);
    }
    
    $this->logResponse($result, 200, $query);
    echo json_encode($result);
    break;
```

**Total Router modifications:** ~100 lines added

---

## 📦 Dependencies

### Required (none - works with file cache)

### Optional (for better performance):

**Add to `composer.json`:**

```json
{
    "require": {
        "php": "^8.0"
    },
    "suggest": {
        "predis/predis": "^2.0 - For Redis cache driver",
        "ext-memcached": "* - For Memcached cache driver",
        "ext-apcu": "* - For APCu cache driver"
    }
}
```

---

## 🚀 Usage Examples

### Example 1: First Request (Cache Miss)

```bash
GET /api/?action=list&table=products&page=1

# Response Headers:
HTTP/1.1 200 OK
X-Cache-Hit: false
X-Cache-Stored: true
X-Cache-TTL: 300

# Response Body:
{
  "records": [...],
  "pagination": {...}
}

# Database query executed: YES
# Response time: 150ms
```

### Example 2: Second Request (Cache Hit)

```bash
GET /api/?action=list&table=products&page=1

# Response Headers:
HTTP/1.1 200 OK
X-Cache-Hit: true
X-Cache-Key: api:table:products:params:a3f5c8...
X-Cache-TTL: 295

# Response Body:
{
  "records": [...],
  "pagination": {...}
}

# Database query executed: NO
# Response time: 5ms  (30x faster!)
```

### Example 3: After Write Operation (Cache Invalidated)

```bash
POST /api/?action=create&table=products
{"name": "New Product"}

# Response:
{"id": 123}

# All cache keys for 'products' table are deleted
# Next GET request will be cache miss and refetch data
```

### Example 4: Per-User Caching

```php
// config/cache.php
'varyBy' => ['api_key'],

// User A requests:
GET /api/?action=list&table=users
X-API-Key: key-user-a

# Cache Key: api:table:users:params:...:hash(key-user-a)

// User B requests:
GET /api/?action=list&table=users
X-API-Key: key-user-b

# Cache Key: api:table:users:params:...:hash(key-user-b)
# Different cache! Users can have different permissions/data
```

---

## 📊 Performance Impact

### Without Cache:
```
GET /api/?action=list&table=products&page_size=100

Database Query Time: 120ms
JSON Serialization: 15ms
Total Response Time: 135ms
```

### With Cache (Hit):
```
GET /api/?action=list&table=products&page_size=100

Redis Get: 2ms
JSON Serialization: 0ms (already serialized)
Total Response Time: 2ms

Improvement: 67.5x faster!
```

### Real-World Scenario (100 requests/second):

**Without Cache:**
- 100 requests × 135ms = 13,500ms of database time per second
- Database: Overloaded ❌
- Scaling needed: Add more DB servers 💰

**With Cache (95% hit rate):**
- 5 cache misses × 135ms = 675ms of database time
- 95 cache hits × 2ms = 190ms of Redis time
- Total: 865ms (vs 13,500ms)
- Database: Relaxed ✅
- Scaling needed: None! 🎉

---

## 🔧 Implementation Checklist

### Phase 1: Core Cache System (2 hours)
- [ ] Create `src/Cache.php` class
- [ ] Create `src/CacheDrivers/CacheDriverInterface.php`
- [ ] Create `src/CacheDrivers/FileDriver.php` (always works)
- [ ] Create `config/cache.php`
- [ ] Test file cache driver

### Phase 2: Router Integration (1 hour)
- [ ] Add cache initialization in Router constructor
- [ ] Modify 'list' action with cache check
- [ ] Modify 'read' action with cache check
- [ ] Add cache invalidation to 'create'
- [ ] Add cache invalidation to 'update'
- [ ] Add cache invalidation to 'delete'
- [ ] Add cache headers to responses

### Phase 3: Additional Drivers (2 hours)
- [ ] Create `src/CacheDrivers/RedisDriver.php`
- [ ] Create `src/CacheDrivers/MemcachedDriver.php`
- [ ] Create `src/CacheDrivers/ApcuDriver.php`
- [ ] Update composer.json with suggestions
- [ ] Test each driver

### Phase 4: Testing & Documentation (1 hour)
- [ ] Write unit tests
- [ ] Test cache invalidation
- [ ] Test different TTL settings
- [ ] Test per-user caching
- [ ] Write documentation
- [ ] Create examples

---

## 🎯 Complexity Assessment

### Easy Parts ✅
- File cache driver (standard PHP file operations)
- Cache key generation (just MD5 hashing)
- Configuration file (simple array)
- Cache headers (standard HTTP headers)

### Medium Parts ⚠️
- Cache invalidation logic (pattern matching)
- Redis/Memcached drivers (requires libraries)
- Per-user cache variation (depends on auth)
- Router integration (careful placement)

### Hard Parts ❌
- None! This is a straightforward implementation

---

## 💡 Recommended Approach

### Option 1: Start Simple (File Cache Only)
**Time:** 2-3 hours  
**Benefits:**
- No dependencies
- Works everywhere
- Good for development/testing

**Implementation:**
1. Create Cache class with file driver only
2. Integrate into Router
3. Test thoroughly
4. Ship it!

### Option 2: Full Implementation (All Drivers)
**Time:** 4-6 hours  
**Benefits:**
- Production-ready
- Maximum performance (Redis)
- Flexible deployment options

**Implementation:**
1. Create Cache class with all drivers
2. Add composer dependencies
3. Integrate into Router
4. Test all drivers
5. Document everything

---

## 🚀 ROI Analysis

### Development Cost:
- 4-6 hours of development time

### Benefits:
- 10-100x performance improvement
- Reduced database load (save $$ on scaling)
- Better user experience (faster responses)
- Competitive advantage over PHP-CRUD-API v2
- Professional feature for public APIs

### Breakeven:
- First production deployment! 🎉

---

## ❓ Questions Before We Start

1. **Which cache driver do you want to prioritize?**
   - File cache (simplest, no dependencies)
   - Redis (best performance, production-ready)
   - All drivers (most flexible)

2. **Should we implement per-user caching?**
   - Yes (different users see different data)
   - No (same cache for everyone)

3. **Default TTL preferences?**
   - Conservative (60 seconds)
   - Moderate (300 seconds = 5 minutes)
   - Aggressive (3600 seconds = 1 hour)

4. **Deployment target?**
   - Development (file cache is fine)
   - Production (Redis recommended)

---

## 📝 Summary

**Total Code:** ~500 lines  
**Complexity:** Medium  
**Time:** 4-6 hours  
**Impact:** 🔥 MASSIVE (10-100x performance)

**Files:**
- ✅ Create: `src/Cache.php` (200 lines)
- ✅ Create: `src/CacheDrivers/*.php` (240 lines)
- ✅ Create: `config/cache.php` (60 lines)
- ✅ Modify: `src/Router.php` (+100 lines)
- ✅ Modify: `composer.json` (+10 lines)

**Ready to implement?** 🚀

I recommend starting with **File Cache** first (simplest, 2-3 hours), then adding Redis support later if needed!
