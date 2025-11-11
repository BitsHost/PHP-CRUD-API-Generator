<?php
namespace App\Security;

use App\Rbac;
use App\Http\Response;

/**
 * RbacGuard enforces RBAC via Response helper.
 *
 * Contract:
 * - guard(authEnabled, role, table, action):
 *   - If auth disabled or table null → allow (no-op)
 *   - If no role → 403 Forbidden via Response
 *   - If role not allowed → 403 Forbidden via Response
 */
class RbacGuard
{
    public function __construct(private Rbac $rbac)
    {
    }

    public function guard(bool $authEnabled, ?string $role, ?string $table, string $action): void
    {
        if (!$authEnabled || !$table) {
            return; // skip when auth off or table not provided
        }
        if (!$role) {
            Response::error('Forbidden: No role assigned', 403);
            exit;
        }
        if (!$this->rbac->isAllowed($role, $table, $action)) {
            Response::error("Forbidden: $role cannot $action on $table", 403);
            exit;
        }
    }
}
