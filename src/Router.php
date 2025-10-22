<?php

namespace App;

class Router
{
    private Database $db;
    private SchemaInspector $inspector;
    private ApiGenerator $api;
    public Authenticator $auth;
    private Rbac $rbac;
    private array $apiConfig;
    private bool $authEnabled;

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

        // JWT login endpoint (always accessible if method is JWT)
        if (($query['action'] ?? '') === 'login' && ($this->auth->config['auth_method'] ?? '') === 'jwt') {
            $post = $_POST;
            $users = $this->auth->config['basic_users'] ?? [];
            $user = $post['username'] ?? '';
            $pass = $post['password'] ?? '';
            if (isset($users[$user]) && $users[$user] === $pass) {
                $token = $this->auth->createJwt(['sub' => $user]);
                echo json_encode(['token' => $token]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
            }
            return;
        }

        // Only require authentication if enabled
        if ($this->authEnabled) {
            $this->auth->requireAuth();
        }

        try {
            switch ($query['action'] ?? '') {
                case 'tables':
                    // No per-table RBAC needed
                    echo json_encode($this->inspector->getTables());
                    break;

                case 'columns':
                    if (isset($query['table'])) {
                        if (!Validator::validateTableName($query['table'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Invalid table name']);
                            break;
                        }
                        $this->enforceRbac('read', $query['table']);
                        echo json_encode($this->inspector->getColumns($query['table']));
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
                        echo json_encode($this->api->list($query['table'], $opts));
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
                        echo json_encode($this->api->count($query['table'], $opts));
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
                        echo json_encode($this->api->read($query['table'], $query['id']));
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
                    echo json_encode($this->api->create($query['table'], $data));
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
                    echo json_encode($this->api->update($query['table'], $query['id'], $data));
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
                        echo json_encode($this->api->delete($query['table'], $query['id']));
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
                    echo json_encode($this->api->bulkCreate($query['table'], $data));
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
                    echo json_encode($this->api->bulkDelete($query['table'], $data['ids']));
                    break;

                case 'openapi':
                    // No per-table RBAC needed by default
                    echo json_encode(OpenApiGenerator::generate(
                        $this->inspector->getTables(),
                        $this->inspector
                    ));
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}