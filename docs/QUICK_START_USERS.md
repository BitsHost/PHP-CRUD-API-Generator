# Quick Start: Database User Management

## 🚀 Setup in 5 Minutes

### Step 1: Create Database Table

```bash
# Run the SQL script to create api_users table
mysql -u root -p your_database < sql/create_api_users.sql

# Or run it in phpMyAdmin, MySQL Workbench, etc.
```

This creates:
- ✅ `api_users` table
- ✅ First admin user (username: admin, password: changeme123)

---

### Step 2: Create New Users

**Option A: Command Line (Recommended)**

```bash
# Navigate to your project
cd d:\GitHub\PHP-CRUD-API-Generator

# Create a user
php scripts/create_user.php john john@example.com SecurePass123! readonly

# Output will show:
# ✅ User created successfully!
# Username:  john
# API Key:   a1b2c3d4e5f6... (64 characters)
```

**Option B: Direct SQL**

```sql
-- Generate API key and hash password first
INSERT INTO api_users (username, email, password_hash, role, api_key, active)
VALUES (
    'newuser',
    'user@example.com',
    '$argon2id$v=19$m=65536,t=4,p=1$...', -- use password_hash() in PHP
    'readonly',
    'generated-64-character-api-key',
    1
);
```

---

### Step 3: Configure API to Use Database Auth

Update `config/api.php` to support both methods:

```php
<?php
return [
    'auth_enabled' => true,
    'auth_method' => 'apikey', // ← CHANGE THIS to 'apikey' or 'basic'
    
    // Keep these for backward compatibility (optional)
    'basic_users' => [
        'admin' => 'secret',
    ],
    
    // NEW: Database authentication
    'use_database_auth' => true,  // ← ADD THIS
    
    // ... rest of config
];
```

---

### Step 4: Update Authenticator (if needed)

Your current `Authenticator` class already supports API keys! Just make sure users provide their API key:

**Method 1: Header (Recommended)**
```bash
curl -H "X-API-Key: YOUR_64_CHAR_API_KEY" \
     http://localhost/PHP-CRUD-API-Generator/public/index.php?action=tables
```

**Method 2: Query Parameter**
```bash
curl "http://localhost/PHP-CRUD-API-Generator/public/index.php?action=tables&api_key=YOUR_64_CHAR_API_KEY"
```

**Method 3: Basic Auth** (username + password)
```bash
curl -u john:SecurePass123! \
     http://localhost/PHP-CRUD-API-Generator/public/index.php?action=tables
```

---

## ✅ You're Done!

### Current Workflow for New Users:

1. **Admin runs:** `php scripts/create_user.php username email password role`
2. **User receives:** API key
3. **User makes requests:** with API key in header

---

## 🔐 How Users Authenticate

### If `auth_method = 'apikey'` in config:

Your `Authenticator` checks these locations for API key:
1. `X-API-Key` header
2. `$_GET['api_key']` query parameter  
3. `$_POST['api_key']` post parameter

**For database auth to work**, you need to:

**Option A: Store API keys in config** (Quick & Simple)
```php
'api_keys' => [
    'a1b2c3d4e5f6...',  // From database
    'x9y8z7w6v5u4...',  // Another user
    'changeme123'       // Legacy key
],
```

**Option B: Check database on every request** (Better, but needs code change)

---

## 🎯 Recommended Approach

**For now (simplest):**

1. Create users with `scripts/create_user.php`
2. Copy API keys to `config/api.php` → `api_keys` array
3. Users authenticate with API keys

**Later (more scalable):**

Implement database lookup in `Authenticator::authenticateApiKey()`:

```php
private function authenticateApiKey(): bool
{
    // Get API key from request
    $apiKey = $_SERVER['HTTP_X_API_KEY'] 
              ?? $_GET['api_key'] 
              ?? $_POST['api_key'] 
              ?? null;
    
    if (!$apiKey) {
        return false;
    }
    
    // NEW: Check database instead of config array
    if ($this->config['use_database_auth'] ?? false) {
        return $this->checkDatabaseApiKey($apiKey);
    }
    
    // OLD: Check config array
    return in_array($apiKey, $this->config['api_keys'] ?? [], true);
}

private function checkDatabaseApiKey(string $apiKey): bool
{
    // Query database for this API key
    // Return true if found and active
    // Set $this->currentUser with user data
}
```

---

## 📊 Summary

| Method | When to Use | Setup Time |
|--------|-------------|------------|
| **Config file** | 1-5 users, internal API | 30 seconds |
| **Database + Script** | 5-100 users, growing API | 5 minutes |
| **Database + Lookup** | 100+ users, public API | 30 minutes |
| **Self-registration** | SaaS product | 2-3 hours |

---

## 🆘 Need Help?

**Check the API key is working:**
```bash
# Test with the admin API key from database
mysql -u root -p -e "SELECT api_key FROM your_db.api_users WHERE username='admin'"

# Use that API key
curl -H "X-API-Key: PASTE_API_KEY_HERE" \
     http://localhost/PHP-CRUD-API-Generator/public/index.php?action=tables
```

**Common Issues:**

1. **401 Unauthorized** → API key not in config `api_keys` array
2. **Table doesn't exist** → Run `sql/create_api_users.sql`
3. **Script error** → Check database connection in `config/db.php`

---

**Next Steps:** See `docs/USER_MANAGEMENT.md` for advanced features like self-registration, admin panel, and OAuth integration.
