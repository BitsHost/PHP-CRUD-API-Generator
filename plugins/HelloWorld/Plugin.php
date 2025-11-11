<?php
namespace Plugins\HelloWorld;

use App\Plugin\PluginInterface;
use App\Plugin\PluginRegistrar;

class Plugin implements PluginInterface
{
    public function getName(): string { return 'hello_world'; }
    public function getDisplayName(): string { return 'Hello World Plugin'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getDependencies(): array { return []; }

    public function register(PluginRegistrar $registrar): void
    {
        // Add a BEFORE hook for all actions to stamp a timestamp
        $registrar->onBefore('*', function(array &$ctx) {
            $ctx['hello_world_before'] = microtime(true);
        });

        // Add an AFTER hook on create to append plugin info to result
        $registrar->onAfter('create', function(array &$ctx) {
            $ctx['plugin_meta']['hello_world'] = 'created';
        });

        // Register a custom action (to be wired in router later)
        $registrar->registerAction('hello', function(array $query) {
            return ['message' => 'Hello from plugin', 'query' => $query];
        });
    }

    public function boot(): void {}
    public function install(): void {}
    public function uninstall(): void {}
}
