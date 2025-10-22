# PHP CRUD API Generator

Expose your MySQL/MariaDB database as a secure, flexible, and instant REST-like API.  
Features optional authentication (API key, Basic Auth, JWT, OAuth-ready),  
OpenAPI (Swagger) docs, and zero code generation.

---

## 🚀 ## 🚀 Features

- Auto-discovers tables and columns
- Full CRUD endpoints for any table
- Configurable authentication (API Key, Basic Auth, JWT, or none)
- Advanced query features: filtering, sorting, pagination
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


### 🔄 Advanced Query Features (Filtering, Sorting, Pagination)

The `list` action endpoint now supports advanced query parameters:

| Parameter    | Type    | Description                                                                                       |
|--------------|---------|---------------------------------------------------------------------------------------------------|
| `filter`     | string  | Filter rows by column values. Format: `filter=col1:value1,col2:value2`. Use `%` for wildcards.    |
| `sort`       | string  | Sort by columns. Comma-separated. Use `-` prefix for DESC. Example: `sort=-created_at,name`       |
| `page`       | int     | Page number (1-based). Default: `1`                                                               |
| `page_size`  | int     | Number of rows per page (max 100). Default: `20`                                                  |

**Examples:**

- `GET /index.php?action=list&table=users&filter=name:Alice`
- `GET /index.php?action=list&table=users&sort=-created_at,name`
- `GET /index.php?action=list&table=users&page=2&page_size=10`
- `GET /index.php?action=list&table=users&filter=email:%gmail.com&sort=name&page=1&page_size=5`

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