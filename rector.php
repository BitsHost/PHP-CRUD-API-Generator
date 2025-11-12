<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'App\\Database' => 'App\\Database\\Database',
        'App\\SchemaInspector' => 'App\\Database\\SchemaInspector',
        'App\\Authenticator' => 'App\\Auth\\Authenticator',
        'App\\RequestLogger' => 'App\\Observability\\RequestLogger',
        'App\\Monitor' => 'App\\Observability\\Monitor',
        'App\\Rbac' => 'App\\Security\\Rbac',
        'App\\RateLimiter' => 'App\\Security\\RateLimiter',
        'App\\OpenApiGenerator' => 'App\\Docs\\OpenApiGenerator',
        'App\\HookManager' => 'App\\Application\\HookManager',
        'App\\Response' => 'App\\Http\\Response',
        'App\\Cors' => 'App\\Http\\Middleware\\CorsMiddleware',
        'App\\Validator' => 'App\\Support\\Validator',
        'App\\Controller\\LoginController' => 'App\\Http\\Controllers\\LoginController',
    ]);

    // Process source by default; adjust paths if needed
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/public',
        __DIR__ . '/tests',
    ]);
};
