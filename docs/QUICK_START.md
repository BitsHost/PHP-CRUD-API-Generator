# Quick Start (v2.1+)

Get a working API in a few minutes. **Do not edit files under `vendor/`.**

---

## Library install (recommended)

```bash
composer require bitshost/php-crud-api-generator

# Entrypoint (+ optional ops UI)
copy vendor\bitshost\php-crud-api-generator\public\index.php index.php
copy vendor\bitshost\php-crud-api-generator\dashboard.html dashboard.html
copy vendor\bitshost\php-crud-api-generator\health.php health.php

# Project-local configs (skips files that already exist)
php vendor\bitshost\php-crud-api-generator\scripts\install-config.php .

# Edit YOUR project config
notepad config\db.php
notepad config\api.php

# Sanity check
php vendor\bitshost\php-crud-api-generator\scripts\doctor.php

php -S localhost:8000
```

Linux/macOS: use `cp` instead of `copy`.

---

## Standalone project

```bash
composer create-project bitshost/php-crud-api-generator my-api
cd my-api

php scripts/install-config.php .
# edit config/db.php and config/api.php
php scripts/doctor.php

php -S localhost:8000 -t public
```

---

## Minimal config checklist

In `config/db.php`: host, dbname, user, pass.

In `config/api.php` (production):

- `auth_enabled` => `true`
- strong `jwt_secret` / `api_keys`
- set `allowed_tables` to an explicit list of business tables
- keep `denied_tables` for system tables (`api_users`, …)
- protect `dashboard.html` / `health.php` — see [DASHBOARD_SECURITY.md](DASHBOARD_SECURITY.md)

---

## Smoke test

```bash
# With Basic auth (default example method may vary — check your api.php)
curl -u admin:secret "http://localhost:8000/index.php?action=tables"

# List rows
curl -u admin:secret "http://localhost:8000/index.php?action=list&table=users"

# Delete must be POST
curl -u admin:secret -X POST "http://localhost:8000/index.php?action=delete&table=users&id=1"
```

---

## Design model (important)

This API is a **data plane**: CRUD + bulk + filters on tables.

Related data / business workflows are composed **outside** (JavaScript, mobile app, upMVC).  
See [CLIENT_SIDE_JOINS.md](CLIENT_SIDE_JOINS.md).

---

## Next reading

- [UPGRADE_2.1.md](UPGRADE_2.1.md) — if you used ≤2.0.1
- [AUTHENTICATION.md](AUTHENTICATION.md)
- [CONFIGURATION.md](CONFIGURATION.md)
- Main [README](../README.md)
