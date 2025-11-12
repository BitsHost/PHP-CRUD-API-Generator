<?php
declare(strict_types=1);

namespace App\Security;

/**
 * Role-Based Access Control (RBAC) System (Canonical)
 */
class Rbac
{
	/**
	 * @var array<string, array<string, list<string>>> $roles
	 */
	private array $roles;

	/**
	 * @var array<string, list<string>> $userRoles
	 */
	private array $userRoles;

	/**
	 * @param array<string, array<string, list<string>>> $roles
	 * @param array<string, list<string>> $userRoles
	 */
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

		if (isset($perms[$table])) {
			if (empty($perms[$table])) {
				return false;
			}
			if (in_array($action, $perms[$table], true)) {
				return true;
			}
			return false;
		}
		if (isset($perms['*']) && in_array($action, $perms['*'], true)) {
			return true;
		}
		return false;
	}
}
