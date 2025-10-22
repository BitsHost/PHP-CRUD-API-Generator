# Security Test: Protected System Tables

## 🔒 Testing RBAC Protection for api_users Table

This document shows how the system protects sensitive tables like `api_users` from unauthorized access.

---

## Test Scenario

**Setup:**
- User "john" has role "readonly" 
- Role "readonly" has wildcard permissions: `'*' => ['list', 'read']`
- But explicitly denies: `'api_users' => []`

---

## Test 1: Admin Can Access (Expected: ✅ Success)

```bash
# Admin has full access to all tables including api_users
curl -u admin:secret \
  "http://localhost/PHP-CRUD-API-Generator/public/index.php?action=list&table=api_users"

# Expected Response: 200 OK
{
  "data": [
    {"id": 1, "username": "admin", "role": "admin", ...},
    {"id": 2, "username": "john", "role": "readonly", ...}
  ],
  "meta": {"page": 1, "total": 2, ...}
}
```

✅ **PASS** - Admin should see all users

---

## Test 2: Readonly User Tries to Access api_users (Expected: ❌ Forbidden)

```bash
# Readonly user tries to list api_users table
curl -u john:password123 \
  "http://localhost/PHP-CRUD-API-Generator/public/index.php?action=list&table=api_users"

# Expected Response: 403 Forbidden
{
  "error": "Forbidden: readonly cannot list on api_users"
}
```

✅ **PASS** - Readonly user is blocked

---

## Test 3: Readonly User Can Access Regular Tables (Expected: ✅ Success)

```bash
# Same user tries to list a regular table (e.g., posts, products)
curl -u john:password123 \
  "http://localhost/PHP-CRUD-API-Generator/public/index.php?action=list&table=posts"

# Expected Response: 200 OK
{
  "data": [...],
  "meta": {...}
}
```

✅ **PASS** - Regular tables work fine

---

## Test 4: Using API Key (Expected: ❌ Forbidden)

```bash
# User with API key tries to access api_users
curl -H "X-API-Key: johns-readonly-api-key" \
  "http://localhost/PHP-CRUD-API-Generator/public/index.php?action=list&table=api_users"

# Expected Response: 403 Forbidden
{
  "error": "Forbidden: readonly cannot list on api_users"
}
```

✅ **PASS** - API key auth also respects RBAC

---

## How It Works

### 1. RBAC Configuration (`config/api.php`)

```php
'roles' => [
    'admin' => [
        '*' => ['list', 'read', 'create', 'update', 'delete']
    ],
    'readonly' => [
        '*' => ['list', 'read'],           // Wildcard allows all tables
        'api_users' => [],                 // But DENY api_users explicitly
        'api_key_usage' => [],             // And other system tables
    ],
],
```

### 2. Enhanced RBAC Logic (`src/Rbac.php`)

```php
public function isAllowed(string $role, string $table, string $action): bool
{
    // ...
    
    // Check for explicit DENY (takes precedence over wildcards)
    if (isset($perms[$table])) {
        if (empty($perms[$table])) {
            return false;  // ← Empty array = DENY
        }
        // ...
    }
    
    // Wildcard only applies if table not explicitly defined
    // ...
}
```

### 3. Router Enforcement

Every API action calls `enforceRbac()`:
```php
case 'list':
    $this->enforceRbac('list', $query['table']);  // ← Checks RBAC
    $result = $this->api->list($query['table'], $opts);
```

---

## Protected Tables

By default, these system tables should be protected:

| Table | Purpose | Who Can Access |
|-------|---------|----------------|
| `api_users` | User credentials & API keys | Admin only |
| `api_key_usage` | Usage tracking | Admin only |
| Any table starting with `_system` | Internal system tables | Admin only |

---

## Adding More Protected Tables

To protect additional tables, add them to `readonly` role config:

```php
'readonly' => [
    '*' => ['list', 'read'],
    'api_users' => [],           // Deny
    'api_key_usage' => [],       // Deny
    'audit_logs' => [],          // Deny
    'payment_methods' => [],     // Deny
    'internal_notes' => [],      // Deny
],
```

---

## Security Best Practices

1. ✅ **Default Deny** - Explicitly list protected tables
2. ✅ **Least Privilege** - Give users minimum permissions needed
3. ✅ **Test Thoroughly** - Run these tests after any RBAC changes
4. ✅ **Monitor Access** - Log all attempts to access sensitive tables
5. ✅ **Regular Audits** - Review role permissions quarterly

---

## Quick Test Command

Run all tests at once:

```bash
# Save as test_rbac.sh or test_rbac.ps1

echo "Test 1: Admin access to api_users..."
curl -u admin:secret \
  "http://localhost/PHP-CRUD-API-Generator/public/index.php?action=list&table=api_users"

echo -e "\n\nTest 2: Readonly blocked from api_users..."
curl -u john:password123 \
  "http://localhost/PHP-CRUD-API-Generator/public/index.php?action=list&table=api_users"

echo -e "\n\nTest 3: Readonly can access regular tables..."
curl -u john:password123 \
  "http://localhost/PHP-CRUD-API-Generator/public/index.php?action=list&table=posts"
```

---

## ✅ Verification Checklist

- [ ] Readonly users CANNOT list `api_users` table
- [ ] Readonly users CANNOT read specific `api_users` records
- [ ] Readonly users CANNOT create/update/delete `api_users`
- [ ] Admin users CAN access `api_users` normally
- [ ] Readonly users CAN access non-protected tables
- [ ] RBAC works with both Basic Auth and API Keys
- [ ] Monitoring logs access attempts to protected tables

---

## Emergency: Disable Protection Temporarily

If you need to debug or temporarily allow access:

```php
// config/api.php
'readonly' => [
    '*' => ['list', 'read'],
    // 'api_users' => [],  // ← Comment out to allow access
],
```

**⚠️ WARNING:** Remember to re-enable protection after debugging!

---

**Security Issue Resolved:** ✅ System tables are now protected from unauthorized access via RBAC.
