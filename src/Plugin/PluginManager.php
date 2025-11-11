<?php
namespace App\Plugin;

use App\HookManager;

/**
 * PluginManager
 * 
 * Discovers and loads plugins from /plugins directory.
 * Provides lifecycle coordination and exposes plugin-provided
 * actions & permissions to the core system.
 */
class PluginManager
{
    private string $pluginDir;
    private HookManager $hooks;
    /** @var PluginInterface[] */
    private array $plugins = [];
    /** @var array<string, callable> */
    private array $customActions = [];
    /** @var array<string, array<string, string[]>> */
    private array $permissions = [];

    public function __construct(string $pluginDir, HookManager $hooks)
    {
        $this->pluginDir = rtrim($pluginDir, DIRECTORY_SEPARATOR);
        $this->hooks = $hooks;
    }

    /** Discover plugin classes using simple convention: plugins/<name>/Plugin.php returning instance implementing PluginInterface */
    public function discover(): void
    {
        if (!is_dir($this->pluginDir)) {
            return;
        }
        foreach (glob($this->pluginDir . '/*/Plugin.php') as $file) {
            // Autoload via PSR-4 (Plugins\<DirName>\Plugin)
            $dirName = basename(dirname($file));
            $class = 'Plugins\\' . $dirName . '\\Plugin';
            if (class_exists($class)) {
                $instance = new $class();
                if ($instance instanceof PluginInterface) {
                    $this->plugins[$instance->getName()] = $instance;
                }
            }
        }
    }

    /** Resolve dependency order (simple topological sort). */
    private function resolveLoadOrder(): array
    {
        $resolved = [];
        $temp = [];
        $plugins = $this->plugins;

        $visit = function(PluginInterface $p) use (&$visit, &$resolved, &$temp, $plugins) {
            $name = $p->getName();
            if (isset($resolved[$name])) return;
            if (isset($temp[$name])) {
                throw new \RuntimeException("Circular plugin dependency involving $name");
            }
            $temp[$name] = true;
            foreach ($p->getDependencies() as $dep) {
                if (!isset($plugins[$dep])) {
                    throw new \RuntimeException("Missing dependency '$dep' required by plugin '{$p->getName()}'");
                }
                $visit($plugins[$dep]);
            }
            $resolved[$name] = $p;
        };

        foreach ($plugins as $p) {
            $visit($p);
        }
        return array_values($resolved);
    }

    /** Load all plugins: discover -> dependency order -> register -> boot */
    public function loadAll(): void
    {
        $this->discover();
        $ordered = $this->resolveLoadOrder();
        foreach ($ordered as $plugin) {
            $registrar = new PluginRegistrar($this->hooks);
            $plugin->register($registrar);
            // Collect actions & permissions
            foreach ($registrar->getActions() as $action => $handler) {
                $this->customActions[$action] = $handler;
            }
            foreach ($registrar->getPermissions() as $table => $roles) {
                foreach ($roles as $role => $acts) {
                    $this->permissions[$table][$role] = array_unique(array_merge($this->permissions[$table][$role] ?? [], $acts));
                }
            }
        }
        // Boot after all registered
        foreach ($ordered as $plugin) {
            $plugin->boot();
        }
    }

    /** Install all plugins (idempotent). */
    public function installAll(): void
    {
        foreach ($this->plugins as $plugin) {
            $plugin->install();
        }
    }

    /** Expose collected custom actions to Router (later integration). */
    public function getCustomActions(): array
    {
        return $this->customActions;
    }

    /** Expose plugin-declared permissions for merging with RBAC config. */
    public function getPermissions(): array
    {
        return $this->permissions;
    }
}
