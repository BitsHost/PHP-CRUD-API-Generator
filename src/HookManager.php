<?php
namespace App;

class HookManager
{
    protected array $hooks = [
        'before' => [],
        'after' => [],
    ];

    /**
     * Register a callback for a specific action and timing.
     * 
     * @param string $action E.g. "create", "read", "update", "delete", or "*" for all
     * @param callable $callback
     * @param string $when "before" or "after"
     */
    public function registerHook(string $action, callable $callback, string $when = 'before'): void
    {
        if (!in_array($when, ['before', 'after'])) {
            throw new \InvalidArgumentException("Invalid hook timing: $when");
        }
        $this->hooks[$when][$action][] = $callback;
    }

    /**
     * Run hooks for a given action/timing.
     * 
     * @param string $when "before" or "after"
     * @param string $action
     * @param array $context Data passed to hooks (by reference)
     */
    public function runHooks(string $when, string $action, array &$context = []): void
    {
        foreach ([$action, '*'] as $key) {
            if (!empty($this->hooks[$when][$key])) {
                foreach ($this->hooks[$when][$key] as $callback) {
                    $callback($context);
                }
            }
        }
    }
}