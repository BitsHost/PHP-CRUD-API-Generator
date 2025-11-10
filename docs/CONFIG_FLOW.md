# Configuration Flow Architecture

## Understanding the Two-Layer Config System

### The Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER LEVEL                              │
│  (Where developers configure their API)                        │
└─────────────────────────────────────────────────────────────────┘
                                ↓
    ┌───────────────────────────────────────────────┐
    │  /config/api.php                              │
    │  ─────────────────                            │
    │  User edits this file to configure:           │
    │  • Authentication method (apikey/jwt/basic)   │
    │  • API keys and secrets                       │
    │  • RBAC roles and permissions                 │
    │  • Rate limiting thresholds                   │
    │  • Logging settings                           │
    │                                               │
    │  return [                                     │
    │      'auth_enabled' => true,                  │
    │      'auth_method' => 'jwt',                  │
    │      'jwt_secret' => 'my-secret',             │
    │      ...                                      │
    │  ];                                           │
    └───────────────────────────────────────────────┘
                                ↓
┌─────────────────────────────────────────────────────────────────┐
│                      APPLICATION LEVEL                          │
│  (How the code consumes configuration)                         │
└─────────────────────────────────────────────────────────────────┘
                                ↓
    ┌───────────────────────────────────────────────┐
    │  src/Config/ApiConfig.php                     │
    │  ─────────────────────────                    │
    │  PSR-4 Config Class that:                     │
    │  • Reads /config/api.php                      │
    │  • Validates values                           │
    │  • Provides type-safe getters                 │
    │  • Offers IDE autocomplete                    │
    │                                               │
    │  $config = ApiConfig::fromFile(               │
    │      __DIR__ . '/../config/api.php'           │
    │  );                                           │
    └───────────────────────────────────────────────┘
                                ↓
    ┌───────────────────────────────────────────────┐
    │  src/Router.php                               │
    │  ─────────────                                │
    │  Uses typed methods instead of arrays:        │
    │                                               │
    │  $config->isAuthEnabled()      // bool        │
    │  $config->getAuthMethod()      // string      │
    │  $config->getRoles()           // array       │
    └───────────────────────────────────────────────┘
```

---

## Detailed Flow Example

### Step 1: User Configures API

**File:** `/config/api.php` (user edits this)

```php
<?php
return [
    'auth_enabled' => true,
    'auth_method' => 'jwt',
    'jwt_secret' => 'my-production-secret-key-12345',
    'jwt_expiration' => 7200,
    'roles' => [
        'admin' => ['tables' => ['*'], 'actions' => ['*']],
        'user' => ['tables' => ['posts', 'comments'], 'actions' => ['list', 'read']],
    ],
    'rate_limit' => [
        'enabled' => true,
        'requests_per_minute' => 100,
    ],
];
```

### Step 2: Router Loads Config via Config Class

**File:** `src/Router.php`

```php
use App\Config\ApiConfig;

public function __construct(Database $db, Authenticator $auth)
{
    // Load user configuration through Config class
    $this->apiConfig = ApiConfig::fromFile(__DIR__ . '/../config/api.php');
    
    // Now use type-safe getters instead of array access
    $this->authEnabled = $this->apiConfig->isAuthEnabled();  // true
    $authMethod = $this->apiConfig->getAuthMethod();          // 'jwt'
    $jwtSecret = $this->apiConfig->getJwtSecret();            // 'my-production-secret-key-12345'
    
    // Pass to subsystems
    $this->rbac = new Rbac(
        $this->apiConfig->getRoles(),      // Type-safe array
        $this->apiConfig->getUserRoles()   // Type-safe array
    );
}
```

### Step 3: Config Class Reads and Validates

**File:** `src/Config/ApiConfig.php`

```php
class ApiConfig
{
    public static function fromFile(string $configFile): self
    {
        // 1. Read the user's config/api.php file
        $config = require $configFile;  // Returns array from user file
        
        // 2. Pass to constructor for validation
        return new self($config);
    }
    
    public function __construct(array $config = [])
    {
        // 3. Validate and set defaults
        $this->authEnabled = $config['auth_enabled'] ?? true;
        $this->authMethod = $config['auth_method'] ?? 'jwt';
        $this->jwtSecret = $config['jwt_secret'] ?? 'your-secret-key-change-this-in-production';
        
        // 4. Store in typed properties
    }
    
    // 5. Provide type-safe getters
    public function isAuthEnabled(): bool
    {
        return $this->authEnabled;
    }
    
    public function getJwtSecret(): string
    {
        return $this->jwtSecret;
    }
}
```

---

## Role Separation

### 👨‍💻 User Responsibility (config/api.php)

```php
// User ONLY edits this file - simple PHP array
return [
    'auth_method' => 'jwt',
    'jwt_secret' => 'change-me-in-production',
    'rate_limit' => ['enabled' => true],
];
```

**User benefits:**
- ✅ Simple PHP array syntax
- ✅ Copy from documentation
- ✅ No OOP knowledge needed
- ✅ Clear comments in file

### 🏗️ Framework Responsibility (src/Config/ApiConfig.php)

```php
// Framework handles:
// - Loading the config file
// - Validating values
// - Providing type-safe access
// - IDE autocomplete
// - Default values

class ApiConfig
{
    public static function fromFile(string $file): self
    {
        return new self(require $file);
    }
}
```

**Developer benefits:**
- ✅ Type safety
- ✅ IDE autocomplete
- ✅ Unit testing
- ✅ Validation logic
- ✅ Documentation

---

## Why Two Files?

### ❌ Bad Alternative: Only Config Class

```php
// Would force users to write OOP code - too complex!
$config = new ApiConfig();
$config->setAuthMethod('jwt');
$config->setJwtSecret('my-secret');
$config->enableAuth();
// Too much code for simple configuration!
```

### ❌ Bad Alternative: Only Array File

```php
// No type safety, no IDE help
$config = require 'config/api.php';
$method = $config['auth_method'] ?? 'jwt';  // Typo risk!
$secret = $config['jwt_secret'] ?? null;     // No validation!
```

### ✅ Good Solution: Both Files Working Together

```php
// User edits simple array in config/api.php
return ['auth_method' => 'jwt'];

// Framework uses Config class for type safety
$config = ApiConfig::fromFile('config/api.php');
$method = $config->getAuthMethod();  // String, validated, autocomplete!
```

---

## File Responsibilities

| File | Purpose | Who Edits | Format |
|------|---------|-----------|--------|
| **config/api.php** | User configuration | 👨‍💻 End user | PHP array |
| **src/Config/ApiConfig.php** | Type-safe wrapper | 👨‍💻 Framework dev | OOP class |
| **src/Router.php** | Uses configuration | 👨‍💻 Framework dev | OOP code |

---

## Complete Flow Example

### 1. User Configures (config/api.php)

```php
<?php
return [
    'auth_enabled' => true,
    'auth_method' => 'jwt',
    'jwt_secret' => 'super-secret-key',
];
```

### 2. Framework Loads (src/Router.php)

```php
$apiConfig = ApiConfig::fromFile(__DIR__ . '/../config/api.php');
```

### 3. Config Class Processes (src/Config/ApiConfig.php)

```php
public static function fromFile(string $configFile): self
{
    $userArray = require $configFile;  // ['auth_enabled' => true, ...]
    return new self($userArray);        // Converts to object
}
```

### 4. Framework Uses Type-Safe Methods

```php
// OLD WAY (array access) ❌
$enabled = $apiConfig['auth_enabled'] ?? true;

// NEW WAY (typed methods) ✅
$enabled = $apiConfig->isAuthEnabled();  // bool guaranteed
```

---

## Benefits of This Architecture

### For End Users

1. **Simple Configuration**
   - Edit plain PHP arrays
   - Copy/paste from docs
   - No OOP knowledge needed

2. **Clear Documentation**
   - Comments in config files
   - Example values
   - Validation hints

### For Developers

1. **Type Safety**
   ```php
   $enabled = $config->isAuthEnabled();  // Always bool
   $method = $config->getAuthMethod();   // Always string
   ```

2. **IDE Support**
   - Autocomplete for all methods
   - PHPDoc hints
   - Jump to definition

3. **Testability**
   ```php
   $config = new ApiConfig(['auth_enabled' => false]);
   assert($config->isAuthEnabled() === false);
   ```

4. **Validation**
   ```php
   public function setAuthMethod(string $method): void
   {
       if (!in_array($method, ['apikey', 'jwt', 'basic'])) {
           throw new InvalidArgumentException("Invalid auth method");
       }
       $this->authMethod = $method;
   }
   ```

---

## Comparison: Before vs After

### Before (Only Arrays)

```php
// config/api.php
return ['auth_method' => 'jwt'];

// src/Router.php
$config = require __DIR__ . '/../config/api.php';
$method = $config['auth_method'];  // ❌ No type check
$secret = $config['jwt_secert'];   // ❌ Typo! Runtime error!
```

### After (Array + Config Class)

```php
// config/api.php (same - user doesn't change)
return ['auth_method' => 'jwt'];

// src/Router.php (type-safe)
$config = ApiConfig::fromFile(__DIR__ . '/../config/api.php');
$method = $config->getAuthMethod();  // ✅ String guaranteed
$secret = $config->getJwtSecret();   // ✅ IDE autocomplete, no typo!
```

---

## Summary

```
config/api.php (USER FILE)
    ↓ (read by)
src/Config/ApiConfig.php (WRAPPER CLASS)
    ↓ (used by)
src/Router.php (FRAMEWORK CODE)
```

**Key Points:**

1. **Users still edit config/api.php** - nothing changed for them
2. **Config class reads that file** - via `fromFile()` method
3. **Framework uses Config class** - for type safety and validation
4. **Backward compatible** - old code still works with `toArray()`
5. **Best of both worlds** - simple config + type safety

**This is the industry standard pattern used by Laravel, Symfony, and modern PHP frameworks!**
