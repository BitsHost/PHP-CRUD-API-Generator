# Plugin System (Experimental)

> Status: Experimental scaffold. Interfaces may change before 2.x stable.

## Goals
Extend the API safely without modifying core files:
- Register CRUD hooks (before/after create/read/update/delete)
- Add custom actions (e.g. `action=hello`)
- Contribute permissions (RBAC entries)
- Perform install/uninstall tasks (migrations, seeds)
- Remain isolated, versioned, and dependency aware

## Directory Layout
```
plugins/
  HelloWorld/
    plugin.json
    Plugin.php          # Implements Plugins\\HelloWorld\\Plugin (PluginInterface)
```

## Autoloading
Composer maps `Plugins\\` => `plugins/` in `composer.json`. Each plugin lives in its own subfolder and defines a `Plugin` class.

## Manifest: `plugin.json`
```json
{
  "name": "hello_world",
  "display_name": "Hello World Plugin",
  "version": "1.0.0",
  "class": "Plugins\\HelloWorld\\Plugin",
  "enabled": true,
  "dependencies": []
}
```
Fields:
- name: unique slug
- display_name: human title
- version: semantic version
- class: fully qualified class
- enabled: loader may skip if false
- dependencies: array of required plugin slugs

## Lifecycle Interface
`PluginInterface` methods:
- getName(), getDisplayName(), getVersion(), getDependencies()
- register(PluginRegistrar $registrar)
- boot(): post-registration wiring
- install(): idempotent setup
- uninstall(): cleanup

## Hooks
Use `onBefore(action, callback)` or `onAfter(action, callback)` with `action` in:
`create, read, update, delete, list, count, bulk_create, bulk_delete, *`
Context is an associative array passed by reference; mutate to affect downstream logic.

Example:
```php
$registrar->onBefore('create', function(array &$ctx){
  if ($ctx['table'] === 'users' && isset($ctx['data']['password'])) {
    $ctx['data']['password'] = password_hash($ctx['data']['password'], PASSWORD_DEFAULT);
  }
});
```

## Custom Actions
Register an action name and handler:
```php
$registrar->registerAction('hello', fn(array $query) => ['message' => 'Hello']);
```
Router integration (future) will check plugin actions before core dispatch.

## Permissions Contribution
Plugins can inject RBAC permissions:
```php
$registrar->registerPermission('orders', 'admin', ['create','update','delete']);
```
Core will merge these into existing role config when PluginManager is integrated.

## Dependency Resolution
`PluginManager` performs a topological sort over `getDependencies()`. Cycles throw an exception; missing dependencies abort load.

## Installation & Uninstallation
Keep these idempotent and safe:
- Use existence checks before creating tables
- Avoid destructive operations unless explicitly confirmed by a higher-level orchestrator

## Security Guidelines
- Never trust user input; validate inside hooks
- Avoid leaking secrets via custom actions
- Namespaces isolate classes; do not reference internal, non-public core classes directly
- Keep plugin manifests signed (future enhancement)

## Versioning & Compatibility
- Follow SemVer: MAJOR.MINOR.PATCH
- Increase MAJOR if breaking `PluginInterface`/registrar contract
- Use dependencies to enforce minimum versions of other plugins

## Distribution
Initial approach: zip folder or separate composer package (map `Plugins\\YourPlugin\\`). Future: centralized registry.

## Future Roadmap
- Router integration for custom actions
- Dynamic enable/disable (runtime cache)
- Migrations helper & rollback manager
- Signed manifests & integrity hash
- Plugin configuration injection
- Web UI for plugin management & status

## Example Quick Start
1. Create `plugins/MyPlugin/Plugin.php` implementing `PluginInterface`
2. Add `plugin.json`
3. (Future) Call `$pluginManager->loadAll()` early in bootstrap
4. Use your new hooks / actions

```php
class Plugin implements PluginInterface {
  public function getName(): string { return 'my_plugin'; }
  public function getDisplayName(): string { return 'My Plugin'; }
  public function getVersion(): string { return '0.1.0'; }
  public function getDependencies(): array { return []; }
  public function register(PluginRegistrar $r): void {
    $r->onAfter('create', function(array &$ctx){ error_log('Created row in '.$ctx['table']); });
    $r->registerAction('ping', fn()=>['pong'=>true]);
  }
  public function boot(): void {}
  public function install(): void {}
  public function uninstall(): void {}
}
```

## Disclaimer
This is an experimental foundation. APIs may evolve; avoid producing many public plugins until stabilized.
