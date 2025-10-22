# User Management Strategies

This guide explains different approaches to manage API users in production.

---

## 📋 Table of Contents

1. [Current Method (Config File)](#1-current-method-config-file)
2. [Database Users (Recommended)](#2-database-users-recommended)
3. [API Key Management](#3-api-key-management)
4. [User Registration Endpoint](#4-user-registration-endpoint)
5. [Admin Panel for User Management](#5-admin-panel-for-user-management)
6. [External Auth (OAuth, LDAP)](#6-external-auth-oauth-ldap)

---

## 1. Current Method (Config File)

### How It Works

Users defined in `config/api.php`:

```php
'basic_users' => [
    'admin' => 'secret',
    'user' => 'userpass'
],
```

### ❌ Limitations

- Manual file editing required
- Requires server access
- No self-registration
- Plain text passwords (security risk)
- Requires code redeployment

### ✅ When To Use

- **Development only**
- Internal APIs with 2-3 known users
- Testing environments

---

## 2. Database Users (Recommended)

### Overview

Store users in a MySQL table with hashed passwords, roles, and metadata.

### Database Schema

```sql
CREATE TABLE api_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'readonly',
    api_key VARCHAR(64) UNIQUE,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_api_key (api_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: API key usage tracking
CREATE TABLE api_key_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    api_key VARCHAR(64) NOT NULL,
    endpoint VARCHAR(255),
    ip_address VARCHAR(45),
    request_count INT DEFAULT 0,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES api_users(id) ON DELETE CASCADE,
    INDEX idx_api_key (api_key),
    INDEX idx_last_used (last_used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Implementation: Database Authenticator

Create a new class `src/DatabaseAuthenticator.php`:

```php
<?php
namespace App;

/**
 * Database-based Authentication
 * 
 * Authenticates users from database table instead of config file.
 * Supports password hashing, API keys, and user management.
 */
class DatabaseAuthenticator extends Authenticator
{
    private \PDO $pdo;
    
    public function __construct(array $config, \PDO $pdo)
    {
        parent::__construct($config);
        $this->pdo = $pdo;
    }
    
    /**
     * Authenticate user from database
     */
    public function authenticate(): bool
    {
        $method = $this->config['auth_method'] ?? 'basic';
        
        switch ($method) {
            case 'basic':
                return $this->authenticateBasic();
            case 'apikey':
                return $this->authenticateApiKey();
            case 'jwt':
                return $this->authenticateJwt();
            default:
                return false;
        }
    }
    
    /**
     * Authenticate using Basic Auth with database lookup
     */
    private function authenticateBasic(): bool
    {
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
            return false;
        }
        
        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];
        
        // Lookup user in database
        $stmt = $this->pdo->prepare(
            "SELECT id, username, email, password_hash, role, active 
             FROM api_users 
             WHERE username = :username AND active = 1"
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        // Verify password hash
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Set current user
        $this->currentUser = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        
        return true;
    }
    
    /**
     * Authenticate using API key from database
     */
    private function authenticateApiKey(): bool
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] 
                  ?? $_GET['api_key'] 
                  ?? $_POST['api_key'] 
                  ?? null;
        
        if (!$apiKey) {
            return false;
        }
        
        // Lookup API key in database
        $stmt = $this->pdo->prepare(
            "SELECT u.id, u.username, u.email, u.role, u.active
             FROM api_users u
             WHERE u.api_key = :api_key AND u.active = 1"
        );
        $stmt->execute(['api_key' => $apiKey]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        // Track API key usage
        $this->trackApiKeyUsage($user['id'], $apiKey);
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Set current user
        $this->currentUser = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        
        return true;
    }
    
    /**
     * Get user role from database user record
     */
    public function getCurrentUserRole(): ?string
    {
        return $this->currentUser['role'] ?? null;
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE api_users SET last_login = NOW() WHERE id = :id"
        );
        $stmt->execute(['id' => $userId]);
    }
    
    /**
     * Track API key usage for analytics
     */
    private function trackApiKeyUsage(int $userId, string $apiKey): void
    {
        $endpoint = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO api_key_usage (user_id, api_key, endpoint, ip_address, request_count)
             VALUES (:user_id, :api_key, :endpoint, :ip, 1)
             ON DUPLICATE KEY UPDATE 
                request_count = request_count + 1,
                last_used = NOW()"
        );
        
        $stmt->execute([
            'user_id' => $userId,
            'api_key' => $apiKey,
            'endpoint' => $endpoint,
            'ip' => $ip
        ]);
    }
}
```

### Using Database Authenticator

Update `public/index.php`:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Router;
use App\DatabaseAuthenticator; // Use database auth

// Load configs
$dbConfig = require __DIR__ . '/../config/db.php';
$apiConfig = require __DIR__ . '/../config/api.php';

// Bootstrap
$db = new Database($dbConfig);
$pdo = $db->getPdo();

// Use DatabaseAuthenticator instead of Authenticator
$auth = new DatabaseAuthenticator($apiConfig, $pdo);
$router = new Router($db, $auth);

// Dispatch
$router->route($_GET);
```

### Benefits

✅ **Scalable** - Unlimited users without code changes  
✅ **Secure** - Passwords hashed with bcrypt/argon2  
✅ **Trackable** - Login history and API usage  
✅ **Manageable** - CRUD operations on users  
✅ **Professional** - Industry standard approach  

---

## 3. API Key Management

### Generate Unique API Keys

```php
<?php
// Helper script: scripts/generate_api_key.php

require_once __DIR__ . '/../vendor/autoload.php';

function generateApiKey(): string
{
    return bin2hex(random_bytes(32)); // 64-character hex string
}

// Usage
$apiKey = generateApiKey();
echo "New API Key: $apiKey\n";
```

### API Key Best Practices

1. **Never reuse keys** - Each user gets unique key
2. **Store hashed** - Hash API keys like passwords (optional but recommended)
3. **Expiration** - Add expiry dates to keys
4. **Rotation** - Allow users to regenerate keys
5. **Rate limiting per key** - Track usage per API key

### Enhanced Schema with Expiration

```sql
ALTER TABLE api_users ADD COLUMN api_key_expires_at TIMESTAMP NULL;

-- Query to check if API key is valid
SELECT * FROM api_users 
WHERE api_key = :key 
  AND active = 1 
  AND (api_key_expires_at IS NULL OR api_key_expires_at > NOW());
```

---

## 4. User Registration Endpoint

### Self-Service Registration

Allow users to register themselves via API endpoint.

Create `src/UserManager.php`:

```php
<?php
namespace App;

/**
 * User Management
 * 
 * Handles user registration, updates, and API key generation
 */
class UserManager
{
    private \PDO $pdo;
    
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Register new user
     * 
     * @return array ['success' => bool, 'user_id' => int, 'api_key' => string, 'error' => string]
     */
    public function registerUser(string $username, string $email, string $password, string $role = 'readonly'): array
    {
        // Validate input
        if (strlen($username) < 3) {
            return ['success' => false, 'error' => 'Username must be at least 3 characters'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }
        
        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters'];
        }
        
        // Check if username or email already exists
        $stmt = $this->pdo->prepare(
            "SELECT id FROM api_users WHERE username = :username OR email = :email"
        );
        $stmt->execute(['username' => $username, 'email' => $email]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Username or email already exists'];
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        
        // Generate API key
        $apiKey = bin2hex(random_bytes(32));
        
        // Insert user
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO api_users (username, email, password_hash, role, api_key, active)
                 VALUES (:username, :email, :password_hash, :role, :api_key, 1)"
            );
            
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $role,
                'api_key' => $apiKey
            ]);
            
            $userId = (int)$this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'user_id' => $userId,
                'api_key' => $apiKey,
                'message' => 'User registered successfully'
            ];
            
        } catch (\PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Regenerate API key for user
     */
    public function regenerateApiKey(int $userId): array
    {
        $newKey = bin2hex(random_bytes(32));
        
        $stmt = $this->pdo->prepare(
            "UPDATE api_users SET api_key = :api_key, updated_at = NOW() WHERE id = :id"
        );
        
        $stmt->execute([
            'api_key' => $newKey,
            'id' => $userId
        ]);
        
        return [
            'success' => true,
            'api_key' => $newKey,
            'message' => 'API key regenerated successfully'
        ];
    }
    
    /**
     * Deactivate user
     */
    public function deactivateUser(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE api_users SET active = 0, updated_at = NOW() WHERE id = :id"
        );
        
        return $stmt->execute(['id' => $userId]);
    }
    
    /**
     * Update user role
     */
    public function updateUserRole(int $userId, string $role): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE api_users SET role = :role, updated_at = NOW() WHERE id = :id"
        );
        
        return $stmt->execute(['role' => $role, 'id' => $userId]);
    }
    
    /**
     * Get user by ID
     */
    public function getUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, username, email, role, active, created_at, last_login 
             FROM api_users WHERE id = :id"
        );
        
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $user ?: null;
    }
    
    /**
     * List all users (admin only)
     */
    public function listUsers(int $page = 1, int $pageSize = 20): array
    {
        $offset = ($page - 1) * $pageSize;
        
        $stmt = $this->pdo->prepare(
            "SELECT id, username, email, role, active, created_at, last_login 
             FROM api_users 
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        
        $stmt->bindValue(':limit', $pageSize, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
```

### Registration Endpoint

Add to Router or create separate `register.php`:

```php
<?php
// public/register.php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\UserManager;
use App\Response;

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

// Load database
$dbConfig = require __DIR__ . '/../config/db.php';
$db = new Database($dbConfig);
$userManager = new UserManager($db->getPdo());

// Register user
$result = $userManager->registerUser($username, $email, $password, 'readonly');

if ($result['success']) {
    http_response_code(201);
    echo json_encode([
        'message' => 'User registered successfully',
        'user_id' => $result['user_id'],
        'api_key' => $result['api_key'],
        'instructions' => 'Save your API key. Use it in X-API-Key header or ?api_key= parameter.'
    ]);
} else {
    http_response_code(400);
    echo json_encode(['error' => $result['error']]);
}
```

### Usage

```bash
# Register new user
curl -X POST http://localhost/PHP-CRUD-API-Generator/public/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "newuser",
    "email": "user@example.com",
    "password": "SecurePass123!"
  }'

# Response:
{
  "message": "User registered successfully",
  "user_id": 42,
  "api_key": "a1b2c3d4e5f6...",
  "instructions": "Save your API key. Use it in X-API-Key header or ?api_key= parameter."
}
```

---

## 5. Admin Panel for User Management

### Simple HTML Admin Panel

Create `public/admin.html`:

```html
<!DOCTYPE html>
<html>
<head>
    <title>API User Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        button { padding: 5px 10px; cursor: pointer; }
        .form-group { margin: 10px 0; }
        input { padding: 5px; margin: 5px; }
    </style>
</head>
<body>
    <h1>API User Management</h1>
    
    <h2>Register New User</h2>
    <form id="registerForm">
        <div class="form-group">
            <input type="text" id="username" placeholder="Username" required>
            <input type="email" id="email" placeholder="Email" required>
            <input type="password" id="password" placeholder="Password" required>
            <select id="role">
                <option value="readonly">Readonly</option>
                <option value="editor">Editor</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit">Register</button>
        </div>
    </form>
    
    <h2>Existing Users</h2>
    <table id="usersTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Active</th>
                <th>Last Login</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    
    <script>
        // Load users
        async function loadUsers() {
            // Implement API call to list users
            // For now, placeholder
        }
        
        // Register user
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const data = {
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                role: document.getElementById('role').value
            };
            
            const response = await fetch('register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (response.ok) {
                alert(`User registered!\nAPI Key: ${result.api_key}\n\nSave this key!`);
                loadUsers();
                e.target.reset();
            } else {
                alert('Error: ' + result.error);
            }
        });
        
        // Load on page load
        loadUsers();
    </script>
</body>
</html>
```

---

## 6. External Auth (OAuth, LDAP)

### OAuth 2.0 Integration

For enterprise applications, integrate with existing identity providers:

- **Google OAuth**
- **Microsoft Azure AD**
- **GitHub OAuth**
- **Okta**

### LDAP/Active Directory

For corporate environments:

```php
// Example LDAP authentication
function authenticateLdap($username, $password): bool
{
    $ldapConn = ldap_connect('ldap://your-ldap-server.com');
    
    if (!$ldapConn) {
        return false;
    }
    
    ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
    
    $bind = @ldap_bind($ldapConn, "cn=$username,dc=company,dc=com", $password);
    
    ldap_close($ldapConn);
    
    return $bind !== false;
}
```

---

## Summary Comparison

| Method | Security | Scalability | Ease of Use | Best For |
|--------|----------|-------------|-------------|----------|
| **Config File** | ⭐ Low | ⭐ Poor | ⭐⭐⭐ Easy | Dev/Testing |
| **Database Users** | ⭐⭐⭐⭐ High | ⭐⭐⭐⭐ Excellent | ⭐⭐⭐ Good | Production APIs |
| **API Keys** | ⭐⭐⭐⭐ High | ⭐⭐⭐⭐ Excellent | ⭐⭐⭐⭐ Very Good | Public APIs |
| **Self Registration** | ⭐⭐⭐ Medium | ⭐⭐⭐⭐ Excellent | ⭐⭐⭐⭐ Excellent | SaaS Products |
| **OAuth/LDAP** | ⭐⭐⭐⭐⭐ Very High | ⭐⭐⭐⭐ Excellent | ⭐⭐ Complex | Enterprise |

---

## Recommended Approach for Production

**Phase 1: Immediate (Database Users)**
1. Create `api_users` table
2. Implement `DatabaseAuthenticator`
3. Use API keys for authentication
4. Manual user creation via SQL/script

**Phase 2: Self-Service (Registration)**
1. Add `register.php` endpoint
2. Email verification (optional)
3. User dashboard for API key management
4. Rate limiting per user

**Phase 3: Enterprise (If Needed)**
1. OAuth integration
2. LDAP/Active Directory
3. SSO (Single Sign-On)
4. Advanced user management

---

## Quick Start: Database Users

```bash
# 1. Create database table
mysql -u root -p mydb < scripts/create_users_table.sql

# 2. Create first user
php scripts/create_user.php admin admin@example.com SecurePass123! admin

# 3. Update index.php to use DatabaseAuthenticator

# 4. Test
curl -H "X-API-Key: YOUR_API_KEY" http://localhost/api.php?action=tables
```

**Done!** You now have a scalable, secure user management system.
