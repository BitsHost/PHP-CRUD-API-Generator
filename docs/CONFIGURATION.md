# Configuration Architecture

## PSR-4 Config Classes vs Legacy Arrays

### The Problem

Previously, the Router loaded configuration from raw PHP array files:

```php
// OLD WAY - NOT PSR-4 compliant ❌
$this->apiConfig = require __DIR__ . '/../config/api.php';
$this->authEnabled = $this->apiConfig['auth_enabled'] ?? true;
```

**Issues:**
- ❌ Not object-oriented
- ❌ No type safety
- ❌ No IDE autocomplete
- ❌ Difficult to test
- ❌ Inconsistent with PSR-4 architecture

---

## The Solution: Config Classes

We now use **PSR-4 compliant Config classes** that provide:

- ✅ Object-oriented design
- ✅ Type safety with return types
- ✅ Full IDE autocomplete
- ✅ Easy testing and mocking
- ✅ Getter/setter methods
- ✅ Validation logic
- ✅ Backward compatibility

---

## Config Classes

### 1. ApiConfig

**Location:** `src/Config/ApiConfig.php`

**Purpose:** Manages all API settings (authentication, RBAC, rate limiting, logging, monitoring)

**Usage:**

```php
use App\Config\ApiConfig;

// Load from config/api.php (backward compatible)
$config = ApiConfig::fromFile(__DIR__ . '/../config/api.php');

// Or create programmatically
$config = new ApiConfig([
    'auth_enabled' => true,
    'auth_method' => 'jwt',
    'jwt_secret' => 'my-secret-key',
]);

// Type-safe getters
$isAuthEnabled = $config->isAuthEnabled(); // bool
$authMethod = $config->getAuthMethod();     // string
$roles = $config->getRoles();               // array

// Dynamic setters
$config->setAuthMethod('apikey');
$config->addApiKey('new-key-123');
$config->assignUserRole('john', 'admin');
```

**Key Methods:**

```php
// Authentication
isAuthEnabled(): bool
getAuthMethod(): string
getApiKeys(): array
getJwtSecret(): string
useDatabaseAuth(): bool

// RBAC
getRoles(): array
getUserRoles(): array
getUserRole(string $username): ?string
assignUserRole(string $username, string $role): void

// Rate Limiting
getRateLimitConfig(): array

// Logging & Monitoring
getLoggingConfig(): array
getMonitoringConfig(): array
isMonitoringEnabled(): bool

// Backward compatibility
toArray(): array
```

---

### 2. CacheConfig

**Location:** `src/Config/CacheConfig.php`

**Purpose:** Manages cache settings (driver, TTL, excluded tables)

**Usage:**

```php
use App\Config\CacheConfig;

// Load from config/cache.php (backward compatible)
$config = CacheConfig::fromFile(__DIR__ . '/../config/cache.php');

// Or create programmatically
$config = new CacheConfig([
    'enabled' => true,
    'driver' => 'file',
    'ttl' => 300,
    'table_ttl' => [
        'users' => 300,
        'products' => 600,
    ],
]);

// Type-safe getters
$isEnabled = $config->isEnabled();              // bool
$driver = $config->getDriver();                 // string
$ttl = $config->getTableTtl('users');          // int
$shouldCache = $config->shouldCacheTable('logs'); // bool

// Dynamic configuration
$config->enable();
$config->setDriver('redis');
$config->setTableTtl('products', 1800);
$config->excludeTable('sessions');
```

**Key Methods:**

```php
// Core settings
isEnabled(): bool
getDriver(): string
getDefaultTtl(): int
getCachePath(): string

// Table-specific
getTableTtl(string $table): int
shouldCacheTable(string $table): bool
setTableTtl(string $table, int $ttl): void
excludeTable(string $table): void
includeTable(string $table): void

// Vary by parameters
getVaryBy(): array

// Backward compatibility
toArray(): array
```

---

## Router Integration

**OLD WAY:**

```php
// ❌ Array-based, no type safety
$this->apiConfig = require __DIR__ . '/../config/api.php';
$this->authEnabled = $this->apiConfig['auth_enabled'] ?? true;
$this->rbac = new Rbac(
    $this->apiConfig['roles'] ?? [],
    $this->apiConfig['user_roles'] ?? []
);
```

**NEW WAY:**

```php
// ✅ OOP, type-safe, PSR-4 compliant
$this->apiConfig = ApiConfig::fromFile(__DIR__ . '/../config/api.php');
$this->authEnabled = $this->apiConfig->isAuthEnabled();
$this->rbac = new Rbac(
    $this->apiConfig->getRoles(),
    $this->apiConfig->getUserRoles()
);
```

---

## Backward Compatibility

Config classes support both approaches:

### Legacy Array Files (Still Work)

```php
// config/api.php
return [
    'auth_enabled' => true,
    'auth_method' => 'jwt',
    'jwt_secret' => 'secret',
];
```

### Load via Config Class

```php
$config = ApiConfig::fromFile(__DIR__ . '/config/api.php');
```

### Convert Back to Array

```php
$array = $config->toArray();
// Same structure as config/api.php
```

---

## Benefits Summary

| Feature | Old (Arrays) | New (Config Classes) |
|---------|--------------|----------------------|
| Type safety | ❌ No | ✅ Yes |
| IDE autocomplete | ❌ No | ✅ Yes |
| PSR-4 compliant | ❌ No | ✅ Yes |
| Testable | ❌ Difficult | ✅ Easy |
| Validation | ❌ Manual | ✅ Built-in |
| Documentation | ❌ Comments | ✅ PHPDoc |
| OOP | ❌ No | ✅ Yes |
| Backward compatible | ✅ Yes | ✅ Yes |

---

## Testing Config Classes

```php
use App\Config\ApiConfig;
use App\Config\CacheConfig;

// Test ApiConfig
$config = new ApiConfig(['auth_enabled' => false]);
assert($config->isAuthEnabled() === false);

$config->enableAuth();
assert($config->isAuthEnabled() === true);

$config->setAuthMethod('jwt');
assert($config->getAuthMethod() === 'jwt');

// Test CacheConfig
$config = new CacheConfig(['enabled' => true, 'driver' => 'file']);
assert($config->isEnabled() === true);
assert($config->getDriver() === 'file');

$config->setTableTtl('users', 600);
assert($config->getTableTtl('users') === 600);

$config->excludeTable('sessions');
assert($config->shouldCacheTable('sessions') === false);
```

---

## Migration Guide

### Step 1: Update Router Constructor

**Before:**
```php
$this->apiConfig = require __DIR__ . '/../config/api.php';
```

**After:**
```php
$this->apiConfig = ApiConfig::fromFile(__DIR__ . '/../config/api.php');
```

### Step 2: Update Array Access

**Before:**
```php
$isEnabled = $this->apiConfig['auth_enabled'] ?? true;
$roles = $this->apiConfig['roles'] ?? [];
```

**After:**
```php
$isEnabled = $this->apiConfig->isAuthEnabled();
$roles = $this->apiConfig->getRoles();
```

### Step 3: Update Imports

```php
use App\Config\ApiConfig;
use App\Config\CacheConfig;
```

---

## Future Enhancements

Planned config classes:

- **DatabaseConfig** - Database connection settings
- **SecurityConfig** - CORS, CSP, rate limiting
- **ValidationConfig** - Input validation rules
- **OpenApiConfig** - OpenAPI specification settings
- **WebhookConfig** - Webhook delivery settings

---

## Best Practices

1. **Always use Config classes in new code**
2. **Keep config/\*.php files for user configuration**
3. **Use `fromFile()` to load from existing config files**
4. **Use `toArray()` for backward compatibility with old code**
5. **Add type hints to all getter methods**
6. **Validate values in setter methods**
7. **Document all config options with PHPDoc**

---

## Example: Full Router Usage

```php
use App\Router;
use App\Database;
use App\Authenticator;
use App\Config\ApiConfig;
use App\Config\CacheConfig;

// Initialize database
$db = new Database([
    'dsn' => 'mysql:host=localhost;dbname=mydb',
    'username' => 'root',
    'password' => 'secret',
]);

// Load API configuration (PSR-4 way)
$apiConfig = ApiConfig::fromFile(__DIR__ . '/config/api.php');

// Initialize authenticator with config
$auth = new Authenticator([
    'auth_method' => $apiConfig->getAuthMethod(),
    'jwt_secret' => $apiConfig->getJwtSecret(),
    'api_keys' => $apiConfig->getApiKeys(),
]);

// Initialize router (automatically loads configs)
$router = new Router($db, $auth);

// Route the request
$router->route($_GET);
```

---

## Conclusion

The new **PSR-4 Config classes** provide:

- 🎯 **Type safety** - No more array key typos
- 🚀 **Better DX** - IDE autocomplete everywhere
- 🧪 **Testability** - Easy to mock and test
- 📚 **Documentation** - PHPDoc for every method
- 🔒 **Validation** - Built-in value checking
- ♻️ **Backward compatible** - Works with existing config files

**This is the correct way to handle configuration in modern PHP applications.**

---

## Config path resolution (v2.1+)

Use project-local config. Do not edit `vendor/`.

Lookup order (`App\Config\ConfigPaths`):

1. Explicit directory passed to the router / helpers
2. `PHPCRUD_CONFIG_DIR` environment variable
3. `./config` when it contains `api.php`
4. Package `config/` directory

```bash
php scripts/install-config.php .   # copy examples → ./config
php scripts/doctor.php             # warn on weak / open settings
```

---

## Table exposure policy (v2.1+)

In `config/api.php`:

```php
'allowed_tables' => [], // empty = all except denied; non-empty = whitelist
'denied_tables' => ['api_users', 'api_key_usage'],
```

Or via env: `API_ALLOWED_TABLES`, `API_DENIED_TABLES` (comma-separated).

Denied tables are blocked even for admin. `action=tables` also respects RBAC visibility.

See [UPGRADE_2.1.md](UPGRADE_2.1.md).
