# PHP CRUD API Generator

Expose your MySQL/MariaDB database as a secure, flexible, and instant REST-like API.  
Features optional authentication (API key, Basic Auth, JWT, OAuth-ready),  
OpenAPI (Swagger) docs, and zero code generation.

---

## 🚀 Features

- Auto-discovers tables and columns
- Full CRUD endpoints for any table
- **Bulk operations** - Create or delete multiple records efficiently
- Configurable authentication (API Key, Basic Auth, JWT, or none)
- **Rate limiting** - Prevent API abuse with configurable request limits
- **Request logging** - Comprehensive logging for debugging and monitoring
- **Advanced query features:**
  - **Field selection** - Choose specific columns to return
  - **Advanced filtering** - Support for multiple comparison operators (eq, neq, gt, gte, lt, lte, like, in, notin, null, notnull)
  - **Sorting** - Multi-column sorting with ascending/descending order
  - **Pagination** - Efficient pagination with metadata
- **Input validation** - Comprehensive validation to prevent SQL injection and invalid inputs
- RBAC: per-table role-based access control
- Admin panel (minimal)
- OpenAPI (Swagger) JSON endpoint for instant docs
- Clean PSR-4 codebase
- PHPUnit tests and extensible architecture

📖 **[See detailed enhancement documentation →](ENHANCEMENTS.md)**
📖 **[Rate Limiting Documentation →](docs/RATE_LIMITING.md)**
📖 **[Request Logging Documentation →](docs/REQUEST_LOGGING.md)**
📖 **[Quick Start (5 minutes) →](docs/QUICK_START.md)**

---

## 📦 Installation

### Option 1: Install as Library (Recommended) ⚡

**Just 4 simple steps:**

```bash
# 1. Install via Composer
composer require bitshost/php-crud-api-generator

# 2. Copy 3 files to your project root
copy vendor/bitshost/php-crud-api-generator/public/index.php index.php
copy vendor/bitshost/php-crud-api-generator/dashboard.html dashboard.html
copy vendor/bitshost/php-crud-api-generator/health.php health.php

# 3. Edit index.php - Change 2 lines (point config paths to vendor)
# Change: require __DIR__ . '/config/...'
# To:     require __DIR__ . '/vendor/bitshost/php-crud-api-generator/config/...'

# 4. Configure & run
notepad vendor/bitshost/php-crud-api-generator/config/db.php
notepad vendor/bitshost/php-crud-api-generator/config/api.php
php -S localhost:8000
```

**That's it!** Total modifications: **2 lines of code** 🚀

**📖 [5-Minute Quick Start Guide →](docs/QUICK_START.md)**

### Option 2: Standalone Project (Even Simpler!)

Download complete ready-to-use project:

```bash
composer create-project bitshost/php-crud-api-generator my-api
cd my-api

# Configure
cp config/db.example.php config/db.php
cp config/api.example.php config/api.php
notepad config/db.php
notepad config/api.php

# Run
php -S localhost:8000
```

**That's it!** Everything in one folder, ready to run. **0 lines to modify** 🚀

---

## ⚙️ Configuration

### If installed as library (via composer require):

Edit config files in vendor directory:

```bash
notepad vendor/bitshost/php-crud-api-generator/config/db.php
notepad vendor/bitshost/php-crud-api-generator/config/api.php
```

### If standalone project (via composer create-project):

Copy and edit config files:

```bash
cp config/db.example.php config/db.php
cp config/api.example.php config/api.php
```

---

**Config file structure:**

Edit `config/db.php`:

```php
return [
    'host' => 'localhost',
    'dbname' => 'your_database',
    'user' => 'your_db_user',
    'pass' => 'your_db_password',
    'charset' => 'utf8mb4'
];
```

Edit `config/api.php`:

```php
return [
    'auth_enabled' => false, // true to require authentication
    'auth_method' => 'apikey', // 'apikey', 'basic', 'jwt', 'oauth'
    'api_keys' => ['changeme123'], // API keys for 'apikey'
    'basic_users' => ['admin' => 'secret'], // Users for 'basic' and 'jwt'
    'jwt_secret' => 'YourSuperSecretKey',
    'jwt_issuer' => 'yourdomain.com',
    'jwt_audience' => 'yourdomain.com',
    
    // Rate limiting (recommended for production)
    'rate_limit' => [
        'enabled' => true,
        'max_requests' => 100,      // 100 requests
        'window_seconds' => 60,     // per 60 seconds (1 minute)
    ],
    
    // Request logging (recommended for production)
    'logging' => [
        'enabled' => true,
        'log_dir' => __DIR__ . '/../logs',
        'log_level' => 'info',      // debug, info, warning, error
    ],
];
```

---

## 🔒 Security Setup (Production)

⚠️ **IMPORTANT:** This framework ships with **example credentials for development**.  
You **MUST** change these before deploying to production!

### Quick Security Setup:

```bash
# 1. Generate secure secrets (JWT secret + API keys)
php scripts/generate_secrets.php

# 2. Update config/api.php with generated secrets

# 3. Create admin user in database
php scripts/create_user.php admin admin@yoursite.com YourSecurePassword123! admin
```

### What to Change:

- [ ] `jwt_secret` - Generate with: `php scripts/generate_jwt_secret.php`
- [ ] `api_keys` - Use long random strings (64+ characters)
- [ ] Default admin password in `sql/create_api_users.sql`
- [ ] Database credentials in `config/db.php`

📖 **Full security guide:** [docs/AUTHENTICATION.md](docs/AUTHENTICATION.md)

---

---

## 🔐 Authentication Modes

- **No auth:** `'auth_enabled' => false`
- **API Key:** `'auth_enabled' => true, 'auth_method' => 'apikey'`  
  Client: `X-API-Key` header or `?api_key=...`
- **Basic Auth:** `'auth_method' => 'basic'`  
  Client: HTTP Basic Auth
- **JWT:** `'auth_method' => 'jwt'`  
  1. `POST /index.php?action=login` with `username` and `password` (from `basic_users`)
  2. Use returned token as `Authorization: Bearer <token>`
- **OAuth (future):** `'auth_method' => 'oauth'`  
  (Implement provider logic as needed)

---

## 📚 API Endpoints

All requests go through `public/index.php` with `action` parameter.

| Action       | Method | Usage Example                                               |
|--------------|--------|-------------------------------------------------------------|
| tables       | GET    | `/index.php?action=tables`                                  |
| columns      | GET    | `/index.php?action=columns&table=users`                     |
| list         | GET    | `/index.php?action=list&table=users`                        |
| count        | GET    | `/index.php?action=count&table=users`                       |
| read         | GET    | `/index.php?action=read&table=users&id=1`                   |
| create       | POST   | `/index.php?action=create&table=users` (form POST or JSON)  |
| update       | POST   | `/index.php?action=update&table=users&id=1` (form POST or JSON) |
| delete       | POST   | `/index.php?action=delete&table=users&id=1`                 |
| bulk_create  | POST   | `/index.php?action=bulk_create&table=users` (JSON array)    |
| bulk_delete  | POST   | `/index.php?action=bulk_delete&table=users` (JSON with ids) |
| openapi      | GET    | `/index.php?action=openapi`                                 |
| login        | POST   | `/index.php?action=login` (JWT only)                        |

---

## 🤖 Example `curl` Commands

```sh
# List tables
curl http://localhost/index.php?action=tables

# List users with API key
curl -H "X-API-Key: changeme123" "http://localhost/index.php?action=list&table=users"

# JWT login
curl -X POST -d "username=admin&password=secret" http://localhost/index.php?action=login

# List with JWT token
curl -H "Authorization: Bearer <token>" "http://localhost/index.php?action=list&table=users"

# Basic auth
curl -u admin:secret "http://localhost/index.php?action=list&table=users"

# Bulk create
curl -X POST -H "Content-Type: application/json" \
  -d '[{"name":"Alice","email":"alice@example.com"},{"name":"Bob","email":"bob@example.com"}]' \
  "http://localhost/index.php?action=bulk_create&table=users"

# Bulk delete
curl -X POST -H "Content-Type: application/json" \
  -d '{"ids":[1,2,3]}' \
  "http://localhost/index.php?action=bulk_delete&table=users"
```

---

### 💪 Bulk Operations

The API supports bulk operations for efficient handling of multiple records:

#### Bulk Create

Create multiple records in a single transaction. If any record fails, the entire operation is rolled back.

**Endpoint:** `POST /index.php?action=bulk_create&table=users`

**Request Body (JSON array):**
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
    {"id": 1, "name": "Alice", "email": "alice@example.com", "age": 25},
    {"id": 2, "name": "Bob", "email": "bob@example.com", "age": 30},
    {"id": 3, "name": "Charlie", "email": "charlie@example.com", "age": 35}
  ]
}
```

#### Bulk Delete

Delete multiple records by their IDs in a single query.

**Endpoint:** `POST /index.php?action=bulk_delete&table=users`

**Request Body (JSON):**
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

---

### 📊 Count Records

Get the total count of records in a table with optional filtering. This is useful for analytics and doesn't include pagination overhead.

**Endpoint:** `GET /index.php?action=count&table=users`

**Query Parameters:**
- `filter` - (Optional) Same filter syntax as the list endpoint

**Examples:**

```sh
# Count all users
curl "http://localhost/index.php?action=count&table=users"

# Count active users
curl "http://localhost/index.php?action=count&table=users&filter=status:eq:active"

# Count users over 18
curl "http://localhost/index.php?action=count&table=users&filter=age:gt:18"

# Count with multiple filters
curl "http://localhost/index.php?action=count&table=users&filter=status:eq:active,age:gte:18"
```

**Response:**
```json
{
  "count": 42
}
```

---


### 🔄 Advanced Query Features (Filtering, Sorting, Pagination, Field Selection)

The `list` action endpoint now supports advanced query parameters:

| Parameter    | Type    | Description                                                                                       |
|--------------|---------|---------------------------------------------------------------------------------------------------|
| `filter`     | string  | Filter rows by column values. Format: `filter=col:op:value` or `filter=col:value` (backward compatible). Use `,` to combine multiple filters. |
| `sort`       | string  | Sort by columns. Comma-separated. Use `-` prefix for DESC. Example: `sort=-created_at,name`       |
| `page`       | int     | Page number (1-based). Default: `1`                                                               |
| `page_size`  | int     | Number of rows per page (max 100). Default: `20`                                                  |
| `fields`     | string  | Select specific fields. Comma-separated. Example: `fields=id,name,email`                          |

#### Filter Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `eq` or `:` | Equals | `filter=name:eq:Alice` or `filter=name:Alice` |
| `neq` or `ne` | Not equals | `filter=status:neq:deleted` |
| `gt` | Greater than | `filter=age:gt:18` |
| `gte` or `ge` | Greater than or equal | `filter=price:gte:100` |
| `lt` | Less than | `filter=stock:lt:10` |
| `lte` or `le` | Less than or equal | `filter=discount:lte:50` |
| `like` | Pattern match | `filter=email:like:%@gmail.com` |
| `in` | In list (pipe-separated) | `filter=status:in:active|pending` |
| `notin` or `nin` | Not in list | `filter=role:notin:admin|super` |
| `null` | Is NULL | `filter=deleted_at:null:` |
| `notnull` | Is NOT NULL | `filter=email:notnull:` |

**Examples:**

- **Basic filtering:** `GET /index.php?action=list&table=users&filter=name:Alice`
- **Advanced filtering:** `GET /index.php?action=list&table=users&filter=age:gt:18,status:eq:active`
- **Field selection:** `GET /index.php?action=list&table=users&fields=id,name,email`
- **Sorting:** `GET /index.php?action=list&table=users&sort=-created_at,name`
- **Pagination:** `GET /index.php?action=list&table=users&page=2&page_size=10`
- **Combined query:** `GET /index.php?action=list&table=users&filter=email:like:%gmail.com&sort=name&page=1&page_size=5&fields=id,name,email`
- **IN operator:** `GET /index.php?action=list&table=orders&filter=status:in:pending|processing|shipped`
- **Multiple conditions:** `GET /index.php?action=list&table=products&filter=price:gte:10,price:lte:100,stock:gt:0`

**Response:**
```json
{
  "data": [ ... array of rows ... ],
  "meta": {
    "total": 47,
    "page": 2,
    "page_size": 10,
    "pages": 5
  }
}
```

---

### 📝 OpenAPI Path Example

For `/index.php?action=list&table={table}`:

```yaml
get:
  summary: List rows in {table} with optional filtering, sorting, and pagination
  parameters:
    - name: table
      in: query
      required: true
      schema: { type: string }
    - name: filter
      in: query
      required: false
      schema: { type: string }
      description: |
        Filter rows by column values. Example: filter=name:Alice,email:%gmail.com
    - name: sort
      in: query
      required: false
      schema: { type: string }
      description: |
        Sort by columns. Example: sort=-created_at,name
    - name: page
      in: query
      required: false
      schema: { type: integer, default: 1 }
      description: Page number (1-based)
    - name: page_size
      in: query
      required: false
      schema: { type: integer, default: 20, maximum: 100 }
      description: Number of rows per page (max 100)
  responses:
    '200':
      description: List of rows with pagination meta
      content:
        application/json:
          schema:
            type: object
            properties:
              data:
                type: array
                items: { type: object }
              meta:
                type: object
                properties:
                  total: { type: integer }
                  page: { type: integer }
                  page_size: { type: integer }
                  pages: { type: integer }
```

## 🛡️ Security Notes

- **Enable authentication for any public deployment!**
- **Enable rate limiting in production** to prevent abuse
- **Enable request logging** for security auditing and debugging
- Never commit real credentials—use `.gitignore` and example configs.
- Restrict DB user privileges.
- **Input validation**: All user inputs (table names, column names, IDs, filters) are validated to prevent SQL injection and invalid queries.
- **Parameterized queries**: All database queries use prepared statements with bound parameters.
- **RBAC enforcement**: Role-based access control is enforced at the routing level before any database operations.
- **Rate limiting**: Configurable request limits prevent API abuse and DoS attacks.
- **Sensitive data redaction**: Passwords, tokens, and API keys are automatically redacted from logs.

📖 **[Rate Limiting Documentation →](docs/RATE_LIMITING.md)**
📖 **[Request Logging Documentation →](docs/REQUEST_LOGGING.md)**

---

## 🧪 Running Tests

```bash
./vendor/bin/phpunit
```

---

### 🔗 Working with Related Data (Client-Side Joins)

Your API provides all the data you need - it's up to the client to decide how to combine it. This approach gives you maximum flexibility and control.

**Current approach:** Fetch related data in separate requests and combine on the client side.

#### Quick Example: Get User with Posts

```javascript
// 1. Fetch user
const user = await fetch('/api.php?action=read&table=users&id=123')
  .then(r => r.json());

// 2. Fetch user's posts
const posts = await fetch('/api.php?action=list&table=posts&filter=user_id:123')
  .then(r => r.json());

// 3. Combine however you want
const userData = {
  ...user,
  posts: posts.data
};
```

#### Optimization: Use IN Operator for Batch Fetching

```javascript
// Get multiple related records in one request
const postIds = '1|2|3|4|5';  // IDs from previous query
const comments = await fetch(
  `/api.php?action=list&table=comments&filter=post_id:in:${postIds}`
).then(r => r.json());

// Group by post_id on client
const commentsByPost = comments.data.reduce((acc, comment) => {
  acc[comment.post_id] = acc[comment.post_id] || [];
  acc[comment.post_id].push(comment);
  return acc;
}, {});
```

#### Parallel Fetching for Performance

```javascript
// Fetch multiple resources simultaneously
const [user, posts, comments] = await Promise.all([
  fetch('/api.php?action=read&table=users&id=123').then(r => r.json()),
  fetch('/api.php?action=list&table=posts&filter=user_id:123').then(r => r.json()),
  fetch('/api.php?action=list&table=comments&filter=user_id:123').then(r => r.json())
]);

// All requests happen at once - much faster!
```

📖 **[See complete client-side join examples →](docs/CLIENT_SIDE_JOINS.md)**

**Why this approach?**
- ✅ Client decides what data to fetch and when
- ✅ Easy to optimize with caching and parallel requests
- ✅ Different clients can have different data needs
- ✅ Standard REST API practice
- ✅ No server-side complexity for joins

**Future:** Auto-join/expand features may be added based on user demand.

---

## 🗺️ Roadmap

- **Client-side joins** ✅ (Current - simple and flexible!)
- Relations / Linked Data (auto-join, populate, or expand related records) - *Future, based on demand*
- API Versioning (when needed)
- OAuth/SSO (if targeting SaaS/public)
- More DB support (Postgres, SQLite, etc.)
- Analytics & promotion endpoints

---

## 📄 License

MIT

---

## 🙌 Credits

Built by [BitHost](https://github.com/BitsHost). PRs/issues welcome!