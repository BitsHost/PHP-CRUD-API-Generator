<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Security\Rbac;
use App\Security\RbacGuard;

$roles = [ 'viewer' => ['*' => ['read']] ];
$userRoles = [];
$rbac = new Rbac($roles, $userRoles);
$guard = new RbacGuard($rbac);

// Expect 403 and exit
$guard->guard(true, 'viewer', 'posts', 'delete');

echo "should not reach here\n"; // shouldn't print
