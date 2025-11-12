<?php
declare(strict_types=1);

namespace App\Application;

// Re-export the existing Router logic moved from root namespace.
// NOTE: This is a direct copy; adjust namespaces for dependencies.

use App\Cache\CacheManager;
use App\Config\ApiConfig;
use App\Config\CacheConfig;
use App\Http\Action;
use App\Http\Response;
use App\Http\Middleware\RateLimitMiddleware;
use App\Security\RbacGuard;
use App\Http\Middleware\CorsMiddleware;
use App\Http\ErrorResponder;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\LoginController;
use App\Database\Database as Database;
use App\Database\SchemaInspector as SchemaInspector;
use App\Auth\Authenticator as Authenticator;
use App\Security\Rbac as Rbac;
use App\Security\RateLimiter;
use App\Observability\RequestLogger as RequestLogger;
use App\Observability\Monitor as Monitor;
use App\ApiGenerator;

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

    public function __construct(Database $db, Authenticator $auth)
    {
        $pdo = $db->getPdo();
        $this->db = $db;
        $this->inspector = new SchemaInspector($pdo);
        $this->api = new ApiGenerator($pdo);
        $this->auth = $auth;

        $this->apiConfig = ApiConfig::fromFile(__DIR__ . '/../../config/api.php');
        $this->authEnabled = $this->apiConfig->isAuthEnabled();
        // Normalize userRoles to array<string, list<string>>
        $userRolesMap = array_map(fn($r) => [$r], $this->apiConfig->getUserRoles());
        $this->rbac = new Rbac(
            $this->apiConfig->getRoles(),
            $userRolesMap
        );
        $this->rbacGuard = new RbacGuard($this->rbac);

        $this->logger = new RequestLogger($this->apiConfig->getLoggingConfig());
        $rateLimiter = new RateLimiter($this->apiConfig->getRateLimitConfig());
        $this->rateLimitMiddleware = new RateLimitMiddleware($rateLimiter, $this->logger, $this->monitor);
        $this->cors = new CorsMiddleware([]);
        $this->errors = new ErrorResponder($this->logger, $this->monitor, true);

        if ($this->apiConfig->isMonitoringEnabled()) {
            $this->monitor = new Monitor($this->apiConfig->getMonitoringConfig());
        }
        $cacheConfig = CacheConfig::fromFile(__DIR__ . '/../../config/cache.php');
        if ($cacheConfig->isEnabled()) {
            $this->cache = new CacheManager($cacheConfig->toArray());
        }
        $this->requestStartTime = microtime(true);
    }

    /**
     * @param array<string,mixed> $query
     */
    public function route(array $query): void
    {
        if ($this->cors->handlePreflight()) {
            return;
        }
        $this->cors->apply();

        $identifier = $this->getRateLimitIdentifier();
        if (!$this->rateLimitMiddleware->checkAndRespond($identifier)) {
            return;
        }

        if (($query['action'] ?? '') === Action::LOGIN && ($this->auth->config['auth_method'] ?? '') === 'jwt') {
            $controller = new LoginController($this->db, $this->auth, $this->logger, $this->monitor);
            [$payload, $status] = $controller->handle($query);
            $this->logResponse($payload, $status, $query);
            Response::json($payload, $status);
            return;
        }

        if ($this->authEnabled) {
            if (!$this->auth->authenticate()) {
                $this->logger->logAuth(
                    $this->auth->config['auth_method'] ?? 'unknown',
                    false,
                    $identifier,
                    'Authentication failed'
                );
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
                if ($this->monitor) {
                    $this->monitor->recordSecurityEvent('auth_success', [
                        'method' => $this->auth->config['auth_method'] ?? 'unknown',
                        'user' => $this->auth->getCurrentUser() ?? $identifier,
                    ]);
                }
            }
        }

        try {
            switch ($query['action'] ?? '') {
                case Action::TABLES:
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
                    $apiCtl = new ApiController($this->inspector, $this->api, $this->cache, $this->rbacGuard, $this->authEnabled);
                    $tuple = $apiCtl->list($this->auth->getCurrentUserRole(), $query['table'] ?? null, $query);
                    $payload = $tuple[0];
                    $status = $tuple[1];
                    $headers = $tuple[2] ?? [];
                    $this->logResponse($payload, $status, $query);
                    Response::json($payload, $status, $headers);
                    break;
                case Action::COUNT:
                    $apiCtl = new ApiController($this->inspector, $this->api, $this->cache, $this->rbacGuard, $this->authEnabled);
                    [$payload, $status] = $apiCtl->count($this->auth->getCurrentUserRole(), $query['table'] ?? null, $query);
                    $this->logResponse($payload, $status, $query);
                    Response::json($payload, $status);
                    break;
                case Action::READ:
                    $apiCtl = new ApiController($this->inspector, $this->api, $this->cache, $this->rbacGuard, $this->authEnabled);
                    [$payload, $status] = $apiCtl->read($this->auth->getCurrentUserRole(), $query['table'] ?? null, $query['id'] ?? null);
                    $this->logResponse($payload, $status, $query);
                    Response::json($payload, $status);
                    break;
                case Action::CREATE:
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        $this->logResponse(['error' => 'Method Not Allowed'], 405, $query);
                        Response::error('Method Not Allowed', 405);
                        break;
                    }
                    $data = $_POST;
                    if (empty($data) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === 0) {
                        $raw = file_get_contents('php://input');
                        $data = json_decode($raw === false ? '' : $raw, true) ?? [];
                    }
                    $apiCtl = new ApiController($this->inspector, $this->api, $this->cache, $this->rbacGuard, $this->authEnabled);
                    [$payload, $status] = $apiCtl->create($this->auth->getCurrentUserRole(), $query['table'] ?? null, $data);
                    $this->logResponse($payload, $status, $query);
                    Response::json($payload, $status);
                    break;
                case Action::UPDATE:
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        $this->logResponse(['error' => 'Method Not Allowed'], 405, $query);
                        Response::error('Method Not Allowed', 405);
                        break;
                    }
                    $data = $_POST;
                    if (empty($data) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === 0) {
                        $raw = file_get_contents('php://input');
                        $data = json_decode($raw === false ? '' : $raw, true) ?? [];
                    }
                    $apiCtl = new ApiController($this->inspector, $this->api, $this->cache, $this->rbacGuard, $this->authEnabled);
                    [$payload, $status] = $apiCtl->update($this->auth->getCurrentUserRole(), $query['table'] ?? null, $query['id'] ?? null, $data);
                    $this->logResponse($payload, $status, $query);
                    Response::json($payload, $status);
                    break;
                case Action::DELETE:
                    $apiCtl = new ApiController($this->inspector, $this->api, $this->cache, $this->rbacGuard, $this->authEnabled);
                    [$payload, $status] = $apiCtl->delete($this->auth->getCurrentUserRole(), $query['table'] ?? null, $query['id'] ?? null);
                    $this->logResponse($payload, $status, $query);
                    Response::json($payload, $status);
                    break;
                case Action::BULK_CREATE:
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        $this->logResponse(['error' => 'Method Not Allowed'], 405, $query);
                        Response::error('Method Not Allowed', 405);
                        break;
                    }
                    $raw = file_get_contents('php://input');
                    $rows = json_decode($raw === false ? '' : $raw, true) ?? [];
                    $apiCtl = new ApiController($this->inspector, $this->api, $this->cache, $this->rbacGuard, $this->authEnabled);
                    [$payload, $status] = $apiCtl->bulkCreate($this->auth->getCurrentUserRole(), $query['table'] ?? null, $rows);
                    $this->logResponse($payload, $status, $query);
                    Response::json($payload, $status);
                    break;
                case Action::BULK_DELETE:
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        $this->logResponse(['error' => 'Method Not Allowed'], 405, $query);
                        Response::error('Method Not Allowed', 405);
                        break;
                    }
                    $raw = file_get_contents('php://input');
                    $data = json_decode($raw === false ? '' : $raw, true) ?? [];
                    $ids = $data['ids'] ?? [];
                    $apiCtl = new ApiController($this->inspector, $this->api, $this->cache, $this->rbacGuard, $this->authEnabled);
                    [$payload, $status] = $apiCtl->bulkDelete($this->auth->getCurrentUserRole(), $query['table'] ?? null, $ids);
                    $this->logResponse($payload, $status, $query);
                    Response::json($payload, $status);
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

    private function getRateLimitIdentifier(): string
    {
        $user = $this->auth->getCurrentUser();
        if ($user) {
            return 'user:' . $user;
        }
        if ($this->apiConfig->getAuthMethod() === 'apikey') {
            $headers = $this->getRequestHeaders();
            $apiKey = $headers['X-API-Key'] ?? ($_GET['api_key'] ?? null);
            if ($apiKey) {
                return 'apikey:' . hash('sha256', $apiKey);
            }
        }
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? 'unknown';
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }
        return 'ip:' . $ip;
    }

    /**
     * @return array<string,string>
     */
    private function getRequestHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
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
     * @param mixed $responseBody
     * @param array<string,mixed> $query
     */
    private function logResponse($responseBody, int $statusCode, array $query): void
    {
        $executionTime = microtime(true) - $this->requestStartTime;
        $rawBody = file_get_contents('php://input');
        $request = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'action' => $query['action'] ?? 'unknown',
            'table' => $query['table'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user' => $this->auth->getCurrentUser(),
            'query' => $query,
            'headers' => $this->getRequestHeaders(),
            'body' => $_POST ?: (json_decode($rawBody === false ? '' : $rawBody, true) ?? [])
        ];
        $response = [
            'status_code' => $statusCode,
            'body' => $responseBody,
            'size' => strlen((string)json_encode($responseBody))
        ];
        $this->logger->logRequest($request, $response, $executionTime);
        if ($this->monitor) {
            $this->monitor->recordResponse(
                $statusCode,
                $executionTime * 1000,
                $response['size']
            );
        }
    }
}
