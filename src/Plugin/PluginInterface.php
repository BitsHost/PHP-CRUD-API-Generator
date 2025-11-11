<?php
namespace App\Plugin;

/**
 * PluginInterface
 * 
 * Defines the lifecycle contract that all plugins must implement.
 * Plugins extend the API generator by registering routes, CRUD hooks,
 * permissions, and performing install/uninstall tasks like migrations.
 * 
 * Lifecycle:
 * - register(): Called early to declare hooks, routes, permissions.
 * - boot(): Called after all plugins registered; can resolve cross-plugin deps.
 * - install(): One-time setup (migrations, seed data). Should be idempotent.
 * - uninstall(): Cleanup (drop tables, remove data) - must be explicit & safe.
 */
interface PluginInterface
{
    /** Return unique machine name (e.g. 'hello_world'). */
    public function getName(): string;
    /** Human readable display name. */
    public function getDisplayName(): string;
    /** Semantic version (e.g. 1.0.0). */
    public function getVersion(): string;
    /** Optional list of plugin names this plugin depends on. */
    public function getDependencies(): array;
    /** Register routes, hooks, permissions with managers. */
    public function register(PluginRegistrar $registrar): void;
    /** Boot after all plugins registered. */
    public function boot(): void;
    /** Install one-time resources. */
    public function install(): void;
    /** Uninstall cleanup (dangerous operations must confirm). */
    public function uninstall(): void;
}
