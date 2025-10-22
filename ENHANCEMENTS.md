# Enhancements and New Features

This document provides a comprehensive overview of the enhancements made to the PHP CRUD API Generator in version 1.1.0.

## Table of Contents
1. [Advanced Filtering](#advanced-filtering)
2. [Field Selection](#field-selection)
3. [Count Endpoint](#count-endpoint)
4. [Bulk Operations](#bulk-operations)
5. [Input Validation](#input-validation)
6. [Security Improvements](#security-improvements)
7. [Migration Guide](#migration-guide)

---

## Advanced Filtering

### Overview
The filtering system has been enhanced to support multiple comparison operators beyond simple equality checks.

### Supported Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `eq` | Equals (default) | `filter=name:eq:Alice` |
| `neq`, `ne` | Not equals | `filter=status:neq:deleted` |
| `gt` | Greater than | `filter=age:gt:18` |
| `gte`, `ge` | Greater than or equal | `filter=price:gte:100` |
| `lt` | Less than | `filter=stock:lt:10` |
| `lte`, `le` | Less than or equal | `filter=discount:lte:50` |
| `like` | Pattern matching | `filter=email:like:%@gmail.com` |
| `in` | In list (pipe-separated) | `filter=status:in:active|pending|processing` |
| `notin`, `nin` | Not in list | `filter=role:notin:admin|superadmin` |
| `null` | Is NULL | `filter=deleted_at:null:` |
| `notnull` | Is NOT NULL | `filter=email:notnull:` |

### Filter Syntax

**New Format:** `col:operator:value`
- Example: `filter=age:gt:18,status:eq:active`

**Legacy Format:** `col:value` (still supported)
- Example: `filter=name:Alice`
- Automatically uses `=` for exact match or `LIKE` if value contains `%`

### Multiple Filters

Combine multiple filters using commas:
```
/index.php?action=list&table=users&filter=age:gte:18,status:eq:active,email:like:%@gmail.com
```

This creates an AND condition for all filters.

### Use Cases

**E-commerce Product Filtering:**
```
# Products between $10 and $100 with stock
/index.php?action=list&table=products&filter=price:gte:10,price:lte:100,stock:gt:0

# Out of stock products
/index.php?action=list&table=products&filter=stock:eq:0
```

**User Management:**
```
# Active users who registered recently
/index.php?action=list&table=users&filter=status:eq:active,created_at:gte:2024-01-01

# Users without email verification
/index.php?action=list&table=users&filter=email_verified_at:null:
```

---

## Field Selection

### Overview
The field selection feature allows you to retrieve only specific columns from a table, reducing bandwidth and improving performance.

### Syntax
```
/index.php?action=list&table=users&fields=id,name,email
```

### Benefits
- **Reduced bandwidth**: Only requested fields are transferred
- **Improved performance**: Less data to serialize and deserialize
- **Privacy**: Exclude sensitive fields from responses
- **Mobile optimization**: Send only necessary data to mobile clients

### Examples

**Basic field selection:**
```
/index.php?action=list&table=users&fields=id,name
```

**Combined with filtering:**
```
/index.php?action=list&table=users&fields=id,name,email&filter=status:eq:active
```

**Combined with sorting and pagination:**
```
/index.php?action=list&table=products&fields=id,name,price&sort=-price&page=1&page_size=20
```

---

## Count Endpoint

### Overview
A dedicated endpoint for counting records without pagination overhead. Perfect for dashboards, analytics, and statistics.

### Syntax
```
GET /index.php?action=count&table=users
```

### Features
- Supports all filter operators
- No pagination overhead
- Returns simple count object
- Uses same permissions as `list` action

### Examples

**Basic count:**
```bash
curl "http://localhost/index.php?action=count&table=users"
# Response: {"count": 150}
```

**Count with filters:**
```bash
# Count active users
curl "http://localhost/index.php?action=count&table=users&filter=status:eq:active"
# Response: {"count": 120}

# Count users over 18
curl "http://localhost/index.php?action=count&table=users&filter=age:gt:18"
# Response: {"count": 95}

# Count premium subscriptions
curl "http://localhost/index.php?action=count&table=subscriptions&filter=type:eq:premium,status:in:active|trial"
# Response: {"count": 45}
```

### Use Cases

**Dashboard Statistics:**
```javascript
// Fetch multiple counts for dashboard
Promise.all([
  fetch('/index.php?action=count&table=users&filter=status:eq:active'),
  fetch('/index.php?action=count&table=orders&filter=status:eq:pending'),
  fetch('/index.php?action=count&table=products&filter=stock:lt:10')
]).then(results => {
  // Display statistics
});
```

**Analytics:**
```bash
# User growth metrics
curl "http://localhost/index.php?action=count&table=users&filter=created_at:gte:2024-01-01"

# Conversion rates
curl "http://localhost/index.php?action=count&table=leads&filter=status:eq:converted"
```

---

## Bulk Operations

### Overview
Bulk operations allow you to create or delete multiple records efficiently in single API calls.

### Bulk Create

**Endpoint:** `POST /index.php?action=bulk_create&table=users`

**Features:**
- Transaction-based (all or nothing)
- Returns all created records with IDs
- Automatic rollback on failure

**Request:**
```json
[
  {"name": "Alice", "email": "alice@example.com", "age": 25},
  {"name": "Bob", "email": "bob@example.com", "age": 30},
  {"name": "Charlie", "email": "charlie@example.com", "age": 35}
]
```

**Response:**
```json
{
  "success": true,
  "created": 3,
  "data": [
    {"id": 101, "name": "Alice", "email": "alice@example.com", "age": 25},
    {"id": 102, "name": "Bob", "email": "bob@example.com", "age": 30},
    {"id": 103, "name": "Charlie", "email": "charlie@example.com", "age": 35}
  ]
}
```

**curl Example:**
```bash
curl -X POST -H "Content-Type: application/json" \
  -d '[{"name":"Alice","email":"alice@example.com"},{"name":"Bob","email":"bob@example.com"}]' \
  "http://localhost/index.php?action=bulk_create&table=users"
```

### Bulk Delete

**Endpoint:** `POST /index.php?action=bulk_delete&table=users`

**Features:**
- Single efficient query
- Returns count of deleted records
- Works with any ID format (numeric or UUID)

**Request:**
```json
{
  "ids": [1, 2, 3, 4, 5]
}
```

**Response:**
```json
{
  "success": true,
  "deleted": 5
}
```

**curl Example:**
```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{"ids":[1,2,3,4,5]}' \
  "http://localhost/index.php?action=bulk_delete&table=users"
```

### Use Cases

**Data Import:**
```javascript
// Import users from CSV
const users = parseCSV(csvData);
fetch('/index.php?action=bulk_create&table=users', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify(users)
});
```

**Batch Cleanup:**
```javascript
// Delete old records
const oldRecordIds = [101, 102, 103, 104, 105];
fetch('/index.php?action=bulk_delete&table=logs', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({ids: oldRecordIds})
});
```

---

## Input Validation

### Overview
Comprehensive input validation has been added to prevent SQL injection, invalid queries, and malicious inputs.

### Validator Class

The new `Validator` class provides centralized validation methods:

```php
Validator::validateTableName($table)      // Alphanumeric + underscore only
Validator::validateColumnName($column)    // Alphanumeric + underscore only
Validator::validateId($id)                // Numeric or UUID format
Validator::validatePage($page)            // Positive integer
Validator::validatePageSize($size)        // Integer, 1-100
Validator::validateOperator($op)          // Valid filter operator
Validator::validateSort($sort)            // Valid sort format
```

### What's Validated

**Table Names:**
- Must be alphanumeric with underscores only
- Example: `users`, `order_items`, `product_123`

**Column Names:**
- Must be alphanumeric with underscores only
- Example: `user_id`, `created_at`, `email_address`

**IDs:**
- Must be numeric or valid UUID format
- Examples: `123`, `550e8400-e29b-41d4-a716-446655440000`

**Pagination:**
- Page must be positive integer (≥1)
- Page size must be 1-100 (default: 20)

**Sort Parameters:**
- Column names must be valid
- Format: `col1,-col2` (prefix `-` for DESC)

---

## Security Improvements

### 1. SQL Injection Prevention

**Problem:** Previous filter implementation could have parameter name collisions.

**Solution:** Each filter parameter now gets a unique name:
```php
// Old: $params['name'] could be overwritten
// New: $params['name_0'], $params['name_1'], etc.
```

### 2. Parameterized Queries

All database queries use prepared statements with bound parameters:
```php
// Good: Using prepared statements
$stmt = $pdo->prepare("SELECT * FROM `$table` WHERE `col` = :param");
$stmt->execute(['param' => $value]);

// Bad: Never concatenate user input
$stmt = $pdo->query("SELECT * FROM $table WHERE col = '$value'"); // ❌
```

### 3. Input Validation

All user inputs are validated before use:
- Table names checked against allowed characters
- Column names validated
- IDs validated for correct format
- Filter operators checked against whitelist

### 4. RBAC Integration

Input validation is applied before RBAC checks, ensuring invalid inputs are rejected early:
```
Request → Input Validation → Authentication → RBAC → Database Query
```

---

## Migration Guide

### From 1.0.0 to 1.1.0

**No Breaking Changes!** Version 1.1.0 is fully backward compatible.

### Using New Features

**1. Upgrade your filtering:**

Before:
```
/index.php?action=list&table=users&filter=age:30
```

After (more options available):
```
/index.php?action=list&table=users&filter=age:gte:30,status:eq:active
```

**2. Optimize with field selection:**

Before:
```
/index.php?action=list&table=users
// Returns all columns
```

After:
```
/index.php?action=list&table=users&fields=id,name,email
// Returns only specified columns
```

**3. Use count for statistics:**

Before:
```
/index.php?action=list&table=users&page_size=1
// Inefficient, still fetches data
```

After:
```
/index.php?action=count&table=users
// Efficient, returns just the count
```

**4. Bulk operations for efficiency:**

Before:
```javascript
// Create users one by one
for (const user of users) {
  await fetch('/index.php?action=create&table=users', {
    method: 'POST',
    body: JSON.stringify(user)
  });
}
```

After:
```javascript
// Create all users at once
await fetch('/index.php?action=bulk_create&table=users', {
  method: 'POST',
  body: JSON.stringify(users)
});
```

### Testing Your Migration

1. **Test basic operations** still work as before
2. **Try new filter operators** on non-production data
3. **Test field selection** to ensure correct columns returned
4. **Validate error responses** for invalid inputs
5. **Test bulk operations** with small datasets first

---

## Best Practices

### 1. Use Field Selection
Always specify fields when you don't need all columns:
```
✅ /index.php?action=list&table=users&fields=id,name
❌ /index.php?action=list&table=users (returns all columns)
```

### 2. Leverage Count Endpoint
Use the count endpoint for statistics instead of fetching and counting:
```
✅ /index.php?action=count&table=users
❌ /index.php?action=list&table=users then count in code
```

### 3. Batch Operations
Use bulk operations when creating or deleting multiple records:
```
✅ bulk_create with array of records
❌ Multiple create calls in a loop
```

### 4. Efficient Filtering
Use specific operators instead of fetching and filtering in code:
```
✅ filter=age:gte:18,status:in:active|trial
❌ Fetch all records and filter in application code
```

### 5. Pagination
Always paginate large result sets:
```
✅ page=1&page_size=20
❌ Fetching thousands of records at once
```

---

## Performance Tips

1. **Field Selection**: 50-70% reduction in response size for tables with many columns
2. **Count Endpoint**: 10x faster than fetching records for counting
3. **Bulk Operations**: 10-100x faster than individual operations depending on record count
4. **Indexed Columns**: Use indexed columns in filters for better performance
5. **Pagination**: Keep page_size reasonable (20-50 records) for best performance

---

## Support

For questions or issues with these enhancements:
1. Check the [README.md](README.md) for usage examples
2. Review the [CHANGELOG.md](CHANGELOG.md) for version history
3. Open an issue on [GitHub](https://github.com/BitsHost/PHP-CRUD-API-Generator)

---

**Built by [BitHost](https://github.com/BitsHost)** | Version 1.1.0
