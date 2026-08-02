# PHP CRUD API Generator

Expose your MySQL/MariaDB database as a secure, flexible, REST-like **data API**.  
Auth (API key, Basic, JWT), OpenAPI docs, rate limiting, logging — **zero code generation**.

**Version:** 2.1.0 · [Changelog](CHANGELOG.md) · [Docs index](docs/README.md) · [Upgrade from ≤2.0.1](docs/UPGRADE_2.1.md)

---

## Design model (read this first)

This package is intentionally a **data plane**, not an application server and **not GraphQL**.

| In this API | Outside (your app / JS) |
|-------------|-------------------------|
| list / read / create / update / delete | Joins & related graphs |
| bulk_create / bulk_delete | Multi-step business workflows |
| filter / sort / paginate / count | UI, domain rules, orchestration |
| auth, RBAC, table allow/deny | Compose responses in **JavaScript** (or upMVC / mobile) |

Fetch tables separately (or with `filter=…:in:…`), combine on the client.  
Guide: **[Client-side joins](docs/CLIENT_SIDE_JOINS.md)**.

---

## Features

- Auto-discovers tables and columns
- Full CRUD + **bulk** create/delete
- Auth: API Key, Basic, JWT (`oauth` reserved — **not implemented**)
- **Table exposure policy** — `allowed_tables` / `denied_tables`
- Rate limiting, request logging, optional monitoring
- Filters: `eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `like`, `in`, `notin`, `null`, `notnull`, `between`
- Field selection, multi-column sort, pagination meta
- Per-table RBAC (including filtered `action=tables`)
- OpenAPI JSON endpoint
- PSR-4 codebase, PHPUnit + PHPStan + CI

**Docs:** [Quick start](docs/QUICK_START.md) · [Auth](docs/AUTHENTICATION.md) · [Configuration](docs/CONFIGURATION.md) · [Rate limiting](docs/RATE_LIMITING.md) · [Logging](docs/REQUEST_LOGGING_IMPLEMENTATION.md) · [upMVC integration](https://github.com/upMVC/upMVC/blob/main/docs/INTEGRATION_PHP_CRUD_API.md) · [Comparison](docs/COMPARISON.md) · [Roadmap](docs/ROADMAP.md)

---

## Security warning

**Critical:** `dashboard.html` and `health.php` expose ops/security metrics. Protect them before production.

→ [Dashboard security](docs/DASHBOARD_SECURITY.md) · run `php scripts/doctor.php`

```apache
<Files "dashboard.html">
  Require ip 127.0.0.1 ::1
</Files>
```

---

## Installation

### Option 1: Library (recommended)

```bash
composer require bitshost/php-crud-api-generator

copy vendor\bitshost\php-crud-api-generator\public\index.php index.php
copy vendor\bitshost\php-crud-api-generator\dashboard.html dashboard.html
copy vendor\bitshost\php-crud-api-generator\health.php health.php

# Project-local configs — do not edit vendor/
php vendor\bitshost\php-crud-api-generator\scripts\install-config.php .

notepad config\db.php
notepad config\api.php
php vendor\bitshost\php-crud-api-generator\scripts\doctor.php
php -S localhost:8000
```

Configs load from `./config` or `PHPCRUD_CONFIG_DIR`.

### Option 2: Standalone

```bash
composer create-project bitshost/php-crud-api-generator my-api
cd my-api
php scripts/install-config.php .
notepad config/db.php
notepad config/api.php
php scripts/doctor.php
php -S localhost:8000 -t public
```

---

## Configuration

```bash
php scripts/install-config.php .
php scripts/doctor.php
```

Older guides said to edit `vendor/.../config` — that is obsolete.

### `config/db.php`

```php
return [
    'host' => 'localhost',
    'dbname' => 'your_database',
    'user' => 'your_db_user',
    'pass' => 'your_db_password',
    'charset' => 'utf8mb4',
];
```

### `config/api.php` (shape)

```php
return [
    'auth_enabled' => true,
    'auth_method' => 'jwt', // apikey | basic | jwt  (oauth = not implemented)
    'api_keys' => ['use-long-random-keys'],
    'api_key_role' => 'readonly',
    'basic_users' => ['admin' => 'change-me'],
    'jwt_secret' => 'generate-with-random_bytes-32+',
    'jwt_issuer' => 'yourdomain.com',
    'jwt_audience' => 'yourdomain.com',

    // Empty allowlist = all except denied. Non-empty = whitelist only.
    'allowed_tables' => [], // e.g. ['users', 'orders']
    'denied_tables' => ['api_users', 'api_key_usage'],

    'rate_limit' => [
        'enabled' => true,
        'max_requests' => 100,
        'window_seconds' => 60,
    ],
    'logging' => [
        'enabled' => true,
        'log_dir' => __DIR__ . '/../logs',
        'log_level' => 'info',
    ],
];
```

Full RBAC examples: `config/apiexample.php`. Details: [CONFIGURATION.md](docs/CONFIGURATION.md).

### Environment (`.env`)

Copy `.env.example` → `.env`. Overrides include:

`DB_*`, `API_AUTH_METHOD`, `API_KEYS`, `BASIC_*_PASSWORD`, `JWT_*`,  
`API_ALLOWED_TABLES`, `API_DENIED_TABLES` (comma-separated).

---

## Production checklist

- [ ] Strong `jwt_secret` / `api_keys` (not examples)
- [ ] `auth_enabled` => true
- [ ] Explicit `allowed_tables`
- [ ] Dashboard/health locked down
- [ ] `php scripts/doctor.php` clean enough for your threat model
- [ ] DB user least privilege

[AUTHENTICATION.md](docs/AUTHENTICATION.md) · [SECURITY.md](SECURITY.md)

---

## Authentication modes

| Mode | Config | Client |
|------|--------|--------|
| Off | `auth_enabled => false` | — (not for public internet) |
| API Key | `auth_method => apikey` | `X-API-Key` header (prefer over query string) |
| Basic | `auth_method => basic` | HTTP Basic |
| JWT | `auth_method => jwt` | `POST ?action=login` then `Authorization: Bearer …` |
| OAuth | — | **Not implemented** (always denies) |

---

## API endpoints

Entry: `index.php` or `public/index.php` + `action=`.

| Action | Method | Example |
|--------|--------|---------|
| tables | GET | `?action=tables` |
| columns | GET | `?action=columns&table=users` |
| list | GET | `?action=list&table=users` |
| count | GET | `?action=count&table=users` |
| read | GET | `?action=read&table=users&id=1` |
| create | POST | `?action=create&table=users` |
| update | POST | `?action=update&table=users&id=1` |
| delete | **POST** | `?action=delete&table=users&id=1` |
| bulk_create | POST | `?action=bulk_create&table=users` |
| bulk_delete | POST | `?action=bulk_delete&table=users` |
| openapi | GET | `?action=openapi` |
| login | POST | `?action=login` (JWT) |

---

## Example `curl`

```sh
curl -u admin:secret "http://localhost:8000/index.php?action=tables"

curl -H "X-API-Key: YOUR_KEY" \
  "http://localhost:8000/index.php?action=list&table=users"

curl -X POST -d "username=admin&password=secret" \
  "http://localhost:8000/index.php?action=login"

curl -H "Authorization: Bearer <token>" \
  "http://localhost:8000/index.php?action=list&table=users"

# Delete requires POST
curl -u admin:secret -X POST \
  "http://localhost:8000/index.php?action=delete&table=users&id=1"

curl -X POST -H "Content-Type: application/json" \
  -d '[{"name":"Alice","email":"alice@example.com"}]' \
  "http://localhost:8000/index.php?action=bulk_create&table=users"

curl -X POST -H "Content-Type: application/json" \
  -d '{"ids":[1,2,3]}' \
  "http://localhost:8000/index.php?action=bulk_delete&table=users"
```

---

## Bulk operations

### Bulk create

`POST ?action=bulk_create&table=users` — JSON array. Transactional (rollback on failure).

```json
[
  {"name": "Alice", "email": "alice@example.com"},
  {"name": "Bob", "email": "bob@example.com"}
]
```

### Bulk delete

`POST ?action=bulk_delete&table=users`

```json
{ "ids": [1, 2, 3] }
```

---

## Count

`GET ?action=count&table=users&filter=status:eq:active`

```json
{ "count": 42 }
```

---

## Query features (`list`)

| Param | Description |
|-------|-------------|
| `filter` | `col:op:value` or `col:value`; comma-separated AND |
| `sort` | Comma-separated; `-col` = DESC |
| `page` | 1-based (default 1) |
| `page_size` | Default 20, max 100 |
| `fields` | Comma-separated columns |

### Filter operators

| Op | Example |
|----|---------|
| `eq` / bare | `name:eq:Alice` or `name:Alice` |
| `neq` / `ne` | `status:neq:deleted` |
| `gt` `gte` `lt` `lte` | `age:gt:18` |
| `like` | `email:like:%@gmail.com` |
| `in` / `notin` | `status:in:active\|pending` |
| `null` / `notnull` | `deleted_at:null:` |
| `between` | `age:between:18\|65` |

Response shape:

```json
{
  "data": [ ... ],
  "meta": { "total": 47, "page": 2, "page_size": 10, "pages": 5 }
}
```

---

## OpenAPI

```bash
curl "http://localhost:8000/index.php?action=openapi"
```

Paste into https://editor.swagger.io/ or use `dashboard.html`.

---

## Related data (client-side)

```javascript
const base = '/index.php';

const user = await fetch(`${base}?action=read&table=users&id=123`).then(r => r.json());
const posts = await fetch(`${base}?action=list&table=posts&filter=user_id:eq:123`).then(r => r.json());

const userData = { ...user, posts: posts.data };

// Batch related rows
const comments = await fetch(
  `${base}?action=list&table=comments&filter=post_id:in:1|2|3|4|5`
).then(r => r.json());

// Parallel
const [u, p, c] = await Promise.all([
  fetch(`${base}?action=read&table=users&id=123`).then(r => r.json()),
  fetch(`${base}?action=list&table=posts&filter=user_id:eq:123`).then(r => r.json()),
  fetch(`${base}?action=list&table=comments&filter=user_id:eq:123`).then(r => r.json()),
]);
```

More: [CLIENT_SIDE_JOINS.md](docs/CLIENT_SIDE_JOINS.md).

**Why not GraphQL / server joins by default?** Another abstraction layer. With CRUD + filters you can move any data; composition stays in the client where each app can differ.

---

## Security notes

- Auth + rate limit + logging for any shared/public deploy
- Never commit real secrets — use `.gitignore` + examples + `.env`
- Inputs validated; queries parameterized; RBAC + table policy before data access
- Secrets redacted from logs

[Rate limiting](docs/RATE_LIMITING.md) · [Logging](docs/REQUEST_LOGGING_IMPLEMENTATION.md)

---

## Tests

```bash
composer install
./vendor/bin/phpunit
./vendor/bin/phpstan analyse
php scripts/doctor.php
```

---

## Roadmap

- Client-side joins — **current model**
- Optional expand/auto-join — only if demand
- OAuth/SSO — if targeting that product shape
- More DB drivers — incremental
- See [ROADMAP.md](docs/ROADMAP.md)

---

## License

MIT — see [LICENSE](LICENSE).
