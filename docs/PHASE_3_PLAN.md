# Phase 3: Hardening and Release Prep

Branch: `feature/phase-3-hardening` (based on `refactor-phase-2`)

## Goals
- Stabilize API and internal structure (no feature creep)
- High-confidence release quality: tests + static analysis + style checks
- Updated documentation (user + developer)
- Green CI across PHP 8.0–8.3

## Success Criteria
- Build: Composer autoload OK
- Static analysis: PHPStan level 7 (target 8)
- Style: PHPCS PSR-12 clean
- Tests: PHPUnit passing; coverage ≥ 70% (stretch: 80%+)
- CI: Green on PR (lint + static + tests)
- Docs: Up to date (Quickstart, Config, Endpoints, Security, Caching, Observability)

## Workstream Breakdown

### 1) Tests
- Unit tests for:
  - Http: Response, ErrorResponder, Middleware (CORS, RateLimit)
  - Auth: Authenticator (apikey/basic/jwt + DB auth path), role retrieval
  - Security: Rbac, RbacGuard, RateLimiter
  - Database: SchemaInspector (via mock PDO), Dialects (quoting)
  - ApiGenerator: list/count filters, sort, pagination; CRUD behaviors
  - Observability: RequestLogger, Monitor (metrics, alerts)
  - Docs: OpenApiGenerator minimal spec
- Integration smoke tests for Router (list/read/create/update/delete/openapi/login)

### 2) Static Analysis + Style
- PHPStan config: `phpstan.neon.dist` (level 7 → iterate up)
- PHPCS config: `phpcs.xml.dist` (PSR-12)
- Address critical findings; schedule non-critical fixes post-freeze

### 3) CI Pipeline
- GitHub Actions: `.github/workflows/ci.yml`
  - Matrix: PHP 8.0, 8.1, 8.2, 8.3
  - Steps: composer validate → install → dump-autoload → phpstan → phpcs → phpunit

### 4) Docs Updates
- README: Quickstart with `App\Application\Router` entrypoint
- CONFIG: `config/api.php` and `config/cache.php` options aligned with `ApiConfig`/`CacheConfig`
- Endpoints: Actions, filters, sorting, pagination, bulk
- Security: Auth methods + RBAC usage, examples; rate limit headers
- Observability: RequestLogger/Monitor paths, rotation/cleanup
- Caching: CacheManager TTLs, exclusions, varyBy
- Migration: v2.0.0-dev hard break (wrappers removed; canonical namespaces only)

## Execution Sequence (Suggested)
1) Baseline run (build/static/tests) → capture issues
2) Add/fix unit tests per module → quick iterations
3) Router integration smoke tests
4) Raise PHPStan level and fix high-signal findings
5) CI green across matrix
6) Final docs refresh + examples

## Risk & Mitigation
- DB-dependent tests: prefer mocks for unit; add optional integration profile later
- Platform differences (Windows paths): tests use sys_get_temp_dir() and portable paths
- Flaky tests (timing, rate limit): use deterministic settings in test env

---
Maintainer note: Keep PRs small and focused (tests per module); keep branch scoped to hardening only.
