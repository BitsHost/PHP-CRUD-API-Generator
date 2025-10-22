<?php
return [
    // ... existing config ...
    'auth_enabled' => true,
    'auth_method' => 'basic', // or 'apikey', 'jwt', etc.
    'api_keys' => ['changeme123'],
    'basic_users' => [
        'admin' => 'secret',
        'user' => 'userpass'
    ],
    // RBAC config: map users to roles, and roles to table permissions
    'roles' => [
        'admin' => [
            // full access
            '*' => ['list', 'read', 'create', 'update', 'delete']
        ],
        'readonly' => [
            // read only on all tables
            '*' => ['list', 'read']
        ],
        'users_manager' => [
            'users' => ['list', 'read', 'create', 'update'],
            'orders' => ['list', 'read']
        ]
    ],
    // Map users to roles
    'user_roles' => [
        'admin' => 'admin',
        'user' => 'readonly'
    ],
];