<?php
namespace App;

/**
 * Role-Based Access Control (RBAC) System
 * 
 * Manages permissions for different roles, controlling access to tables and actions.
 * Supports wildcard permissions and table-specific permissions.
 * 
 * Features:
 * - Role-based permission management
 * - Wildcard table permissions ('*')
 * - Table-specific permissions
 * - Action-level control (list, read, create, update, delete)
 * - User-to-role mapping
 * 
 * @package App
 * @author  PHP-CRUD-API-Generator
 * @version 1.0.0
 */
class Rbac
{
    /**
     * Role definitions with their permissions
     * 
     * Structure: ['role_name' => ['table_name' => ['action1', 'action2']]]
     * 
     * @var array
     */
    private array $roles;
    
    /**
     * User-to-role mapping
     * 
     * Structure: ['username' => 'role_name']
     * 
     * @var array
     */
    private array $userRoles;

    /**
     * Initialize RBAC system
     * 
     * @param array $roles     Role definitions with permissions
     * @param array $userRoles User-to-role mappings
     * 
     * @example
     * $rbac = new Rbac(
     *     [
     *         'admin' => ['*' => ['list', 'read', 'create', 'update', 'delete']],
     *         'editor' => [
     *             'posts' => ['list', 'read', 'update'],
     *             'comments' => ['list', 'read', 'delete']
     *         ],
     *         'viewer' => ['*' => ['list', 'read']]
     *     ],
     *     [
     *         'john' => 'admin',
     *         'jane' => 'editor',
     *         'bob' => 'viewer'
     *     ]
     * );
     */
    public function __construct(array $roles, array $userRoles)
    {
        $this->roles = $roles;
        $this->userRoles = $userRoles;
    }

    /**
     * Check if a role is allowed to perform an action on a table
     * 
     * Checks both wildcard permissions ('*') and table-specific permissions.
     * Wildcard permissions apply to all tables.
     * 
     * @param string $role   Role name to check
     * @param string $table  Table name being accessed
     * @param string $action Action being performed (list, read, create, update, delete)
     * 
     * @return bool True if role is allowed to perform action on table, false otherwise
     * 
     * @example
     * // Check if admin can update posts
     * if ($rbac->isAllowed('admin', 'posts', 'update')) {
     *     // Allow update
     * }
     * 
     * @example
     * // Check viewer permissions
     * $rbac->isAllowed('viewer', 'users', 'delete'); // Returns false
     * $rbac->isAllowed('viewer', 'users', 'read');   // Returns true (has wildcard read)
     */
    public function isAllowed(string $role, string $table, string $action): bool
    {
        if (!isset($this->roles[$role])) {
            return false;
        }
        $perms = $this->roles[$role];
        
        // Check for explicit DENY (empty array or 'deny' marker)
        // This takes precedence over wildcard permissions
        if (isset($perms[$table])) {
            // Empty array = explicit deny
            if (empty($perms[$table])) {
                return false;
            }
            // Check if action is allowed for this specific table
            if (in_array($action, $perms[$table], true)) {
                return true;
            }
            // If table is explicitly defined but action not in list, deny
            return false;
        }
        
        // Wildcard table permissions (only if table not explicitly defined)
        if (isset($perms['*']) && in_array($action, $perms['*'], true)) {
            return true;
        }
        
        return false;
    }
}