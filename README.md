# PHP CRUD API Generator

Expose your MySQL/MariaDB database as a secure, flexible, and instant REST-like API.  
Features optional authentication (API key, Basic Auth, JWT, OAuth-ready),  
OpenAPI (Swagger) docs, and zero code generation.

---

## 🚀 ## 🚀 Features

- Auto-discovers tables and columns
- Full CRUD endpoints for any table
- Configurable authentication (API Key, Basic Auth, JWT, or none)
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

---

## 📦 Installation

```bash
composer create-project bitshost/php-crud-api-generator
```

---

## ⚙️ Configuration

Copy and edit config files:

```bash
cp config/db.example.php config/db.php
cp config/api.example.php config/api.php
```

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
    'oauth_providers' => [
        // 'google' => ['client_id' => '', 'client_secret' => '', ...]
    ]
];
```

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

| Action    | Method | Usage Example                                               |
|-----------|--------|------------------------------------------------------------|
| tables    | GET    | `/index.php?action=tables`                                 |
| columns   | GET    | `/index.php?action=columns&table=users`                    |
| list      | GET    | `/index.php?action=list&table=users`                       |
| read      | GET    | `/index.php?action=read&table=users&id=1`                  |
| create    | POST   | `/index.php?action=create&table=users` (form POST)         |
| update    | POST   | `/index.php?action=update&table=users&id=1` (form POST)    |
| delete    | POST   | `/index.php?action=delete&table=users&id=1`                |
| openapi   | GET    | `/index.php?action=openapi`                                |
| login     | POST   | `/index.php?action=login` (JWT only)                       |

---

## 🤖 Example `curl` Commands

```sh
curl http://localhost/index.php?action=tables
curl -H "X-API-Key: changeme123" "http://localhost/index.php?action=list&table=users"
curl -X POST -d "username=admin&password=secret" http://localhost/index.php?action=login
curl -H "Authorization: Bearer <token>" "http://localhost/index.php?action=list&table=users"
curl -u admin:secret "http://localhost/index.php?action=list&table=users"
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
- Never commit real credentials—use `.gitignore` and example configs.
- Restrict DB user privileges.
- **Input validation**: All user inputs (table names, column names, IDs, filters) are validated to prevent SQL injection and invalid queries.
- **Parameterized queries**: All database queries use prepared statements with bound parameters.
- **RBAC enforcement**: Role-based access control is enforced at the routing level before any database operations.

---

## 🧪 Running Tests

```bash
./vendor/bin/phpunit
```

---

## 🗺️ Roadmap

- Relations / Linked Data (auto-join, populate, or expand related records)
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