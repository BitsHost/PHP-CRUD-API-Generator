<?php
/**
 * Type-safe API configuration wrapper.
 *
 * @package   PHP-CRUD-API-Generator
 * @author    BitsHost
 * @copyright 2025 BitsHost
 * @license   MIT License
 * @link      https://bitshost.biz/
 * @created   2025-11-12
 */

namespace App\Config;

/**
 * API Configuration Class
 * 
 * PSR-4 compliant configuration class for API settings.
 * 
 * ARCHITECTURE:
 * This class WRAPS the user's config/api.php file and provides type-safe access.
 * 
 * Flow:
 * 1. User edits config/api.php (simple PHP array)
 * 2. ApiConfig::fromFile() loads that array
 * 3. Framework code uses typed methods (isAuthEnabled(), getAuthMethod(), etc.)
 * 
 * Benefits:
 * - User: Simple array configuration
 * - Developer: Type safety, IDE autocomplete, validation
 * 
 * Manages:
 * - Authentication settings (API Key, Basic, JWT, OAuth)
 * - Role-Based Access Control (RBAC)
 * - Rate limiting configuration
 * - Request logging settings
 * - Monitoring configuration
 * 
 * @package App\Config
 * @see docs/CONFIG_FLOW.md for complete architecture explanation
 */
class ApiConfig
{
    /** @var bool */
    private bool $authEnabled;
    /** @var string */
    private string $authMethod;
    /** @var array<int,string> */
    private array $apiKeys;
    /** @var string */
    private string $apiKeyRole;
    /** @var array<string,string> */
    private array $basicUsers;
    /** @var bool */
    private bool $useDatabaseAuth;
    /** @var string */
    private string $jwtSecret;
    /** @var int */
    private int $jwtExpiration;
    /** @var string */
    private string $jwtAlgorithm;
    /** @var array<string,array{tables:list<string>,actions:list<string>}> */
    private array $roles;
    /** @var array<string,string> */
    private array $userRoles;
    /** @var array{enabled:bool,requests_per_minute:int,requests_per_hour:int,requests_per_day:int} */
    private array $rateLimitConfig;
    /** @var array{enabled:bool,log_requests:bool,log_responses:bool,log_errors:bool} */
    private array $loggingConfig;
    /** @var array{enabled:bool} */
    private array $monitoringConfig;

    /**
     * Initialize API configuration
     * 
     * This constructor takes the PHP array from config/api.php and converts it
     * to type-safe object properties with validation and defaults.
     * 
     * Usually called via ApiConfig::fromFile() - not directly.
     * 
     * @param array $config Configuration array from config/api.php (or defaults)
     */
    /**
     * @param array<string,mixed> $config Configuration array from config/api.php (or defaults)
     */
    public function __construct(array $config = [])
    {
        // Authentication settings
        $this->authEnabled = $config['auth_enabled'] ?? true;
        $this->authMethod = $config['auth_method'] ?? 'jwt';
    $rawKeys = $config['api_keys'] ?? ['changeme123'];
    $keys = is_array($rawKeys) ? array_values(array_map('strval', $rawKeys)) : [strval($rawKeys)];
    $this->apiKeys = $keys;
        $this->apiKeyRole = $config['api_key_role'] ?? 'admin';
        $this->basicUsers = $config['basic_users'] ?? [
            'admin' => 'secret',
            'user' => 'userpass',
        ];
        $this->useDatabaseAuth = $config['use_database_auth'] ?? true;

        // JWT settings
        $this->jwtSecret = $config['jwt_secret'] ?? 'your-secret-key-change-this-in-production';
        $this->jwtExpiration = $config['jwt_expiration'] ?? 3600;
        $this->jwtAlgorithm = $config['jwt_algorithm'] ?? 'HS256';

        // RBAC settings
        $this->roles = $config['roles'] ?? [
            'admin' => [
                'tables' => ['*'],
                'actions' => ['*'],
            ],
            'editor' => [
                'tables' => ['*'],
                'actions' => ['list', 'read', 'create', 'update', 'count'],
            ],
            'readonly' => [
                'tables' => ['*'],
                'actions' => ['list', 'read', 'count'],
            ],
        ];

        $this->userRoles = $config['user_roles'] ?? [
            'admin' => 'admin',
            'user' => 'readonly',
        ];

        // Rate limiting
        $this->rateLimitConfig = $config['rate_limit'] ?? [
            'enabled' => true,
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
            'requests_per_day' => 10000,
        ];

        // Logging
        $this->loggingConfig = $config['logging'] ?? [
            'enabled' => true,
            'log_requests' => true,
            'log_responses' => false,
            'log_errors' => true,
        ];

        // Monitoring
        $this->monitoringConfig = $config['monitoring'] ?? [
            'enabled' => false,
        ];
    }

    /**
     * Create from legacy config file
     * 
     * This is the BRIDGE between user configuration and framework code.
     * 
     * Example:
     * // User edits config/api.php:
     * return ['auth_enabled' => true, 'auth_method' => 'jwt'];
     * 
     * // Framework loads it via this method:
     * $config = ApiConfig::fromFile(__DIR__ . '/../config/api.php');
     * 
     * // Now use type-safe getters:
     * $enabled = $config->isAuthEnabled();  // bool
     * $method = $config->getAuthMethod();   // string
     * 
     * @param string $configFile Path to config/api.php
     * @return self
     */
    public static function fromFile(string $configFile): self
    {
        if (!file_exists($configFile)) {
            // Return defaults if config doesn't exist
            return new self();
        }

        // Load user's PHP array from config/api.php
        $config = require $configFile;
        
        // Convert array to type-safe object
        return new self($config);
    }

    /**
     * Check if authentication is enabled
     */
    public function isAuthEnabled(): bool
    {
        return $this->authEnabled;
    }

    /**
     * Get authentication method
     */
    public function getAuthMethod(): string
    {
        return $this->authMethod;
    }

    /**
     * Get valid API keys
     */
    /**
     * @return array<int,string>
     */
    public function getApiKeys(): array
    {
        return $this->apiKeys;
    }

    /**
     * Get API key default role
     */
    public function getApiKeyRole(): string
    {
        return $this->apiKeyRole;
    }

    /**
     * Get basic auth users
     */
    /**
     * @return array<string,string>
     */
    public function getBasicUsers(): array
    {
        return $this->basicUsers;
    }

    /**
     * Check if database authentication is enabled
     */
    public function useDatabaseAuth(): bool
    {
        return $this->useDatabaseAuth;
    }

    /**
     * Get JWT secret
     */
    public function getJwtSecret(): string
    {
        return $this->jwtSecret;
    }

    /**
     * Get JWT expiration time
     */
    public function getJwtExpiration(): int
    {
        return $this->jwtExpiration;
    }

    /**
     * Get JWT algorithm
     */
    public function getJwtAlgorithm(): string
    {
        return $this->jwtAlgorithm;
    }

    /**
     * Get all roles configuration
     */
    /**
     * @return array<string,array{tables:list<string>,actions:list<string>}>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Get user role mappings
     */
    /**
     * @return array<string,string>
     */
    public function getUserRoles(): array
    {
        return $this->userRoles;
    }

    /**
     * Get role for specific user
     */
    public function getUserRole(string $username): ?string
    {
        return $this->userRoles[$username] ?? null;
    }

    /**
     * Get rate limit configuration
     */
    /**
     * @return array{enabled:bool,requests_per_minute:int,requests_per_hour:int,requests_per_day:int}
     */
    public function getRateLimitConfig(): array
    {
        return $this->rateLimitConfig;
    }

    /**
     * Get logging configuration
     */
    /**
     * @return array{enabled:bool,log_requests:bool,log_responses:bool,log_errors:bool}
     */
    public function getLoggingConfig(): array
    {
        return $this->loggingConfig;
    }

    /**
     * Get monitoring configuration
     */
    /**
     * @return array{enabled:bool}
     */
    public function getMonitoringConfig(): array
    {
        return $this->monitoringConfig;
    }

    /**
     * Check if monitoring is enabled
     */
    public function isMonitoringEnabled(): bool
    {
        return (bool) $this->monitoringConfig['enabled'];
    }

    /**
     * Convert to array (for backward compatibility)
     */
    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'auth_enabled' => $this->authEnabled,
            'auth_method' => $this->authMethod,
            'api_keys' => $this->apiKeys,
            'api_key_role' => $this->apiKeyRole,
            'basic_users' => $this->basicUsers,
            'use_database_auth' => $this->useDatabaseAuth,
            'jwt_secret' => $this->jwtSecret,
            'jwt_expiration' => $this->jwtExpiration,
            'jwt_algorithm' => $this->jwtAlgorithm,
            'roles' => $this->roles,
            'user_roles' => $this->userRoles,
            'rate_limit' => $this->rateLimitConfig,
            'logging' => $this->loggingConfig,
            'monitoring' => $this->monitoringConfig,
        ];
    }

    /**
     * Enable authentication
     */
    public function enableAuth(): void
    {
        $this->authEnabled = true;
    }

    /**
     * Disable authentication
     */
    public function disableAuth(): void
    {
        $this->authEnabled = false;
    }

    /**
     * Set authentication method
     */
    public function setAuthMethod(string $method): void
    {
        $this->authMethod = $method;
    }

    /**
     * Add API key
     */
    public function addApiKey(string $key): void
    {
        if (!in_array($key, $this->apiKeys)) {
            $this->apiKeys[] = $key;
        }
    }

    /**
     * Remove API key
     */
    public function removeApiKey(string $key): void
    {
        $this->apiKeys = array_filter($this->apiKeys, fn($k) => $k !== $key);
    }

    /**
     * Add basic auth user
     */
    public function addBasicUser(string $username, string $password): void
    {
        $this->basicUsers[$username] = $password;
    }

    /**
     * Remove basic auth user
     */
    public function removeBasicUser(string $username): void
    {
        unset($this->basicUsers[$username]);
    }

    /**
     * Set JWT secret
     */
    public function setJwtSecret(string $secret): void
    {
        $this->jwtSecret = $secret;
    }

    /**
     * Assign role to user
     */
    public function assignUserRole(string $username, string $role): void
    {
        $this->userRoles[$username] = $role;
    }

    /**
     * Remove user role
     */
    public function removeUserRole(string $username): void
    {
        unset($this->userRoles[$username]);
    }
}
