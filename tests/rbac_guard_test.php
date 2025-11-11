<?php
// Minimal test for RbacGuard allowed path (no exit)
require __DIR__ . '/../src/Rbac.php';
require __DIR__ . '/../src/Http/Response.php';
require __DIR__ . '/../src/Security/RbacGuard.php';

use App\Rbac;
use App\Security\RbacGuard;

$roles = [
    'admin' => ['*' => ['list','read','create','update','delete']],
    'viewer' => ['*' => ['list','read']],
];
$userRoles = ['john' => 'viewer'];

$rbac = new Rbac($roles, $userRoles);
$guard = new RbacGuard($rbac);

// Should not exit: auth enabled, role viewer, table posts, action read permitted
$guard->guard(true, 'viewer', 'posts', 'read');

echo "rbac_guard allowed: PASS\n";
