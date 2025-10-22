<?php
namespace App;

class Rbac
{
    private array $roles;
    private array $userRoles;

    public function __construct(array $roles, array $userRoles)
    {
        $this->roles = $roles;
        $this->userRoles = $userRoles;
    }

    public function isAllowed(string $role, string $table, string $action): bool
    {
        if (!isset($this->roles[$role])) {
            return false;
        }
        $perms = $this->roles[$role];
        // Wildcard table permissions
        if (isset($perms['*']) && in_array($action, $perms['*'], true)) {
            return true;
        }
        // Table-specific permissions
        if (isset($perms[$table]) && in_array($action, $perms[$table], true)) {
            return true;
        }
        return false;
    }
}