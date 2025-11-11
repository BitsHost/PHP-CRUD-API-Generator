<?php
require __DIR__ . '/../src/Rbac.php';
require __DIR__ . '/../src/Http/Response.php';
require __DIR__ . '/../src/Security/RbacGuard.php';

use App\Rbac;
use App\Security\RbacGuard;

$roles = [ 'viewer' => ['*' => ['read']] ];
$userRoles = [];
$rbac = new Rbac($roles, $userRoles);
$guard = new RbacGuard($rbac);

// Expect 403 and exit
$guard->guard(true, 'viewer', 'posts', 'delete');

echo "should not reach here\n"; // shouldn't print
