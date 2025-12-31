<?php
namespace App\Http\Controllers;

use App\Auth\Authenticator;
use App\Database\Database; 
use App\Observability\RequestLogger; 
use App\Observability\Monitor; 

/**
 * LoginController
 * Extracted from Router::route() Phase 1 without changing behavior.
 */
class LoginController
{
    private Database $db;
    private Authenticator $auth;
    private RequestLogger $logger;
    private ?Monitor $monitor;

    public function __construct(Database $db, Authenticator $auth, RequestLogger $logger, ?Monitor $monitor = null)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->logger = $logger;
        $this->monitor = $monitor;
    }

    /**
     * Handle JWT login. Returns [payload, statusCode].
     * Mirrors previous Router logic, including DB auth fallback and events.
     *
     * @param array<string,mixed> $query
     * @return array{0:mixed,1:int}
     */
    public function handle(array $query): array
    {
        // Handle both JSON body and form data
        $post = $_POST;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $jsonInput = file_get_contents('php://input');
            $post = json_decode($jsonInput === false ? '' : $jsonInput, true) ?? [];
        }
        $user = $post['username'] ?? '';
        $pass = $post['password'] ?? '';

        $authenticated = false;
        $userRole = 'readonly';

        // Try database authentication first (if enabled)
        if (!empty($this->auth->config['use_database_auth'])) {
            $pdo = $this->db->getPdo();
            $stmt = $pdo->prepare(
                "SELECT id, username, email, password_hash, role, active 
                 FROM api_users 
                 WHERE username = :username AND active = 1"
            );
            $stmt->execute(['username' => $user]);
            $dbUser = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($dbUser && password_verify($pass, $dbUser['password_hash'])) {
                $authenticated = true;
                $userRole = $dbUser['role'];

                // Update last login
                $updateStmt = $pdo->prepare("UPDATE api_users SET last_login = NOW() WHERE id = :id");
                $updateStmt->execute(['id' => $dbUser['id']]);
            }
        }

        // Fallback to config file authentication
        if (!$authenticated) {
            $users = $this->auth->config['basic_users'] ?? [];
            if (isset($users[$user]) && $users[$user] === $pass) {
                $authenticated = true;
                $userRole = $this->auth->config['user_roles'][$user] ?? 'readonly';
            }
        }

        if ($authenticated) {
            $this->logger->logAuth('jwt', true, $user);

            if ($this->monitor) {
                $this->monitor->recordSecurityEvent('auth_success', [
                    'method' => 'jwt',
                    'user' => $user,
                ]);
            }

            // Create JWT with user role
            $payload = ['sub' => $user, 'role' => $userRole];
            $token = $this->auth->createJwt($payload);

            // Decode token to get expiration time
            $tokenParts = explode('.', $token);
            $tokenPayload = json_decode(base64_decode($tokenParts[1]), true);
            $expiresAt = $tokenPayload['exp'] ?? (time() + 3600);

            $response = [
                'token' => $token,
                'expires_at' => $expiresAt,
                'user' => $user,
                'role' => $userRole
            ];
            return [$response, 200];
        }

        $this->logger->logAuth('jwt', false, $user, 'Invalid credentials');
        if ($this->monitor) {
            $this->monitor->recordSecurityEvent('auth_failure', [
                'method' => 'jwt',
                'reason' => 'Invalid credentials',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
        }
        return [["error" => "Invalid credentials"], 401];
    }
}
