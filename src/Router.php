<?php

namespace App;

use App\Cache\CacheManager;
use App\Config\ApiConfig;
use App\Config\CacheConfig;
use App\Controller\LoginController;
use App\Http\Action;
use App\Http\Response;
use App\Support\QueryValidator;
use App\Http\Middleware\RateLimitMiddleware;
use App\Security\RbacGuard;
use App\Http\Middleware\CorsMiddleware;
use App\Http\ErrorResponder;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\DocsController;

/**
 * API Router
 * 
 * Main routing class that handles all API requests, coordinates authentication,
 * authorization, rate limiting, logging, and delegates CRUD operations to ApiGenerator.
 * Acts as the central orchestrator for the entire API request lifecycle.
 * 
 * Request Lifecycle:
 * 1. Rate limiting check (with headers)
 * 2. Authentication (if enabled)
 * 3. Route parsing and validation
 * 4. RBAC authorization check
 * 5. Business logic execution (via ApiGenerator)
 * 6. Response formatting and logging
 * 7. Error handling
 * 
 * Features:
 * - Automatic rate limiting with configurable thresholds
 * - Multi-method authentication (API key, Basic, JWT, OAuth)
 * - Role-based access control (RBAC) enforcement
 * - Comprehensive request/response logging
 * - Input validation for all parameters
 * - JWT login endpoint (/api.php?action=login)
 * - OpenAPI specification generation
 * - Bulk operations (bulk_create, bulk_delete)
 * - Schema introspection (tables, columns)
 * - Full CRUD operations (list, read, create, update, delete, count)
 * - Error handling with proper HTTP status codes
 * - JSON request/response formatting
 * - Execution time tracking
 * 
 * Supported Actions:
 * - tables: List all database tables
 * - columns: Get column information for a table
 * - list: Retrieve paginated, filtered, sorted records
 * - count: Count records with optional filters
 * - read: Get single record by ID
 * - create: Insert new record
 * - update: Modify existing record
 * - delete: Remove record by ID
 * - bulk_create: Insert multiple records in transaction
 * - bulk_delete: Remove multiple records by IDs
 * - openapi: Generate OpenAPI 3.0 specification
 * - login: JWT authentication endpoint
 * 
 * @package App
 * @author Adrian D
 * @copyright 2025 BitHost
 * @license MIT
 * @version 1.4.0
 * @link https://upmvc.com
 * 
 * @example
 * // Basic usage in index.php or api.php
 * $db = new Database(['dsn' => 'mysql:host=localhost;dbname=mydb', ...]);
 * $auth = new Authenticator(['auth_method' => 'apikey', ...]);
 * $router = new Router($db, $auth);
 * 
 * // Parse query string
 * $query = $_GET;
 * 
 * // Route the request
 * $router->route($query);
 * // Automatically handles rate limiting, auth, RBAC, logging
 * 
 * @example
 * // API requests
 * GET  /api.php?action=list&table=users&page=1&page_size=20
 * GET  /api.php?action=read&table=users&id=123
 * POST /api.php?action=create&table=users (body: {"name": "John"})
 * POST /api.php?action=update&table=users&id=123 (body: {"name": "Jane"})
 * GET  /api.php?action=delete&table=users&id=123
 * POST /api.php?action=bulk_create&table=users (body: [{"name": "A"}, {"name": "B"}])
 * GET  /api.php?action=openapi
 */
class Router
{
    private Database $db;
    private SchemaInspector $inspector;
    private ApiGenerator $api;
    public Authenticator $auth;
    private Rbac $rbac;
    private RbacGuard $rbacGuard;
    private RateLimitMiddleware $rateLimitMiddleware;
    private RequestLogger $logger;
    private ?Monitor $monitor = null;
    private ?CacheManager $cache = null;
    private ApiConfig $apiConfig;
    private bool $authEnabled;
    private float $requestStartTime;
    private CorsMiddleware $cors;
    private ErrorResponder $errors;

    /**
     * Initialize Router
     * 
     * Sets up all components needed for request handling including database connection,
     * authentication, authorization, rate limiting, and logging. Loads configuration
     * from config/api.php and initializes subsystems.
     * 
     * @param Database $db Database connection instance
     * @param Authenticator $auth Authenticator instance with configured auth method
     * 
     * @example
     * $db = new Database([
     *     'dsn' => 'mysql:host=localhost;dbname=mydb',
     *     'username' => 'root',
     *     'password' => 'secret'
     * ]);
     * 
     * $auth = new Authenticator([
     *     'auth_method' => 'jwt',
     *     'jwt_secret' => 'your-secret-key',
     *     'jwt_expiration' => 3600
     * ]);
     * 
     * $router = new Router($db, $auth);
     */
    public function __construct(Database $db, Authenticator $auth)
    {
        $pdo = $db->getPdo();
        $this->db = $db;
        $this->inspector = new SchemaInspector($pdo);
        $this->api = new ApiGenerator($pdo);
        $this->auth = $auth;

        // Load configuration using PSR-4 Config classes
        $this->apiConfig = ApiConfig::fromFile(__DIR__ . '/../config/api.php');
        $this->authEnabled = $this->apiConfig->isAuthEnabled();
        $this->rbac = new Rbac(
            $this->apiConfig->getRoles(),
            $this->apiConfig->getUserRoles()
        );
        $this->rbacGuard = new RbacGuard($this->rbac);
        
        // Initialize request logger with config
        $this->logger = new RequestLogger($this->apiConfig->getLoggingConfig());

    // Initialize rate limiter + middleware with config (logger must exist first)
    $rateLimiter = new RateLimiter($this->apiConfig->getRateLimitConfig());
    $this->rateLimitMiddleware = new RateLimitMiddleware($rateLimiter, $this->logger, $this->monitor);

    // Initialize CORS middleware (using defaults for now)
    $this->cors = new CorsMiddleware([ /* TODO: load from config when available */ ]);

    // Initialize error responder
    $this->errors = new ErrorResponder($this->logger, $this->monitor, true);
        
    // (moved logger initialization earlier)
        
        // Initialize monitor if enabled
        if ($this->apiConfig->isMonitoringEnabled()) {
            $this->monitor = new Monitor($this->apiConfig->getMonitoringConfig());
        }
        
        // Initialize cache if enabled (using PSR-4 Config class)
        $cacheConfig = CacheConfig::fromFile(__DIR__ . '/../config/cache.php');
        if ($cacheConfig->isEnabled()) {
            $this->cache = new CacheManager($cacheConfig->toArray());
        }
        
        // Track request start time
        $this->requestStartTime = microtime(true);
    }

    /**
     * Enforce RBAC (Role-Based Access Control)
     * 
     * Checks if the current authenticated user has permission to perform the specified
     * action on the given table. Sends 403 Forbidden response and exits if permission
     * is denied. Skips check if authentication is disabled in config.
     * 
     * Uses the following permission format: "table:action" (e.g., "users:create")
     * Supports wildcard permissions: "*:*" grants all access
     * 
     * @param string $action Action to perform (list, read, create, update, delete)
     * @param string|null $table Table name to check permissions for (null skips check)
     * @return void No return value; exits on permission denial
     * 
     * @example
     * // Internal usage (called automatically by route())
     * $this->enforceRbac('create', 'users');
     * // If user role doesn't have 'users:create' permission, sends 403 and exits
     * 
     * @example
     * // RBAC configuration in config/api.php
     * 'roles' => [
     *     'admin' => ['*' => ['*']],  // All permissions
     *     'editor' => [
     *         'posts' => ['list', 'read', 'create', 'update'],
     *         'users' => ['read']
     *     ],
     *     'viewer' => [
     *         'posts' => ['list', 'read'],
     *         'users' => ['read']
     *     ]
     * ]
     */
    // RBAC checks are delegated to RbacGuard

    /**
     * Route API request
     * 
     * Main routing method that processes API requests through the complete lifecycle:
     * rate limiting, authentication, validation, authorization, execution, and logging.
     * Handles all supported actions and returns JSON responses with appropriate HTTP
     * status codes.
     * 
     * Request Flow:
     * 1. Check rate limit (returns 429 if exceeded)
     * 2. Handle JWT login (if action=login)
     * 3. Authenticate user (if auth enabled)
     * 4. Parse and validate action/parameters
     * 5. Enforce RBAC permissions
     * 6. Execute business logic via ApiGenerator
     * 7. Log request/response
     * 8. Return JSON response
     * 
     * Supported Query Parameters:
     * - action: Required action name (tables, list, read, create, etc.)
     * - table: Table name for CRUD operations
     * - id: Record ID for read/update/delete
     * - page: Page number for pagination (default: 1)
     * - page_size: Records per page (default: 20, max: 100)
     * - filter: Filter conditions (e.g., "name:eq:John,age:gt:18")
     * - sort: Sort order (e.g., "name:asc,created_at:desc")
     * - fields: Comma-separated field list to return
     * 
     * POST Body Formats:
     * - create/update: JSON object {"field": "value"}
     * - bulk_create: JSON array [{"field": "value"}, ...]
     * - bulk_delete: JSON object {"ids": [1, 2, 3]}
     * 
     * @param array $query Query parameters from $_GET (typically)
     * @return void Outputs JSON response directly, no return value
     * 
     * @example
     * // List users with pagination and filters
     * $router->route([
     *     'action' => 'list',
     *     'table' => 'users',
     *     'page' => 1,
     *     'page_size' => 20,
     *     'filter' => 'status:eq:active,age:gt:18',
     *     'sort' => 'created_at:desc'
     * ]);
     * // Output: {"records": [...], "pagination": {...}}
     * 
     * @example
     * // Create new record (requires POST)
     * $_POST = ['name' => 'John Doe', 'email' => 'john@example.com'];
     * $router->route(['action' => 'create', 'table' => 'users']);
     * // Output: {"id": 123}
     * 
     * @example
     * // JWT login
     * $_POST = ['username' => 'admin', 'password' => 'secret'];
     * $router->route(['action' => 'login']);
     * // Output: {"token": "eyJ0eXAiOiJKV1QiLCJhbGc..."}
     * 
     * @example
     * // Get OpenAPI specification
     * $router->route(['action' => 'openapi']);
     * // Output: {"openapi": "3.0.0", "info": {...}, "paths": {...}}
     */
    public function route(array $query)
    {
        // CORS headers first; handle preflight early
        if ($this->cors->handlePreflight()) {
            return;
        }
        $this->cors->apply();

        // Content-Type header will be set per-response via Response helper

        // ========================================
        // RATE LIMITING CHECK
        // ========================================
        $identifier = $this->getRateLimitIdentifier();
        if (!$this->rateLimitMiddleware->checkAndRespond($identifier)) {
            return; // 429 already returned
        }
        
        // Record request metric
        if ($this->monitor) {
            $this->monitor->recordRequest([
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                'action' => $query['action'] ?? null,
                'table' => $query['table'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user' => $this->auth->getCurrentUser()['username'] ?? null,
            ]);
        }

        // JWT login endpoint (always accessible if method is JWT)
    if (($query['action'] ?? '') === Action::LOGIN && ($this->auth->config['auth_method'] ?? '') === 'jwt') {
            $controller = new LoginController($this->db, $this->auth, $this->logger, $this->monitor);
            [$payload, $status] = $controller->handle($query);
            $this->logResponse($payload, $status, $query);
            Response::json($payload, $status);
            return;
        }

        // Only require authentication if enabled
        if ($this->authEnabled) {
            if (!$this->auth->authenticate()) {
                $this->logger->logAuth(
                    $this->auth->config['auth_method'] ?? 'unknown',
                    false,
                    $identifier,
                    'Authentication failed'
                );
                
                // Record auth failure
                if ($this->monitor) {
                    $this->monitor->recordSecurityEvent('auth_failure', [
                        'method' => $this->auth->config['auth_method'] ?? 'unknown',
                        'reason' => 'Authentication failed',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    ]);
                }
                
                $this->auth->requireAuth();
            } else {
                $this->logger->logAuth(
                    $this->auth->config['auth_method'] ?? 'unknown',
                    true,
                    $this->auth->getCurrentUser() ?? $identifier
                );
                
                // Record auth success
                if ($this->monitor) {
                    $this->monitor->recordSecurityEvent('auth_success', [
                        'method' => $this->auth->config['auth_method'] ?? 'unknown',
                        'user' => $this->auth->getCurrentUser()['username'] ?? $identifier,
                    ]);
                }
            }
        }

        try {
            switch ($query['action'] ?? '') {
                case Action::TABLES:
                    // Delegate to ApiController
                    $apiCtl = new ApiController($this->inspector, $this->api, $this->cache, $this->rbacGuard, $this->authEnabled);
                    [$payload, $status] = $apiCtl->tables();
                    $this->logResponse($payload, $status, $query);
                    Response::json($payload, $status);
                    break;

                case Action::COLUMNS:
                    $apiCtl = new ApiController($this->inspector, $this->api, $this->cache, $this->rbacGuard, $this->authEnabled);
                    [$payload, $status] = $apiCtl->columns($this->auth->getCurrentUserRole(), $query['table'] ?? null);
                    $this->logResponse($payload, $status, $query);
                    Response::json($payload, $status);
                    break;

                case Action::LIST:
                    if (isset($query['table'])) {
                        if (!QueryValidator::table($query['table'])) {
                            $this->logResponse(['error' => 'Invalid table name'], 400, $query);
                            Response::error('Invalid table name', 400);
                            break;
                        }
                        $this->rbacGuard->guard($this->authEnabled, $this->auth->getCurrentUserRole(), $query['table'], 'list');
                        $opts = [
                            'filter' => $query['filter'] ?? null,
                            'sort' => $query['sort'] ?? null,
                            'page' => QueryValidator::page($query['page'] ?? 1),
                            'page_size' => QueryValidator::pageSize($query['page_size'] ?? 20),
                            'fields' => $query['fields'] ?? null,
                        ];
                        // Validate sort if provided
                        if (isset($opts['sort']) && !QueryValidator::sort($opts['sort'])) {
                            $this->logResponse(['error' => 'Invalid sort parameter'], 400, $query);
                            Response::error('Invalid sort parameter', 400);
                            break;
                        }
                        
                        // ========================================
                        // CACHE CHECK
                        // ========================================
                        $cacheHit = false;
                        $result = null;
                        $responseHeaders = [];
                        
                        if ($this->cache && $this->cache->shouldCache($query['table'])) {
                            $cacheKey = $this->cache->generateKey($query['table'], $opts);
                            $result = $this->cache->get($cacheKey);
                            
                            if ($result !== null) {
                                $cacheHit = true;
                                $ttl = $this->cache->getTtl($query['table']);
                                $responseHeaders['X-Cache-Hit'] = 'true';
                                $responseHeaders['X-Cache-TTL'] = (string)$ttl;
                            }
                        }
                        
                        // If not cached, fetch from database
                        if ($result === null) {
                            $result = $this->api->list($query['table'], $opts);
                            
                            // Store in cache
                            if ($this->cache && $this->cache->shouldCache($query['table'])) {
                                $this->cache->set($cacheKey, $result, $query['table']);
                                $ttl = $this->cache->getTtl($query['table']);
                                $responseHeaders['X-Cache-Hit'] = 'false';
                                $responseHeaders['X-Cache-Stored'] = 'true';
                                $responseHeaders['X-Cache-TTL'] = (string)$ttl;
                            }
                        }
                        // ========================================
                        // END CACHE CHECK
                        // ========================================
                        
                        $this->logResponse($result, 200, $query);
                        Response::json($result, 200, $responseHeaders);
                    } else {
                        $this->logResponse(['error' => 'Missing table parameter'], 400, $query);
                        Response::error('Missing table parameter', 400);
                    }
                    break;

                case Action::COUNT:
                    if (isset($query['table'])) {
                        if (!QueryValidator::table($query['table'])) {
                            $this->logResponse(['error' => 'Invalid table name'], 400, $query);
                            Response::error('Invalid table name', 400);
                            break;
                        }
                        $this->rbacGuard->guard($this->authEnabled, $this->auth->getCurrentUserRole(), $query['table'], 'list'); // Use 'list' permission for count
                        $opts = [
                            'filter' => $query['filter'] ?? null,
                        ];
                        $result = $this->api->count($query['table'], $opts);
                        $this->logResponse($result, 200, $query);
                        Response::json($result, 200);
                    } else {
                        $this->logResponse(['error' => 'Missing table parameter'], 400, $query);
                        Response::error('Missing table parameter', 400);
                    }
                    break;

                case Action::READ:
                    if (isset($query['table'], $query['id'])) {
                        if (!QueryValidator::table($query['table'])) {
                            $this->logResponse(['error' => 'Invalid table name'], 400, $query);
                            Response::error('Invalid table name', 400);
                            break;
                        }
                        if (!QueryValidator::id($query['id'])) {
                            $this->logResponse(['error' => 'Invalid id parameter'], 400, $query);
                            Response::error('Invalid id parameter', 400);
                            break;
                        }
                        $this->rbacGuard->guard($this->authEnabled, $this->auth->getCurrentUserRole(), $query['table'], 'read');
                        $result = $this->api->read($query['table'], $query['id']);
                        $this->logResponse($result, 200, $query);
                        Response::json($result, 200);
                    } else {
                        $this->logResponse(['error' => 'Missing table or id parameter'], 400, $query);
                        Response::error('Missing table or id parameter', 400);
                    }
                    break;

                case Action::CREATE:
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        $this->logResponse(['error' => 'Method Not Allowed'], 405, $query);
                        Response::error('Method Not Allowed', 405);
                        break;
                    }
                    if (!isset($query['table']) || !QueryValidator::table($query['table'])) {
                        $this->logResponse(['error' => 'Invalid or missing table parameter'], 400, $query);
                        Response::error('Invalid or missing table parameter', 400);
                        break;
                    }
                    $this->rbacGuard->guard($this->authEnabled, $this->auth->getCurrentUserRole(), $query['table'], 'create');
                    $data = $_POST;
                    if (empty($data) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === 0) {
                        $data = json_decode(file_get_contents('php://input'), true) ?? [];
                    }
                    $result = $this->api->create($query['table'], $data);
                    
                    // Invalidate cache for this table
                    if ($this->cache) {
                        $this->cache->invalidateTable($query['table']);
                    }
                    
                    $this->logResponse($result, 201, $query);
                    Response::json($result, 201);
                    break;

                case Action::UPDATE:
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        $this->logResponse(['error' => 'Method Not Allowed'], 405, $query);
                        Response::error('Method Not Allowed', 405);
                        break;
                    }
                    if (!isset($query['table']) || !QueryValidator::table($query['table'])) {
                        $this->logResponse(['error' => 'Invalid or missing table parameter'], 400, $query);
                        Response::error('Invalid or missing table parameter', 400);
                        break;
                    }
                    if (!isset($query['id']) || !QueryValidator::id($query['id'])) {
                        $this->logResponse(['error' => 'Invalid or missing id parameter'], 400, $query);
                        Response::error('Invalid or missing id parameter', 400);
                        break;
                    }
                    $this->rbacGuard->guard($this->authEnabled, $this->auth->getCurrentUserRole(), $query['table'], 'update');
                    $data = $_POST;
                    if (empty($data) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === 0) {
                        $data = json_decode(file_get_contents('php://input'), true) ?? [];
                    }
                    $result = $this->api->update($query['table'], $query['id'], $data);
                    
                    // Invalidate cache for this table
                    if ($this->cache) {
                        $this->cache->invalidateTable($query['table']);
                    }
                    
                    $this->logResponse($result, 200, $query);
                    Response::json($result, 200);
                    break;

                case Action::DELETE:
                    if (isset($query['table'], $query['id'])) {
                        if (!QueryValidator::table($query['table'])) {
                            $this->logResponse(['error' => 'Invalid table name'], 400, $query);
                            Response::error('Invalid table name', 400);
                            break;
                        }
                        if (!QueryValidator::id($query['id'])) {
                            $this->logResponse(['error' => 'Invalid id parameter'], 400, $query);
                            Response::error('Invalid id parameter', 400);
                            break;
                        }
                        $this->rbacGuard->guard($this->authEnabled, $this->auth->getCurrentUserRole(), $query['table'], 'delete');
                        $result = $this->api->delete($query['table'], $query['id']);
                        
                        // Invalidate cache for this table
                        if ($this->cache) {
                            $this->cache->invalidateTable($query['table']);
                        }
                        
                        $this->logResponse($result, 200, $query);
                        Response::json($result, 200);
                    } else {
                        $this->logResponse(['error' => 'Missing table or id parameter'], 400, $query);
                        Response::error('Missing table or id parameter', 400);
                    }
                    break;

                case Action::BULK_CREATE:
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        $this->logResponse(['error' => 'Method Not Allowed'], 405, $query);
                        Response::error('Method Not Allowed', 405);
                        break;
                    }
                    if (!isset($query['table']) || !QueryValidator::table($query['table'])) {
                        $this->logResponse(['error' => 'Invalid or missing table parameter'], 400, $query);
                        Response::error('Invalid or missing table parameter', 400);
                        break;
                    }
                    $this->rbacGuard->guard($this->authEnabled, $this->auth->getCurrentUserRole(), $query['table'], 'create');
                    $data = json_decode(file_get_contents('php://input'), true) ?? [];
                    if (!is_array($data) || empty($data)) {
                        $this->logResponse(['error' => 'Invalid or empty JSON array'], 400, $query);
                        Response::error('Invalid or empty JSON array', 400);
                        break;
                    }
                    $result = $this->api->bulkCreate($query['table'], $data);
                    
                    // Invalidate cache for this table
                    if ($this->cache) {
                        $this->cache->invalidateTable($query['table']);
                    }
                    
                    $this->logResponse($result, 201, $query);
                    Response::json($result, 201);
                    break;

                case Action::BULK_DELETE:
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        $this->logResponse(['error' => 'Method Not Allowed'], 405, $query);
                        Response::error('Method Not Allowed', 405);
                        break;
                    }
                    if (!isset($query['table']) || !QueryValidator::table($query['table'])) {
                        $this->logResponse(['error' => 'Invalid or missing table parameter'], 400, $query);
                        Response::error('Invalid or missing table parameter', 400);
                        break;
                    }
                    $this->rbacGuard->guard($this->authEnabled, $this->auth->getCurrentUserRole(), $query['table'], 'delete');
                    $data = json_decode(file_get_contents('php://input'), true) ?? [];
                    if (!isset($data['ids']) || !is_array($data['ids']) || empty($data['ids'])) {
                        $this->logResponse(['error' => 'Invalid or empty ids array. Send JSON with "ids" field.'], 400, $query);
                        Response::error('Invalid or empty ids array. Send JSON with "ids" field.', 400);
                        break;
                    }
                    $result = $this->api->bulkDelete($query['table'], $data['ids']);
                    
                    // Invalidate cache for this table
                    if ($this->cache) {
                        $this->cache->invalidateTable($query['table']);
                    }
                    
                    $this->logResponse($result, 200, $query);
                    Response::json($result, 200);
                    break;

                case Action::OPENAPI:
                    $docsCtl = new DocsController($this->inspector);
                    [$payload, $status] = $docsCtl->openapi();
                    $this->logResponse($payload, $status, $query);
                    Response::json($payload, $status);
                    break;

                default:
                    $error = ['error' => 'Invalid action'];
                    $this->logResponse($error, 400, $query);
                    Response::json($error, 400);
            }
        } catch (\Throwable $e) {
            [$payload, $status] = $this->errors->fromException($e, [
                'query' => $query,
                'action' => $query['action'] ?? null,
                'table' => $query['table'] ?? null,
            ], 500);
            $this->logResponse($payload, $status, $query);
        }
    }

    /**
     * Get unique identifier for rate limiting
     * 
     * Determines the best available identifier for rate limiting in priority order:
     * 1. Authenticated user (most accurate, per-user limits)
     * 2. API key hash (for API key authentication)
     * 3. Client IP address (fallback, supports proxies)
     * 
     * Handles X-Forwarded-For and X-Real-IP headers for proxied requests.
     * Multiple IPs in X-Forwarded-For are handled by using the first (client) IP.
     * 
     * @return string Unique identifier with prefix (user:, apikey:, or ip:)
     * 
     * @example
     * // For authenticated user
     * // Returns: "user:john@example.com"
     * 
     * // For API key auth
     * // Returns: "apikey:a3f5c8..." (SHA-256 hash)
     * 
     * // For anonymous/IP-based
     * // Returns: "ip:192.168.1.100"
     * 
     * // Behind proxy with X-Forwarded-For
     * // Returns: "ip:203.0.113.45" (first IP from list)
     */
    private function getRateLimitIdentifier(): string
    {
        // Priority 1: Authenticated user (most accurate)
        $user = $this->auth->getCurrentUser();
        if ($user) {
            return 'user:' . $user;
        }

        // Priority 2: API Key (for apikey auth)
        if ($this->apiConfig->getAuthMethod() === 'apikey') {
            $headers = $this->getRequestHeaders();
            $apiKey = $headers['X-API-Key'] ?? ($_GET['api_key'] ?? null);
            if ($apiKey) {
                return 'apikey:' . hash('sha256', $apiKey);
            }
        }

        // Priority 3: IP Address (fallback)
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['HTTP_X_REAL_IP'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? 'unknown';
        
        // Handle multiple IPs in X-Forwarded-For (take first one)
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return 'ip:' . $ip;
    }

    /**
     * Get request headers
     * 
     * Retrieves all HTTP request headers using getallheaders() if available,
     * otherwise parses $_SERVER array for HTTP_* variables. Provides cross-platform
     * compatibility for header access.
     * 
     * @return array Associative array of header names to values
     *   Header names are normalized to Title-Case format (e.g., "Content-Type")
     * 
     * @example
     * $headers = $this->getRequestHeaders();
     * // Returns: [
     * //   'Content-Type' => 'application/json',
     * //   'Authorization' => 'Bearer eyJ0eXAi...',
     * //   'X-Api-Key' => 'abc123...',
     * //   'User-Agent' => 'Mozilla/5.0...'
     * // ]
     */
    private function getRequestHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        // Fallback
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * Log the response
     * 
     * Creates comprehensive log entry for the completed request including execution time,
     * HTTP status code, request/response bodies, headers, and user context. Automatically
     * called after each route execution for audit trail and debugging.
     * 
     * Captures:
     * - HTTP method and action
     * - Table name (if applicable)
     * - Client IP and authenticated user
     * - Request headers and body
     * - Response status, body, and size
     * - Execution time in seconds
     * 
     * @param mixed $responseBody Response payload (array, object, or scalar)
     * @param int $statusCode HTTP status code (200, 201, 400, 403, 404, 500, etc.)
     * @param array $query Query parameters from the request
     * @return void No return value; logs to configured RequestLogger
     * 
     * @example
     * // Internal usage (called automatically)
     * $this->logResponse(['id' => 123], 201, ['action' => 'create', 'table' => 'users']);
     * 
     * // Creates log entry with:
     * // - Request: POST /api.php?action=create&table=users
     * // - Response: 201 Created, {"id": 123}, 12 bytes
     * // - Execution: 0.045s (45ms)
     * // - User: john@example.com
     * // - IP: 192.168.1.100
     */
    private function logResponse($responseBody, int $statusCode, array $query): void
    {
        $executionTime = microtime(true) - $this->requestStartTime;
        
        $request = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'action' => $query['action'] ?? 'unknown',
            'table' => $query['table'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user' => $this->auth->getCurrentUser(),
            'query' => $query,
            'headers' => $this->getRequestHeaders(),
            'body' => $_POST ?: (json_decode(file_get_contents('php://input'), true) ?? [])
        ];

        $response = [
            'status_code' => $statusCode,
            'body' => $responseBody,
            'size' => strlen(json_encode($responseBody))
        ];

        $this->logger->logRequest($request, $response, $executionTime);
        
        // Record response metric
        if ($this->monitor) {
            $this->monitor->recordResponse(
                $statusCode,
                $executionTime * 1000, // Convert to milliseconds
                $response['size']
            );
        }
    }
}