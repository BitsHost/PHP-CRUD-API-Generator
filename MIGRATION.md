# Migration Guide

## Overview
In version `2.0.0-dev` the legacy root wrapper classes were removed. If you are upgrading from `<2.0.0`, update your imports using the mapping below (Rector config provided) before moving to a stable 2.x release.

## Removal Timeline (Historical)
- <2.0.0: Wrappers existed (deprecated) + optional runtime notices via env.
- 2.0.0-dev: Wrappers deleted.
- 2.x stable: Only canonical namespaces supported. (Optional future `legacy-shims` package may provide class_alias for extended migration windows.)

## Environment Flag
Set `API_GEN_DEPRECATIONS` (only relevant when running old versions <2.0.0) to control runtime notices:
- `off` / `0` / (unset): silent
- `1` / `trigger`: `E_USER_DEPRECATED` errors
- `log`: logged with `error_log`

Windows PowerShell example:
```powershell
$env:API_GEN_DEPRECATIONS = "trigger"; php -S localhost:8000
```

## Namespace Mapping
| Old | New |
|-----|-----|
| App\Database | App\Database\Database |
| App\SchemaInspector | App\Database\SchemaInspector |
| App\Authenticator | App\Auth\Authenticator |
| App\RequestLogger | App\Observability\RequestLogger |
| App\Monitor | App\Observability\Monitor |
| App\Rbac | App\Security\Rbac |
| App\RateLimiter | App\Security\RateLimiter |
| App\OpenApiGenerator | App\Docs\OpenApiGenerator |
| App\HookManager | App\Application\HookManager |
| App\Response | App\Http\Response |
| App\Cors | App\Http\Middleware\CorsMiddleware |
| App\Validator | App\Support\Validator |
| App\Controller\LoginController | App\Http\Controllers\LoginController |

## How to Migrate Manually
1. Search for `use App\\` matching the old names.
2. Replace with new canonical namespaces.
3. Run tests / static analysis.

## Automated Migration (Rector)
Install Rector (if not already present):
```bash
composer require --dev rector/rector
```
Run provided config (assuming `rector.php` exists at project root):
```bash
vendor\bin\rector process src --dry-run
vendor\bin\rector process src
```

## Validation
After migration:
- Run `composer dump-autoload -o`
- Run test suite: `vendor\bin\phpunit`
- Optionally enable deprecation notices to ensure no old imports remain.

## FAQ
**Q: Do wrappers add overhead?** Minimal (autoload + class inheritance only).
**Q: Can I suppress all notices?** Yes: unset the env var or set to `off`.
**Q: What if I need more time past v2.0?** Use the optional `legacy-shims` package (if published) or keep a private fork with class_alias definitions.

## Future Removal Script (Example)
If you need to purge on CI:
```bash
grep -R "use App\\Database;" -n src && exit 1 || echo "No legacy imports"
```

## Support
Open an issue for edge cases or suggestions.
