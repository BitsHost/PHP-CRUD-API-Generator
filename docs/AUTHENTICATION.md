# Authentication Guide

Complete guide to authentication methods in PHP-CRUD-API-Generator.

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication Methods](#authentication-methods)
3. [Configuration](#configuration)
4. [API Key Authentication](#api-key-authentication)
5. [Basic Authentication](#basic-authentication)
6. [JWT Authentication](#jwt-authentication)
7. [Role-Based Access Control (RBAC)](#role-based-access-control-rbac)
8. [Security Best Practices](#security-best-practices)
9. [Troubleshooting](#troubleshooting)

---

## Overview

The API supports **4 authentication methods**:

| Method | Best For | Performance | Security | Complexity |
|--------|----------|-------------|----------|------------|
| **API Key** | Server-to-server, webhooks | ⚡ Fast | 🔒 Medium | ⭐ Simple |
| **Basic Auth** | Development, internal tools | ⚡ Fast | 🔒 Medium | ⭐ Simple |
| **JWT** | Web/mobile apps, high traffic | ⚡⚡⚡ Very Fast | 🔒🔒 High | ⭐⭐ Medium |
| **OAuth** | Third-party integrations | ⚡ Fast | 🔒🔒🔒 Very High | ⭐⭐⭐ Complex |

---

## Authentication Methods

### Method Names (IMPORTANT!)

**Use these exact values in `config/api.php`:**

```php
'auth_method' => 'apikey',  // ✅ Correct (not 'api_key')
'auth_method' => 'basic',   // ✅ Correct
'auth_method' => 'jwt',     // ✅ Correct
'auth_method' => 'oauth',   // ✅ Correct (placeholder)
```

❌ **Common mistakes:**
- `'api_key'` (with underscore) - Won't work!
- `'API_KEY'` (uppercase) - Won't work!
- `'bearer'` - Use `'jwt'` instead

---

## Configuration

### Location

Edit: **`config/api.php`**

### Basic Setup

```php
<?php
return [
    // Enable/disable authentication globally
    'auth_enabled' => true,
    
    // Choose ONE authentication method
    'auth_method' => 'jwt',  // Options: 'apikey', 'basic', 'jwt', 'oauth'
    
    // ... method-specific configs below
];
```

---

## API Key Authentication

### When to Use

✅ **Good for:**
- Server-to-server communication
- Webhooks and callbacks
- Internal microservices
- Automated scripts/cron jobs
- Testing and development

❌ **Avoid for:**
- Public-facing web apps (keys can be exposed in browser)
- Mobile apps (keys in source code)
- Multi-user systems (one key = all same permissions)

---

### Configuration

```php
'auth_enabled' => true,
'auth_method' => 'apikey',

// List of valid API keys
'api_keys' => [
    'changeme123',
    'production-key-xyz789',
    'webhook-secret-abc456',
],

// Default role for ALL API key users
'api_key_role' => 'admin',  // Options: 'admin', 'editor', 'readonly', custom
```

---

### Usage Examples

#### Method 1: Header (Recommended)

**Postman:**
```
GET http://localhost/api.php?action=tables

Headers:
  X-API-Key: changeme123

Steps:
1. Create new request (GET)
2. URL: http://localhost/api.php?action=tables
3. Go to "Headers" tab
4. Add header:
   - Key: X-API-Key
   - Value: changeme123
5. Click "Send"
```

**HTTPie:**
```bash
http GET http://localhost/api.php action==tables X-API-Key:changeme123

# Or with explicit header syntax:
http http://localhost/api.php action==tables "X-API-Key: changeme123"
```

**cURL:**
```bash
curl -H "X-API-Key: changeme123" \
  http://localhost/api.php?action=tables
```

**JavaScript (Fetch):**
```javascript
fetch('http://localhost/api.php?action=tables', {
  headers: {
    'X-API-Key': 'changeme123'
  }
})
.then(res => res.json())
.then(data => console.log(data));
```

**PHP:**
```php
$ch = curl_init('http://localhost/api.php?action=tables');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: changeme123'
]);
$response = curl_exec($ch);
```

**Python (Requests):**
```python
import requests

response = requests.get(
    'http://localhost/api.php?action=tables',
    headers={'X-API-Key': 'changeme123'}
)
print(response.json())
```

---

#### Method 2: Query Parameter

**URL:**
```
http://localhost/api.php?action=tables&api_key=changeme123
```

⚠️ **Warning:** Query parameters are logged in server access logs. Use headers for production.

**Postman:**
```
GET http://localhost/api.php?action=tables&api_key=changeme123

Steps:
1. Create new request (GET)
2. URL: http://localhost/api.php
3. Go to "Params" tab
4. Add parameters:
   - action: tables
   - api_key: changeme123
5. Click "Send"
```

**HTTPie:**
```bash
http GET http://localhost/api.php action==tables api_key==changeme123
```

**cURL:**
```bash
curl "http://localhost/api.php?action=tables&api_key=changeme123"
```

**JavaScript:**
```javascript
fetch('http://localhost/api.php?action=tables&api_key=changeme123')
  .then(res => res.json());
```

---

### Security Notes

🔒 **Best Practices:**
1. **Rotate keys regularly** (every 90 days)
2. **Use long, random keys** (32+ characters)
3. **Generate keys securely:**
   ```php
   bin2hex(random_bytes(32))  // 64-char hex string
   ```
4. **One key per service** (easier to revoke)
5. **Use HTTPS only** (keys sent in plaintext)

---

## Basic Authentication

### When to Use

✅ **Good for:**
- Development and testing
- Internal admin tools
- Legacy system integration
- Small teams (< 10 users)

❌ **Avoid for:**
- High-traffic APIs (queries database on every request)
- Scalable systems (use JWT instead)
- Public APIs (username/password less secure than tokens)

---

### Configuration

```php
'auth_enabled' => true,
'auth_method' => 'basic',

// Option 1: Config file users (simple, not recommended for production)
'basic_users' => [
    'admin' => 'secret',      // Username => Password
    'john'  => 'password123',
    'alice' => 'alicepass',
],

// Option 2: Database users (recommended for production)
'use_database_auth' => true,  // Enable database lookup

// Map config users to roles
'user_roles' => [
    'admin' => 'admin',
    'john'  => 'readonly',
    'alice' => 'editor',
],
```

---

### Usage Examples

#### Method 1: Authorization Header

**Postman:**
```
GET http://localhost/api.php?action=tables

Authorization:
  Type: Basic Auth
  Username: admin
  Password: secret

Steps:
1. Create new request (GET)
2. URL: http://localhost/api.php?action=tables
3. Go to "Authorization" tab
4. Type: Select "Basic Auth" from dropdown
5. Username: admin
6. Password: secret
7. Click "Send"

Postman automatically encodes credentials as Base64 and adds header:
Authorization: Basic YWRtaW46c2VjcmV0
```

**HTTPie:**
```bash
# Method 1: Simple syntax (HTTPie handles Basic Auth automatically)
http -a admin:secret GET http://localhost/api.php action==tables

# Method 2: Explicit header (manual Base64 encoding)
http GET http://localhost/api.php action==tables "Authorization: Basic YWRtaW46c2VjcmV0"
```

**cURL:**
```bash
curl -u admin:secret \
  http://localhost/api.php?action=tables
```

**JavaScript (Fetch):**
```javascript
const credentials = btoa('admin:secret'); // Base64 encode

fetch('http://localhost/api.php?action=tables', {
  headers: {
    'Authorization': 'Basic ' + credentials
  }
})
.then(res => res.json());
```

**PHP:**
```php
$ch = curl_init('http://localhost/api.php?action=tables');
curl_setopt($ch, CURLOPT_USERPWD, 'admin:secret');
$response = curl_exec($ch);
```

**Python:**
```python
import requests
from requests.auth import HTTPBasicAuth

response = requests.get(
    'http://localhost/api.php?action=tables',
    auth=HTTPBasicAuth('admin', 'secret')
)
```

---

#### Method 2: Browser Prompt

Simply visit the URL in a browser:
```
http://localhost/api.php?action=tables
```

Browser will prompt for username and password automatically.

---

### Database Users

**Create users via CLI:**
```bash
php scripts/create_user.php john john@example.com SecurePass123! readonly
```

**How it works:**
1. User credentials stored in `api_users` table (password hashed with Argon2ID)
2. Basic Auth first checks database, then falls back to config file
3. Role comes from database `api_users.role` column

**Authentication Flow:**
```
Request with Basic Auth
  ↓
Check database (if use_database_auth = true)
  ↓ (if not found)
Check config file basic_users
  ↓ (if not found)
Return 401 Unauthorized
```

---

### Performance Note

⚠️ **Database Query on Every Request:**

With Basic Auth + database users:
- 1000 users × 10 requests/minute = **10,000 database queries/minute**

**Solution:** Use JWT instead (99.8% fewer queries)

---

## JWT Authentication

### When to Use

✅ **Best for:**
- High-traffic APIs
- Web and mobile apps
- Scalable microservices
- Multi-user systems
- Public-facing APIs

✅ **Advantages:**
- **Performance:** No database query per request (stateless)
- **Scalability:** Works with load balancers (no shared sessions)
- **Security:** Signed tokens, expiration, role claims
- **User experience:** Login once, use for hours

---

### Configuration

```php
'auth_enabled' => true,
'auth_method' => 'jwt',

// JWT signing secret (CHANGE THIS IN PRODUCTION!)
'jwt_secret' => 'YourSuperSecretKeyChangeMe',

// Token expiration time (seconds)
'jwt_expiration' => 3600,  // 1 hour

// Optional: JWT issuer and audience claims
'jwt_issuer' => 'api.yourdomain.com',
'jwt_audience' => 'yourdomain.com',

// Enable database authentication for login
'use_database_auth' => true,
```

⚠️ **CRITICAL:** Change `jwt_secret` in production to a long random string (64+ characters)

```php
// Generate secure secret:
bin2hex(random_bytes(32))
```

---

### Usage - Login Flow

#### Step 1: Login (Get Token)

The API accepts login credentials in **3 different formats**:

| Format | Content-Type | Is JSON? | Use Case |
|--------|-------------|----------|----------|
| **JSON** | `application/json` | ✅ Yes | Modern APIs, JavaScript apps |
| **Form Data** | `application/x-www-form-urlencoded` | ❌ No | Traditional HTML forms |
| **Multipart** | `multipart/form-data` | ❌ No | File uploads |

**Important:** Only Option 1 (JSON) uses actual JSON format!

---

##### Option 1: JSON Body (Recommended for Modern APIs)

**cURL:**
```bash
curl -X POST "http://localhost/api.php?action=login" \
  -H "Content-Type: application/json" \
  -d '{"username":"john","password":"SecurePass123!"}'
```

**Postman:**
```
POST http://localhost/api.php?action=login

Headers:
  Content-Type: application/json

Body → raw → JSON:
{
  "username": "john",
  "password": "SecurePass123!"
}
```

**HTTPie:**
```bash
http POST http://localhost/api.php action==login username=john password=SecurePass123!
```

---

##### Option 2: Form Data (URL-encoded, NOT JSON)

**Format:** `application/x-www-form-urlencoded` (traditional HTML form format)

**cURL:**
```bash
curl -X POST \
  -d "username=john&password=SecurePass123!" \
  http://localhost/api.php?action=login
```

**Postman:**
```
POST http://localhost/api.php?action=login

Body → x-www-form-urlencoded:
  username: john
  password: SecurePass123!

(This is NOT JSON - it's the same format as URL query parameters)
```

---

##### Option 3: Multipart Form Data (for file uploads, NOT JSON)

**Format:** `multipart/form-data` (used when uploading files)

**cURL:**
```bash
curl -X POST \
  -F "username=john" \
  -F "password=SecurePass123!" \
  http://localhost/api.php?action=login
```

**Postman:**
```
POST http://localhost/api.php?action=login

Body → form-data:
  username: john
  password: SecurePass123!

(This is NOT JSON - it's a multipart format for file uploads)
```

---

**Response (Success):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3MzQ4...",
  "expires_at": 1699568400,
  "user": "john",
  "role": "readonly"
}
```

**Response (Failure):**
```json
{
  "error": "Invalid credentials"
}
```

---

#### Step 2: Use Token for API Requests

**Request:**
```bash
GET /api.php?action=tables
Authorization: Bearer eyJ0eXAiOiJKV1Qi...
```

**cURL:**
```bash
TOKEN="eyJ0eXAiOiJKV1Qi..."

curl -H "Authorization: Bearer $TOKEN" \
  http://localhost/api.php?action=tables
```

**JavaScript (Fetch):**
```javascript
// After login, save token
const loginResponse = await fetch('/api.php?action=login', {
  method: 'POST',
  body: new URLSearchParams({
    username: 'john',
    password: 'SecurePass123!'
  })
});
const { token } = await loginResponse.json();

// Use token for subsequent requests
const dataResponse = await fetch('/api.php?action=tables', {
  headers: {
    'Authorization': 'Bearer ' + token
  }
});
const data = await dataResponse.json();
```

**React Example:**
```jsx
import { useState, useEffect } from 'react';

function App() {
  const [token, setToken] = useState(localStorage.getItem('jwt_token'));
  const [tables, setTables] = useState([]);

  const login = async (username, password) => {
    const response = await fetch('/api.php?action=login', {
      method: 'POST',
      body: new URLSearchParams({ username, password })
    });
    const data = await response.json();
    
    if (data.success) {
      setToken(data.token);
      localStorage.setItem('jwt_token', data.token);
    }
  };

  const fetchTables = async () => {
    const response = await fetch('/api.php?action=tables', {
      headers: { 'Authorization': 'Bearer ' + token }
    });
    const data = await response.json();
    setTables(data.tables);
  };

  return (
    <div>
      {!token ? (
        <LoginForm onLogin={login} />
      ) : (
        <Dashboard tables={tables} onLoad={fetchTables} />
      )}
    </div>
  );
}
```

---

### Token Structure

JWT tokens contain 3 parts (separated by `.`):

```
eyJ0eXAiOiJKV1QiLCJhbGc...  ← Header (algorithm)
.
eyJpYXQiOjE3MzQ4MzIwMDA...  ← Payload (user, role, expiration)
.
9Xw7rZ8kL5mN3pQ6tY1uV...  ← Signature (prevents tampering)
```

**Payload (decoded):**
```json
{
  "iat": 1734832000,           // Issued at (timestamp)
  "exp": 1734835600,           // Expires at (timestamp)
  "iss": "api.yourdomain.com", // Issuer
  "aud": "yourdomain.com",     // Audience
  "sub": "john",               // Subject (username)
  "role": "readonly"           // Custom: user role
}
```

---

### Performance Benefits

**Before JWT (Basic Auth with 1000 users):**
```
10 requests/min × 1000 users = 10,000 auth queries/minute
10,000 queries/min × 60 min = 600,000 queries/hour
```

**After JWT:**
```
1 login/hour × 1000 users = 1,000 auth queries/hour (99.8% reduction!)
```

**Why so fast?**
- Token validated in-memory (no database)
- Signature verification takes microseconds
- Role embedded in token (no lookup needed)

---

### Security Features

✅ **Signed Tokens:**
- Signature prevents tampering
- If token modified, validation fails

✅ **Expiration:**
- Tokens auto-expire (default: 1 hour)
- Reduces impact of stolen tokens

✅ **Role Claims:**
- Role embedded in token
- RBAC enforced without database query

✅ **Stateless:**
- No server-side session storage
- Scales horizontally (load balancers)

---

### Token Storage (Client-Side)

**Option 1: localStorage (Simple)**
```javascript
// After login
localStorage.setItem('jwt_token', token);

// For requests
const token = localStorage.getItem('jwt_token');
fetch('/api.php?action=tables', {
  headers: { 'Authorization': 'Bearer ' + token }
});
```

⚠️ **Vulnerability:** XSS attacks can steal tokens

---

**Option 2: httpOnly Cookie (More Secure)**

Modify login endpoint to set cookie:
```php
setcookie('jwt_token', $token, [
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => true,      // HTTPS only
    'httponly' => true,    // JavaScript can't access
    'samesite' => 'Strict' // CSRF protection
]);
```

Browser automatically sends cookie with requests.

---

**Option 3: Memory (Most Secure)**

Store token in JavaScript variable (lost on page refresh):
```javascript
let token = null;

// After login
token = loginResponse.token;

// User must re-login on page refresh
```

---

### Refresh Tokens (Optional)

For sessions longer than token expiration:

1. **Login:** Get access token (1 hour) + refresh token (30 days)
2. **Access Expired:** Use refresh token to get new access token
3. **Refresh Expired:** User must re-login

**Implementation:** (Future enhancement)

---

## Role-Based Access Control (RBAC)

### Overview

RBAC controls which tables and actions each role can access.

**Defined in:** `config/api.php`

---

### Role Configuration

```php
'roles' => [
    // Admin: Full access to everything
    'admin' => [
        '*' => ['list', 'read', 'create', 'update', 'delete']
    ],
    
    // Read-only: Can view data but not modify
    'readonly' => [
        '*' => ['list', 'read'],
        // Explicitly deny system tables
        'api_users' => [],           // Empty array = NO ACCESS
        'api_key_usage' => [],
    ],
    
    // Editor: Can modify data but not see system tables
    'editor' => [
        '*' => ['list', 'read', 'create', 'update', 'delete'],
        'api_users' => [],           // Deny access
        'api_key_usage' => [],
    ],
    
    // Custom: Users manager (specific tables only)
    'users_manager' => [
        'users' => ['list', 'read', 'create', 'update'],
        'orders' => ['list', 'read'],
        // All other tables: no access
    ],
],
```

---

### Permission Actions

| Action | Description | Example |
|--------|-------------|---------|
| `list` | View list of records | `GET /api.php?table=users&action=list` |
| `read` | View single record | `GET /api.php?table=users&action=read&id=1` |
| `create` | Insert new record | `POST /api.php?table=users&action=create` |
| `update` | Modify existing record | `PUT /api.php?table=users&action=update&id=1` |
| `delete` | Remove record | `DELETE /api.php?table=users&action=delete&id=1` |

---

### Explicit DENY

**Empty array blocks all access:**

```php
'readonly' => [
    '*' => ['list', 'read'],        // Can read all tables...
    'api_users' => [],              // ...EXCEPT this one (denied)
]
```

**Specific table permissions override wildcards:**

```php
'users_manager' => [
    'users' => ['list', 'read', 'create', 'update'],
    // All other tables: no access (no wildcard = deny by default)
]
```

---

### Role Assignment

#### API Key Method

All API key users get the same role:

```php
'api_key_role' => 'admin',  // All API keys = admin role
```

---

#### Basic Auth Method

**Config file users:**
```php
'basic_users' => [
    'admin' => 'secret',
],
'user_roles' => [
    'admin' => 'admin',  // Username => Role
],
```

**Database users:**
```sql
-- Role stored in database
SELECT username, role FROM api_users WHERE username = 'john';
-- john, readonly
```

---

#### JWT Method

Role embedded in token during login:

```php
// Login endpoint creates token with role claim
$token = createJwt([
    'sub' => 'john',
    'role' => 'readonly'  // ← Role from database
]);
```

Extracted during request validation:
```php
$decoded = JWT::decode($token, ...);
$role = $decoded->role;  // No database query!
```

---

### Testing RBAC

**Test 1: Admin can access system tables**
```bash
curl -H "X-API-Key: changeme123" \
  http://localhost/api.php?table=api_users&action=list

# Expected: 200 OK with user list
```

**Test 2: Readonly blocked from system tables**
```bash
curl -u john:password123 \
  http://localhost/api.php?table=api_users&action=list

# Expected: 403 Forbidden
```

**Test 3: Readonly can view regular tables**
```bash
curl -u john:password123 \
  http://localhost/api.php?table=products&action=list

# Expected: 200 OK with product list
```

**Test 4: Editor blocked from creating users**
```bash
curl -X POST -u alice:alicepass \
  -d "username=hacker&role=admin" \
  http://localhost/api.php?table=api_users&action=create

# Expected: 403 Forbidden
```

---

## Security Best Practices

### 1. Always Use HTTPS in Production

❌ **HTTP (Insecure):**
```
http://api.example.com/api.php
```
- Credentials sent in plaintext
- Tokens can be intercepted
- Man-in-the-middle attacks

✅ **HTTPS (Secure):**
```
https://api.example.com/api.php
```

---

### 2. Strong Secrets

**JWT Secret:**
```php
// ❌ Weak
'jwt_secret' => 'secret123',

// ✅ Strong (64+ characters, random)
'jwt_secret' => 'a7f92c8e4b6d1f3a9e8c7b5d2f1a6e9b8c7d5e4f3a2b1c0d9e8f7a6b5c4d3e2f1',
```

**Generate:**
```bash
php -r "echo bin2hex(random_bytes(32));"
```

---

### 3. API Key Rotation

**Rotate keys every 90 days:**

```php
'api_keys' => [
    'current-key-xyz789',      // Active
    'previous-key-abc456',     // Grace period (7 days)
    // 'old-key-def123',       // Removed after grace period
],
```

---

### 4. Rate Limiting

Prevent brute force attacks:

```php
'rate_limit' => [
    'enabled' => true,
    'max_requests' => 100,     // 100 requests
    'window_seconds' => 60,    // Per minute
],
```

---

### 5. Monitor Authentication Failures

```php
'monitoring' => [
    'enabled' => true,
    'thresholds' => [
        'auth_failures' => 10,  // Alert if > 10 failures in time window
    ],
],
```

View dashboard: `http://localhost/dashboard.html`

---

### 6. Secure Password Storage

**Database users:** Argon2ID hashing (automatic via `create_user.php`)

```php
// In create_user.php
$passwordHash = password_hash($password, PASSWORD_ARGON2ID);
```

**Config file users:** Use hashed passwords (future enhancement)

---

### 7. Token Expiration

**Short-lived tokens:**
```php
'jwt_expiration' => 3600,  // 1 hour (recommended)
```

**Long-lived tokens (less secure):**
```php
'jwt_expiration' => 86400,  // 24 hours
```

---

### 8. CORS Configuration

Restrict API access to specific domains:

```php
// Add to public/index.php
header('Access-Control-Allow-Origin: https://yourdomain.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Authorization, X-API-Key, Content-Type');
```

---

## Troubleshooting

### Issue: "401 Unauthorized"

**Causes:**
1. Wrong credentials
2. Auth enabled but no credentials provided
3. Token expired (JWT)
4. Wrong auth method configured

**Solutions:**
```bash
# Check auth method in config
'auth_method' => 'jwt',  # Must match your usage

# Test with API key
curl -H "X-API-Key: changeme123" http://localhost/api.php?action=tables

# Test with Basic Auth
curl -u admin:secret http://localhost/api.php?action=tables

# Test JWT login
curl -X POST -d "username=john&password=pass" http://localhost/api.php?action=login
```

---

### Issue: "403 Forbidden: No role assigned"

**Cause:** User authenticated but no role configured

**Solutions:**

**For API Key:**
```php
'api_key_role' => 'admin',  // Add this to config
```

**For Basic Auth (config users):**
```php
'user_roles' => [
    'john' => 'readonly',  // Map username to role
],
```

**For Basic Auth (database users):**
```sql
-- Check role in database
SELECT username, role FROM api_users WHERE username = 'john';

-- Update if NULL
UPDATE api_users SET role = 'readonly' WHERE username = 'john';
```

**For JWT:**
- Role should be in token claims (check login endpoint)

---

### Issue: "403 Forbidden" (with role assigned)

**Cause:** RBAC blocking access to table

**Check RBAC config:**
```php
'roles' => [
    'readonly' => [
        '*' => ['list', 'read'],
        'api_users' => [],  // ← Explicitly denied
    ],
],
```

**Solution:** Grant permission or use admin role

---

### Issue: API Key not working (wrong method name)

❌ **Wrong:**
```php
'auth_method' => 'api_key',  // Underscore won't work!
```

✅ **Correct:**
```php
'auth_method' => 'apikey',  // No underscore
```

---

### Issue: JWT token invalid

**Causes:**
1. Token expired
2. Wrong secret key
3. Token tampered with

**Debug:**
```bash
# Decode token (without verification)
echo "eyJ0eXAi..." | base64 -d

# Check expiration
php -r "
  \$token = 'eyJ0eXAi...';
  \$parts = explode('.', \$token);
  \$payload = json_decode(base64_decode(\$parts[1]));
  echo 'Expires: ' . date('Y-m-d H:i:s', \$payload->exp);
"
```

**Solution:** Re-login to get fresh token

---

### Issue: Database authentication not working

**Check configuration:**
```php
'use_database_auth' => true,  // Must be enabled
```

**Check database:**
```sql
-- Verify user exists
SELECT * FROM api_users WHERE username = 'john';

-- Check password hash
SELECT password_hash FROM api_users WHERE username = 'john';
```

**Test password:**
```php
php -r "
  \$hash = '$2y$10$...';  // From database
  \$password = 'SecurePass123!';
  echo password_verify(\$password, \$hash) ? 'Match' : 'No match';
"
```

---

### Issue: Performance slow with Basic Auth

**Cause:** Database query on every request

**Solution:** Switch to JWT

**Before (Basic Auth):**
- 1000 users × 10 req/min = 10,000 auth queries/minute

**After (JWT):**
- 1000 users × 1 login/hour = 1,000 auth queries/hour
- **99.8% reduction!**

**Change config:**
```php
'auth_method' => 'jwt',  // Instead of 'basic'
```

---

## Summary - Quick Reference

### Postman Quick Setup Guide

#### 1. API Key Authentication
```
Request Type: GET
URL: http://localhost/api.php?action=tables

Option A - Header (Recommended):
├── Headers tab
└── Add: X-API-Key = changeme123

Option B - Query Parameter:
├── Params tab
└── Add: api_key = changeme123
```

#### 2. Basic Authentication
```
Request Type: GET
URL: http://localhost/api.php?action=tables

Authorization tab:
├── Type: Basic Auth
├── Username: admin
└── Password: secret
```

#### 3. JWT Authentication
```
Step 1 - Login:
Request Type: POST
URL: http://localhost/api.php?action=login

Body → x-www-form-urlencoded:
├── username: john
└── password: SecurePass123!

Response: Copy the "token" value

Step 2 - Use Token:
Request Type: GET
URL: http://localhost/api.php?action=tables

Headers tab:
└── Add: Authorization = Bearer eyJ0eXAiOiJKV1Qi...
```

---

### HTTPie Quick Syntax Guide

```bash
# API Key (Header)
http GET http://localhost/api.php action==tables X-API-Key:changeme123

# API Key (Query Parameter)
http GET http://localhost/api.php action==tables api_key==changeme123

# Basic Auth
http -a admin:secret GET http://localhost/api.php action==tables

# JWT Login
http POST http://localhost/api.php action==login username=john password=SecurePass123!

# JWT Request (after login)
http GET http://localhost/api.php action==tables "Authorization: Bearer TOKEN_HERE"
```

---

### Method Comparison Table

| Feature | API Key | Basic Auth | JWT |
|---------|---------|------------|-----|
| **Config Value** | `'apikey'` | `'basic'` | `'jwt'` |
| **Header Name** | `X-API-Key` | `Authorization: Basic` | `Authorization: Bearer` |
| **Query Param** | `?api_key=XXX` | ❌ | ❌ |
| **Login Required** | ❌ | ❌ | ✅ (POST ?action=login) |
| **Role Assignment** | `api_key_role` config | `user_roles` or DB | Token claim |
| **DB Query per Request** | ❌ | ✅ (with DB users) | ❌ |
| **Best For** | Webhooks | Development | Production |
| **Performance** | ⚡ Fast | ⚡ Fast | ⚡⚡⚡ Very Fast |
| **Security** | 🔒 Medium | 🔒 Medium | 🔒🔒 High |
| **User Tracking** | ❌ (shared key) | ✅ | ✅ |
| **Postman Setup** | Headers or Params | Authorization tab | Headers (after login) |
| **HTTPie Syntax** | `X-API-Key:value` | `-a user:pass` | `"Authorization: Bearer ..."` |

---

## Next Steps

1. **Choose auth method** based on your use case
2. **Update `config/api.php`** with correct method name
3. **Configure roles** in RBAC section
4. **Test authentication** with examples above
5. **Monitor dashboard** for security events
6. **Read security best practices** before production

---

**Related Documentation:**
- [User Management Guide](USER_MANAGEMENT.md)
- [RBAC Security Tests](SECURITY_RBAC_TESTS.md)
- [Performance Guide](PERFORMANCE_AUTHENTICATION.md)
- [Monitoring Guide](MONITORING_COMPLETE.md)

---

**Version:** 1.0.0  
**Last Updated:** October 22, 2025
