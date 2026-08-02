# Upgrade to v2.1.0

For developers moving from **≤2.0.1** to **2.1.0**.

---

## Behavior changes

| Area | Before | After (2.1) |
|------|--------|-------------|
| `action=delete` | Often worked with GET | **POST only** (405 otherwise) |
| `action=tables` | Full schema list | Filtered by **RBAC** + table policy; 403 if auth on and no role |
| Table exposure | Entire DB (minus RBAC) | `allowed_tables` / `denied_tables` |
| API key default role | Often `admin` in examples | Prefer **`readonly`** |
| OAuth `auth_method` | Documented as ready-ish | **Not implemented** (always denies) |
| `firebase/php-jwt` | ^6 | **^7** (Composer advisory) |

---

## Install / config changes

**Stop editing `vendor/.../config`.**

1. Run:
   ```bash
   php vendor/bitshost/php-crud-api-generator/scripts/install-config.php .
   ```
2. Keep secrets in **project** `./config` (or `PHPCRUD_CONFIG_DIR`).
3. Run:
   ```bash
   php vendor/bitshost/php-crud-api-generator/scripts/doctor.php
   ```

The entrypoint resolves config via `App\Config\ConfigPaths`.

---

## Recommended production settings

```php
'auth_enabled' => true,
'allowed_tables' => ['users', 'orders', 'products'], // your business tables
'denied_tables' => ['api_users', 'api_key_usage'],
'api_key_role' => 'readonly', // unless machine clients must write
```

Protect `dashboard.html` and `health.php` — see [DASHBOARD_SECURITY.md](DASHBOARD_SECURITY.md).

---

## Client code checklist

- [ ] All deletes use **POST**
- [ ] API keys / JWT secrets rotated off example values
- [ ] Related data still composed client-side ([CLIENT_SIDE_JOINS.md](CLIENT_SIDE_JOINS.md))
- [ ] Filter operator `between` available as `col:between:min|max`

---

## Verify

```bash
composer update bitshost/php-crud-api-generator
php vendor/bin/phpunit   # if developing the package
curl -X POST "...?action=delete&table=...&id=1"   # expect success path with auth
curl "...?action=delete&table=...&id=1"           # expect 405
```

Full notes: [CHANGELOG.md](../CHANGELOG.md).
