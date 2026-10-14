# Upgrade to v2.2.0 — Optional multitenancy

## Compatibility

- **Default unchanged:** `multitenancy.enabled => false` — simple apps behave like v2.1.
- No breaking URL/action changes.

## Enable SaaS-style CRUD scoping

1. Ensure tenant-scoped tables have a column (default `tenant_id`) + index.
2. In `config/api.php`:

```php
'multitenancy' => [
    'enabled' => true,
    'column' => 'tenant_id',
    'source' => 'jwt',           // preferred
    'claim' => 'tenant_id',
    'enforce_on_write' => true,
    'require_column' => true,
    'tables' => ['cars', 'locations', 'settings'], // or ['*']
    'basic_user_tenants' => ['admin' => '1'],
    'api_key_tenant' => null,    // set if using apikey auth
],
```

3. Prefer **JWT**: login token must include `tenant_id` (from `api_users.tenant_id` or `basic_user_tenants`).
4. Run `php scripts/doctor.php`.
5. (Local only) Optional demo rows: `php scripts/seed-mt-demo.php` — seeds locations/cars/extras/… for tenants 1–3.

## Security rules

- Tenant is enforced **on the server** in SQL — do not rely on JS filtering.
- Client `filter=tenant_id:…` is stripped; writes overwrite the tenant column.
- `source=header` only behind a trusted gateway.

## Hybrid apps (e.g. car-rental SaaS)

Use the generator for allowlisted CRUD tables; keep payments/reservations/marketplace as custom endpoints. Share the same JWT so both surfaces see the same tenant.

See [CONFIGURATION.md](CONFIGURATION.md) and the main [README](../README.md).
