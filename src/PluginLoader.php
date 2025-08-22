<?php
namespace App;

interface PluginInterface
{
    public function register(HookManager $hooks): void;
}

class PluginLoader
{
    /**
     * Loads all plugins from the plugins directory and registers them.
     *
     * @param HookManager $hooks
     * @param string $pluginDir
     */
    public function loadPlugins(HookManager $hooks, string $pluginDir = __DIR__ . '/../plugins'): void
    {
        foreach (glob($pluginDir . '/*.php') as $file) {
            require_once $file;
            $className = $this->getClassNameFromFile($file);

            if ($className && class_exists($className) && in_array(PluginInterface::class, class_implements($className))) {
                $plugin = new $className();
                $plugin->register($hooks);
            }
        }
    }

    /**
     * Attempts to derive the fully-qualified class name from the file.
     * You may customize this for your plugin namespace.
     */
    private function getClassNameFromFile($file): ?string
    {
        // Assumes plugins are in the global Plugins namespace
        $basename = basename($file, '.php');
        return "Plugins\\$basename";
    }
}
