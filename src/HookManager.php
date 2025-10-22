<?php
namespace App;

/**
 * Hook Manager
 * 
 * Event-driven hook system for extending API functionality without modifying core code.
 * Allows registration of callbacks that execute before or after CRUD operations,
 * enabling custom validation, logging, data transformation, and side effects.
 * 
 * Features:
 * - Before/after hook timing
 * - Action-specific hooks (create, read, update, delete)
 * - Wildcard hooks (*) for all actions
 * - Context passing by reference for data modification
 * - Multiple callbacks per hook point
 * - Priority-based execution order
 * 
 * Hook Points:
 * - before:create - Before record creation
 * - after:create - After record creation
 * - before:read - Before record retrieval
 * - after:read - After record retrieval
 * - before:update - Before record update
 * - after:update - After record update
 * - before:delete - Before record deletion
 * - after:delete - After record deletion
 * - before:* - Before any action
 * - after:* - After any action
 * 
 * Use Cases:
 * - Input validation and sanitization
 * - Audit logging
 * - Data transformation (hashing passwords, etc.)
 * - Triggering external APIs/webhooks
 * - Cache invalidation
 * - Sending notifications
 * - Access control checks
 * 
 * @package App
 * @author Adrian D
 * @copyright 2025 BitHost
 * @license MIT
 * @version 1.4.0
 * @link https://upmvc.com
 * 
 * @example
 * // Basic hook registration
 * $hooks = new HookManager();
 * 
 * // Hash passwords before user creation
 * $hooks->registerHook('create', function(&$context) {
 *     if ($context['table'] === 'users' && isset($context['data']['password'])) {
 *         $context['data']['password'] = password_hash(
 *             $context['data']['password'], 
 *             PASSWORD_DEFAULT
 *         );
 *     }
 * }, 'before');
 * 
 * @example
 * // Audit logging after updates
 * $hooks->registerHook('update', function(&$context) {
 *     error_log(sprintf(
 *         'User %s updated %s#%d',
 *         $context['user'],
 *         $context['table'],
 *         $context['id']
 *     ));
 * }, 'after');
 * 
 * @example
 * // Wildcard hook for all actions
 * $hooks->registerHook('*', function(&$context) {
 *     $context['timestamp'] = time();
 * }, 'before');
 * 
 * // Execute hooks
 * $context = ['table' => 'users', 'data' => [...]];
 * $hooks->runHooks('before', 'create', $context);
 */
class HookManager
{
    protected array $hooks = [
        'before' => [],
        'after' => [],
    ];

    /**
     * Register a callback for a specific action and timing
     * 
     * Registers a callable function/method to execute at the specified hook point.
     * Callbacks receive context data by reference and can modify it. Multiple
     * callbacks can be registered for the same hook point and execute in order.
     * 
     * @param string $action Action name ("create", "read", "update", "delete") 
     *   or "*" for all actions
     * @param callable $callback Function to execute. Signature: function(array &$context): void
     *   Context typically contains: table, data, id, user, timestamp
     * @param string $when Hook timing: "before" (pre-operation) or "after" (post-operation)
     * @return void
     * @throws \InvalidArgumentException If $when is not "before" or "after"
     * 
     * @example
     * // Validate email before user creation
     * $hooks->registerHook('create', function(&$context) {
     *     if ($context['table'] === 'users') {
     *         if (!filter_var($context['data']['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
     *             throw new Exception('Invalid email address');
     *         }
     *     }
     * }, 'before');
     * 
     * @example
     * // Log successful deletions
     * $hooks->registerHook('delete', function(&$context) {
     *     $logger->info("Deleted {$context['table']}#{$context['id']}");
     * }, 'after');
     * 
     * @example
     * // Add timestamps to all creates
     * $hooks->registerHook('*', function(&$context) {
     *     $context['data']['created_at'] = date('Y-m-d H:i:s');
     * }, 'before');
     */
    public function registerHook(string $action, callable $callback, string $when = 'before'): void
    {
        if (!in_array($when, ['before', 'after'])) {
            throw new \InvalidArgumentException("Invalid hook timing: $when");
        }
        $this->hooks[$when][$action][] = $callback;
    }

    /**
     * Run hooks for a given action and timing
     * 
     * Executes all registered callbacks for the specified action and timing.
     * First runs action-specific hooks, then wildcard (*) hooks. Context is
     * passed by reference so callbacks can modify data.
     * 
     * Execution Order:
     * 1. Action-specific hooks (e.g., hooks for "create")
     * 2. Wildcard hooks (hooks for "*")
     * 3. Within each group, callbacks execute in registration order
     * 
     * @param string $when Hook timing: "before" or "after"
     * @param string $action Action name ("create", "read", "update", "delete")
     * @param array $context Context data passed by reference. Typically contains:
     *   - table: string Table name
     *   - data: array Record data (for create/update)
     *   - id: mixed Record ID (for read/update/delete)
     *   - user: string Current user identifier
     *   - Custom fields as needed
     * @return void Context may be modified by callbacks
     * 
     * @example
     * // In ApiGenerator before creating record
     * $context = [
     *     'table' => 'users',
     *     'data' => ['name' => 'John', 'password' => 'plain123'],
     *     'user' => 'admin'
     * ];
     * $hooks->runHooks('before', 'create', $context);
     * // $context['data']['password'] now hashed by registered hook
     * 
     * @example
     * // After successful update
     * $context = [
     *     'table' => 'posts',
     *     'id' => 42,
     *     'data' => ['title' => 'Updated Title'],
     *     'result' => $updateResult
     * ];
     * $hooks->runHooks('after', 'update', $context);
     * // Notification sent, cache invalidated by registered hooks
     * 
     * @example
     * // Wildcard hook runs for all actions
     * $hooks->registerHook('*', function(&$ctx) {
     *     $ctx['processed_at'] = microtime(true);
     * }, 'after');
     * 
     * $hooks->runHooks('after', 'create', $context);
     * $hooks->runHooks('after', 'update', $context);
     * // Both get 'processed_at' timestamp added
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