<?php

class PluginLoader {
    private $plugins = [];

    public function load($plugin) {
        // Assume plugins are located in the plugins directory
        $pluginPath = __DIR__ . '/plugins/' . $plugin . '.php';
        if (file_exists($pluginPath)) {
            include $pluginPath;
            $this->plugins[] = $plugin;
        } else {
            throw new Exception('Plugin not found: ' . $plugin);
        }
    }

    public function getPlugins() {
        return $this->plugins;
    }
}