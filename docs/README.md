# Documentation index

Developer docs for **PHP CRUD API Generator** (v2.1+).

This package is a **data plane**: expose MySQL/MariaDB tables as CRUD/bulk/filter endpoints.  
Compose related data and business workflows **outside** the API (JS / app / upMVC). Not GraphQL.

---

## Start here

| Doc | Purpose |
|-----|---------|
| [../README.md](../README.md) | Overview, install, endpoints |
| [QUICK_START.md](QUICK_START.md) | 5-minute setup |
| [UPGRADE_2.1.md](UPGRADE_2.1.md) | Migrating from ≤2.0.1 |
| [CLIENT_SIDE_JOINS.md](CLIENT_SIDE_JOINS.md) | Related data in the client |
| [../CHANGELOG.md](../CHANGELOG.md) | Release history |

---

## Configuration & security

| Doc | Purpose |
|-----|---------|
| [CONFIGURATION.md](CONFIGURATION.md) | Config architecture (`ApiConfig`, paths) |
| [CONFIG_FLOW.md](CONFIG_FLOW.md) | How config is loaded |
| [AUTHENTICATION.md](AUTHENTICATION.md) | API key / Basic / JWT / RBAC |
| [AUTH_QUICK_REFERENCE.md](AUTH_QUICK_REFERENCE.md) | Short auth cheat sheet |
| [DASHBOARD_SECURITY.md](DASHBOARD_SECURITY.md) | Lock down dashboard & health |
| [../SECURITY.md](../SECURITY.md) | Security policy |
| [USER_MANAGEMENT.md](USER_MANAGEMENT.md) | DB users |
| [QUICK_START_USERS.md](QUICK_START_USERS.md) | User setup |

---

## Runtime features

| Doc | Purpose |
|-----|---------|
| [RATE_LIMITING.md](RATE_LIMITING.md) | Rate limits |
| [REQUEST_LOGGING_IMPLEMENTATION.md](REQUEST_LOGGING_IMPLEMENTATION.md) | Request logging |
| [MONITORING.md](MONITORING.md) | Monitoring |
| [MONITORING_QUICKSTART.md](MONITORING_QUICKSTART.md) | Monitoring quick start |
| [CACHING_IMPLEMENTATION.md](CACHING_IMPLEMENTATION.md) | Caching (file; Redis stub) |

---

## Product context

| Doc | Purpose |
|-----|---------|
| [COMPARISON.md](COMPARISON.md) | vs php-crud-api v2 |
| [ROADMAP.md](ROADMAP.md) | Future ideas |
| [JWT_EXPLAINED.md](JWT_EXPLAINED.md) | JWT deep dive |

---

## Scripts (package)

```bash
php scripts/install-config.php .   # copy example configs into project
php scripts/doctor.php             # warn on weak/prod-unsafe settings
```

---

## Note on older docs

Some files under `docs/` are historical implementation reports (PHPDoc/monitoring phase notes). Prefer the tables above for day-to-day development.
