<?php

namespace App;

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
    private RateLimiter $rateLimiter;
    private RequestLogger $logger;
    private array $apiConfig;
    private bool $authEnabled;
    private float $requestStartTime;

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

        $this->apiConfig = require __DIR__ . '/../config/api.php';
        $this->authEnabled = $this->apiConfig['auth_enabled'] ?? true;
        $this->rbac = new Rbac($this->apiConfig['roles'] ?? [], $this->apiConfig['user_roles'] ?? []);
        
        // Initialize rate limiter with config
        $this->rateLimiter = new RateLimiter($this->apiConfig['rate_limit'] ?? []);
        
        // Initialize request logger with config
        $this->logger = new RequestLogger($this->apiConfig['logging'] ?? []);
        
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
    private function enforceRbac(string $action, ?string $table = null)
    {
        if (!$this->authEnabled) {
            return; // skip RBAC if auth is disabled
        }
        $role = $this->auth->getCurrentUserRole();
        if (!$role) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: No role assigned']);
            exit;
        }
        if (!$table) return;
        if (!$this->rbac->isAllowed($role, $table, $action)) {
            http_response_code(403);
            echo json_encode(['error' => "Forbidden: $role cannot $action on $table"]);
            exit;
        }
    }

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
        header('Content-Type: application/json');

        // ========================================
        // RATE LIMITING CHECK
        // ========================================
        $identifier = $this->getRateLimitIdentifier();
        if (!$this->rateLimiter->checkLimit($identifier)) {
            // Log rate limit hit
            $this->logger->logRateLimit(
                $identifier,
                $this->rateLimiter->getRequestCount($identifier),
                $this->rateLimiter->getRemainingRequests($identifier) + $this->rateLimiter->getRequestCount($identifier)
            );
            $this->rateLimiter->sendRateLimitResponse($identifier);
        }

        // Add rate limit headers to response
        foreach ($this->rateLimiter->getHeaders($identifier) as $name => $value) {
            header("$name: $value");
        }

        // JWT login endpoint (always accessible if method is JWT)
        if (($query['action'] ?? '') === 'login' && ($this->auth->config['auth_method'] ?? '') === 'jwt') {
            $post = $_POST;
            $users = $this->auth->config['basic_users'] ?? [];
            $user = $post['username'] ?? '';
            $pass = $post['password'] ?? '';
            if (isset($users[$user]) && $users[$user] === $pass) {
                $this->logger->logAuth('jwt', true, $user);
                $token = $this->auth->createJwt(['sub' => $user]);
                $this->logResponse(['token' => $token], 200, $query);
                echo json_encode(['token' => $token]);
            } else {
                $this->logger->logAuth('jwt', false, $user, 'Invalid credentials');
                http_response_code(401);
                $this->logResponse(['error' => 'Invalid credentials'], 401, $query);
                echo json_encode(['error' => 'Invalid credentials']);
            }
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
                $this->auth->requireAuth();
            } else {
                $this->logger->logAuth(
                    $this->auth->config['auth_method'] ?? 'unknown',
                    true,
                    $this->auth->getCurrentUser() ?? $identifier
                );
            }
        }

        try {
            switch ($query['action'] ?? '') {
                case 'tables':
                    // No per-table RBAC needed
                    $result = $this->inspector->getTables();
                    $this->logResponse($result, 200, $query);
                    echo json_encode($result);
                    break;

                case 'columns':
                    if (isset($query['table'])) {
                        if (!Validator::validateTableName($query['table'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Invalid table name']);
                            break;
                        }
                        $this->enforceRbac('read', $query['table']);
                        $result = $this->inspector->getColumns($query['table']);
                        $this->logResponse($result, 200, $query);
                        echo json_encode($result);
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Missing table parameter']);
                    }
                    break;

                case 'list':
                    if (isset($query['table'])) {
                        if (!Validator::validateTableName($query['table'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Invalid table name']);
                            break;
                        }
                        $this->enforceRbac('list', $query['table']);
                        $opts = [
                            'filter' => $query['filter'] ?? null,
                            'sort' => $query['sort'] ?? null,
                            'page' => Validator::validatePage($query['page'] ?? 1),
                            'page_size' => Validator::validatePageSize($query['page_size'] ?? 20),
                            'fields' => $query['fields'] ?? null,
                        ];
                        // Validate sort if provided
                        if (isset($opts['sort']) && !Validator::validateSort($opts['sort'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Invalid sort parameter']);
                            break;
                        }
                        $result = $this->api->list($query['table'], $opts);
                        $this->logResponse($result, 200, $query);
                        echo json_encode($result);
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Missing table parameter']);
                    }
                    break;

                case 'count':
                    if (isset($query['table'])) {
                        if (!Validator::validateTableName($query['table'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Invalid table name']);
                            break;
                        }
                        $this->enforceRbac('list', $query['table']); // Use 'list' permission for count
                        $opts = [
                            'filter' => $query['filter'] ?? null,
                        ];
                        $result = $this->api->count($query['table'], $opts);
                        $this->logResponse($result, 200, $query);
                        echo json_encode($result);
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Missing table parameter']);
                    }
                    break;

                case 'read':
                    if (isset($query['table'], $query['id'])) {
                        if (!Validator::validateTableName($query['table'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Invalid table name']);
                            break;
                        }
                        if (!Validator::validateId($query['id'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Invalid id parameter']);
                            break;
                        }
                        $this->enforceRbac('read', $query['table']);
                        $result = $this->api->read($query['table'], $query['id']);
                        $this->logResponse($result, 200, $query);
                        echo json_encode($result);
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Missing table or id parameter']);
                    }
                    break;

                case 'create':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method Not Allowed']);
                        break;
                    }
                    if (!isset($query['table']) || !Validator::validateTableName($query['table'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid or missing table parameter']);
                        break;
                    }
                    $this->enforceRbac('create', $query['table']);
                    $data = $_POST;
                    if (empty($data) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === 0) {
                        $data = json_decode(file_get_contents('php://input'), true) ?? [];
                    }
                    $result = $this->api->create($query['table'], $data);
                    $this->logResponse($result, 201, $query);
                    echo json_encode($result);
                    break;

                case 'update':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method Not Allowed']);
                        break;
                    }
                    if (!isset($query['table']) || !Validator::validateTableName($query['table'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid or missing table parameter']);
                        break;
                    }
                    if (!isset($query['id']) || !Validator::validateId($query['id'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid or missing id parameter']);
                        break;
                    }
                    $this->enforceRbac('update', $query['table']);
                    $data = $_POST;
                    if (empty($data) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === 0) {
                        $data = json_decode(file_get_contents('php://input'), true) ?? [];
                    }
                    $result = $this->api->update($query['table'], $query['id'], $data);
                    $this->logResponse($result, 200, $query);
                    echo json_encode($result);
                    break;

                case 'delete':
                    if (isset($query['table'], $query['id'])) {
                        if (!Validator::validateTableName($query['table'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Invalid table name']);
                            break;
                        }
                        if (!Validator::validateId($query['id'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Invalid id parameter']);
                            break;
                        }
                        $this->enforceRbac('delete', $query['table']);
                        $result = $this->api->delete($query['table'], $query['id']);
                        $this->logResponse($result, 200, $query);
                        echo json_encode($result);
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Missing table or id parameter']);
                    }
                    break;

                case 'bulk_create':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method Not Allowed']);
                        break;
                    }
                    if (!isset($query['table']) || !Validator::validateTableName($query['table'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid or missing table parameter']);
                        break;
                    }
                    $this->enforceRbac('create', $query['table']);
                    $data = json_decode(file_get_contents('php://input'), true) ?? [];
                    if (!is_array($data) || empty($data)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid or empty JSON array']);
                        break;
                    }
                    $result = $this->api->bulkCreate($query['table'], $data);
                    $this->logResponse($result, 201, $query);
                    echo json_encode($result);
                    break;

                case 'bulk_delete':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method Not Allowed']);
                        break;
                    }
                    if (!isset($query['table']) || !Validator::validateTableName($query['table'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid or missing table parameter']);
                        break;
                    }
                    $this->enforceRbac('delete', $query['table']);
                    $data = json_decode(file_get_contents('php://input'), true) ?? [];
                    if (!isset($data['ids']) || !is_array($data['ids']) || empty($data['ids'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid or empty ids array. Send JSON with "ids" field.']);
                        break;
                    }
                    $result = $this->api->bulkDelete($query['table'], $data['ids']);
                    $this->logResponse($result, 200, $query);
                    echo json_encode($result);
                    break;

                case 'openapi':
                    // No per-table RBAC needed by default
                    $result = OpenApiGenerator::generate(
                        $this->inspector->getTables(),
                        $this->inspector
                    );
                    $this->logResponse($result, 200, $query);
                    echo json_encode($result);
                    break;

                default:
                    http_response_code(400);
                    $error = ['error' => 'Invalid action'];
                    $this->logResponse($error, 400, $query);
                    echo json_encode($error);
            }
        } catch (\Throwable $e) {
            $this->logger->logError($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'query' => $query
            ]);
            http_response_code(500);
            $error = ['error' => $e->getMessage()];
            $this->logResponse($error, 500, $query);
            echo json_encode($error);
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
        if (($this->apiConfig['auth_method'] ?? '') === 'apikey') {
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
    }
}