<?php

namespace App;

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
     * Checks if the current user (via Authenticator) is allowed to perform $action on $table.
     * If not, sends a 403 response and exits.
     * No-op if auth/rbac is disabled.
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
     * Uses authenticated user, API key, or IP address (in that order)
     *
     * @return string Unique identifier for rate limiting
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
     * Get request headers (helper method)
     *
     * @return array Associative array of headers
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
     * @param mixed $responseBody Response body
     * @param int $statusCode HTTP status code
     * @param array $query Query parameters
     * @return void
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