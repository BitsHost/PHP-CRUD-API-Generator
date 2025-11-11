<?php
namespace App\Plugin;

use App\HookManager;

/**
 * PluginRegistrar
 * 
 * Helper passed to plugins at registration time so they can declare
 * hooks, actions and capabilities without directly touching core.
 */
class PluginRegistrar
{
    private HookManager $hooks;
    /** @var array<string, callable> */
    private array $actions = [];
    /** @var array<string, array<string, string[]>> table => role => actions */
    private array $permissions = [];

    public function __construct(HookManager $hooks)
    {
        $this->hooks = $hooks;
    }

    // ---- Hooks API (wraps HookManager) ----
    public function onBefore(string $action, callable $callback): void
    {
        $this->hooks->registerHook($action, $callback, 'before');
    }

    public function onAfter(string $action, callable $callback): void
    {
        $this->hooks->registerHook($action, $callback, 'after');
    }

    // ---- Custom Actions (handled by Router via PluginManager, later wiring) ----
    public function registerAction(string $action, callable $handler): void
    {
        $this->actions[$action] = $handler;
    }

    /**
     * Declare permissions this plugin needs. Example:
     *   registerPermission('orders', 'admin', ['create','update']);
     */
    public function registerPermission(string $table, string $role, array $actions): void
    {
        $this->permissions[$table][$role] = $actions;
    }

    /** @return array<string, callable> */
    public function getActions(): array
    {
        return $this->actions;
    }

    /** @return array<string, array<string, string[]>> */
    public function getPermissions(): array
    {
        return $this->permissions;
    }
}
