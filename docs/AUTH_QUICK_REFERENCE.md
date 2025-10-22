# Authentication Quick Reference Card

**Last Updated:** October 22, 2025  
**Full Guide:** [AUTHENTICATION.md](AUTHENTICATION.md)

---

## Config Values (MUST BE EXACT!)

```php
// In config/api.php

'auth_method' => 'apikey',  // ✅ Correct (NOT 'api_key')
'auth_method' => 'basic',   // ✅ Correct
'auth_method' => 'jwt',     // ✅ Correct
'auth_method' => 'oauth',   // ✅ Correct (placeholder)
```

---

## API Key Authentication

**Config:**
```php
'auth_method' => 'apikey',
'api_keys' => ['changeme123'],
'api_key_role' => 'admin',  // Role for all API key users
```

**Usage:**
```bash
# Header (recommended)
curl -H "X-API-Key: changeme123" http://localhost/api.php?action=tables

# Query parameter
curl "http://localhost/api.php?action=tables&api_key=changeme123"
```

---

## Basic Authentication

**Config:**
```php
'auth_method' => 'basic',
'basic_users' => [
    'admin' => 'secret',
],
'user_roles' => [
    'admin' => 'admin',
],
'use_database_auth' => true,  // Check database too
```

**Usage:**
```bash
# cURL
curl -u admin:secret http://localhost/api.php?action=tables

# JavaScript
const credentials = btoa('admin:secret');
fetch('/api.php?action=tables', {
  headers: { 'Authorization': 'Basic ' + credentials }
});
```

**Create Database User:**
```bash
php scripts/create_user.php john john@email.com SecurePass123! readonly
```

---

## JWT Authentication

**Config:**
```php
'auth_method' => 'jwt',
'jwt_secret' => 'a7f92c8e4b6d1f3a9e8c7b5d2f1a6e9b...',  // Change this!
'jwt_expiration' => 3600,  // 1 hour
'use_database_auth' => true,
```

**Step 1 - Login:**
```bash
curl -X POST \
  -d "username=john&password=SecurePass123!" \
  http://localhost/api.php?action=login

# Response:
# {"success":true,"token":"eyJ0eXAi...","expires_in":3600,"user":"john","role":"readonly"}
```

**Step 2 - Use Token:**
```bash
curl -H "Authorization: Bearer eyJ0eXAi..." \
  http://localhost/api.php?action=tables
```

**JavaScript Example:**
```javascript
// Login
const loginRes = await fetch('/api.php?action=login', {
  method: 'POST',
  body: new URLSearchParams({
    username: 'john',
    password: 'SecurePass123!'
  })
});
const { token } = await loginRes.json();

// Use token
const dataRes = await fetch('/api.php?action=tables', {
  headers: { 'Authorization': 'Bearer ' + token }
});
const data = await dataRes.json();
```

---

## RBAC Roles

**Predefined Roles:**

| Role | Tables | Actions | System Tables |
|------|--------|---------|---------------|
| `admin` | All (`*`) | All | ✅ Can access |
| `readonly` | All (`*`) | list, read | ❌ Blocked |
| `editor` | All (`*`) | All | ❌ Blocked |
| `users_manager` | users, orders | Specific | ❌ No access |

**Config:**
```php
'roles' => [
    'admin' => [
        '*' => ['list', 'read', 'create', 'update', 'delete']
    ],
    'readonly' => [
        '*' => ['list', 'read'],
        'api_users' => [],       // Empty array = DENY
        'api_key_usage' => [],
    ],
],
```

**Actions:**
- `list` - View list
- `read` - View single record
- `create` - Insert
- `update` - Modify
- `delete` - Remove

---

## Role Assignment by Auth Method

| Auth Method | Role Source |
|-------------|-------------|
| **apikey** | `api_key_role` in config |
| **basic** (config users) | `user_roles` mapping |
| **basic** (DB users) | `api_users.role` column |
| **jwt** | `role` claim in token |

---

## Common Issues

### "401 Unauthorized"
- Check `auth_method` matches your usage
- Verify credentials/token
- Ensure `auth_enabled = true`

### "403 Forbidden: No role assigned"
- API Key: Add `'api_key_role' => 'admin'` to config
- Basic Auth: Add user to `user_roles` mapping or check DB role
- JWT: Role should be in token claims

### "403 Forbidden" (with role)
- Check RBAC permissions for your role
- System tables blocked for non-admin roles

### API Key doesn't work
- Use `'apikey'` NOT `'api_key'` (no underscore!)

---

## Performance Comparison

| Method | DB Queries per Request | Best For |
|--------|------------------------|----------|
| API Key | 0 | Webhooks |
| Basic (config) | 0 | Development |
| Basic (DB) | 1 | Small apps |
| JWT | 0 | Production |

**JWT Performance:**
- Before: 600,000 auth queries/hour
- After: 1,000 auth queries/hour
- **Reduction: 99.8%** 🚀

---

## Security Checklist

- [ ] Use HTTPS in production
- [ ] Change `jwt_secret` to random 64+ char string
- [ ] Rotate API keys every 90 days
- [ ] Use strong passwords (8+ chars, mixed case, numbers, symbols)
- [ ] Enable rate limiting (`'rate_limit' => ['enabled' => true]`)
- [ ] Monitor authentication failures (dashboard)
- [ ] Set appropriate JWT expiration (1-24 hours)
- [ ] Block system tables for non-admin roles
- [ ] Use database users (not config file) for production

---

## Quick Commands

```bash
# Generate JWT secret
php -r "echo bin2hex(random_bytes(32));"

# Create database user
php scripts/create_user.php <username> <email> <password> <role>

# Test authentication
curl -H "X-API-Key: changeme123" http://localhost/api.php?action=tables

# View monitoring dashboard
# http://localhost/PHP-CRUD-API-Generator/dashboard.html
```

---

## Documentation Links

- **[AUTHENTICATION.md](AUTHENTICATION.md)** - Complete guide (50+ pages)
- **[USER_MANAGEMENT.md](USER_MANAGEMENT.md)** - User management system
- **[QUICK_START_USERS.md](QUICK_START_USERS.md)** - 5-minute setup
- **[SECURITY_RBAC_TESTS.md](SECURITY_RBAC_TESTS.md)** - RBAC testing
- **[PERFORMANCE_AUTHENTICATION.md](PERFORMANCE_AUTHENTICATION.md)** - Performance optimization

---

**Need help?** Read the full guide: [docs/AUTHENTICATION.md](AUTHENTICATION.md)
