<?php
declare(strict_types=1);

namespace App\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * API Authenticator (canonical)
 *
 * Provides multiple authentication methods for securing API access.
 * Supports API keys, Basic Auth, JWT tokens, and OAuth (placeholder).
 *
 * Features:
 * - Multiple authentication methods (API Key, Basic Auth, JWT, OAuth)
 * - JWT token generation and validation
 * - Role-based access via JWT claims
 * - Configurable authentication requirements
 * - Automatic 401 responses for unauthorized access
 */
class Authenticator
{
	/**
	 * Authentication configuration
	 *
	 * @var array<string,mixed>
	 */
	public array $config;

	/**
	 * Database connection (optional, for database authentication)
	 *
	 * @var \PDO|null
	 */
	private ?\PDO $pdo = null;

	/**
	 * Currently authenticated user data
	 *
	 * @var array{ id?: int|string, username?: string, email?: string, role?: string }|null
	 */
	private ?array $currentUser = null;

	/**
	 * Initialize authenticator with configuration
	 *
	 * @param array $config Authentication configuration with keys:
	 *                      - auth_enabled: Enable/disable authentication (bool)
	 *                      - auth_method: Method to use ('apikey', 'basic', 'jwt', 'oauth')
	 *                      - api_keys: Array of valid API keys (for 'apikey' method)
	 *                      - basic_users: Array of username => password pairs (for 'basic')
	 *                      - jwt_secret: Secret key for JWT signing (for 'jwt')
	 *                      - jwt_issuer: JWT issuer claim (optional)
	 *                      - jwt_audience: JWT audience claim (optional)
	 */
	/**
	 * @param array<string,mixed> $config Authentication configuration
	 */
	public function __construct(array $config, ?\PDO $pdo = null)
	{
		$this->config = $config;
		$this->pdo = $pdo;
	}

	/**
	 * Authenticate the current request
	 *
	 * Validates credentials based on the configured authentication method.
	 * Returns true if authentication is disabled or credentials are valid.
	 *
	 * Supported methods:
	 * - apikey: Checks X-API-Key header or api_key query parameter
	 * - basic: HTTP Basic Authentication with username/password
	 * - jwt: Bearer token validation with JWT
	 * - oauth: OAuth bearer token (placeholder implementation)
	 */
	public function authenticate(): bool
	{
		if (empty($this->config['auth_enabled'])) {
			return true;
		}

		switch ($this->config['auth_method']) {
			case 'apikey':
				$headers = $this->getHeaders();
				$key = $headers['X-API-Key'] ?? ($_GET['api_key'] ?? null);
				return in_array($key, $this->config['api_keys'], true);

			case 'basic':
				if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
					$this->requireBasicAuth();
					return false;
				}
				$user = $_SERVER['PHP_AUTH_USER'];
				$pass = $_SERVER['PHP_AUTH_PW'];

				// Try database authentication first (if enabled and PDO available)
				if (!empty($this->config['use_database_auth']) && $this->pdo) {
					if ($this->authenticateFromDatabase($user, $pass)) {
						return true;
					}
				}

				// Fallback to config file authentication
				return isset($this->config['basic_users'][$user])
					&& $this->config['basic_users'][$user] === $pass;

			case 'jwt':
				$headers = $this->getHeaders();
				$authHeader = $headers['Authorization'] ?? '';
				if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
					$jwt = $matches[1];
					return $this->validateJwt($jwt);
				}
				return false;

			case 'oauth':
				// Placeholder for OAuth token validation
				$headers = $this->getHeaders();
				$authHeader = $headers['Authorization'] ?? '';
				if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
					$token = $matches[1];
					// TODO: Validate $token with OAuth provider
					return false;
				}
				return false;

			default:
				return false;
		}
	}

	/**
	 * Require authentication or exit with 401 Unauthorized
	 */
	public function requireAuth(): void
	{
		if (!$this->authenticate()) {
			http_response_code(401);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Unauthorized']);
			exit;
		}
	}

	/**
	 * Create a JWT token with custom payload
	 */
	/**
	 * Create a JWT token with custom payload
	 *
	 * @param array<string,mixed> $payload
	 */
	public function createJwt(array $payload, int $expireSeconds = 3600): string
	{
		$now = time();
		$payload = array_merge([
			'iat' => $now,
			'exp' => $now + $expireSeconds,
			'iss' => $this->config['jwt_issuer'] ?? '',
			'aud' => $this->config['jwt_audience'] ?? '',
		], $payload);

		return JWT::encode($payload, $this->config['jwt_secret'], 'HS256');
	}

	/**
	 * Validate a JWT token
	 */
	public function validateJwt(string $jwt): bool
	{
		try {
			$decoded = JWT::decode($jwt, new Key($this->config['jwt_secret'], 'HS256'));
			// Optionally: Validate iss/aud/exp
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Get HTTP request headers
	 *
	 * @return array<string,string>
	 */
	private function getHeaders(): array
	{
		if (function_exists('getallheaders')) {
			return getallheaders();
		}
		// Fallback
		$headers = [];
		foreach ($_SERVER as $name => $value) {
			if (str_starts_with($name, 'HTTP_')) {
				$header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
				$headers[$header] = (string)$value;
			}
		}
		return $headers;
	}

	private function requireBasicAuth(): void
	{
		header('WWW-Authenticate: Basic realm="API"');
		http_response_code(401);
		echo json_encode(['error' => 'Unauthorized']);
		exit;
	}

	public function getCurrentUser(): ?string
	{
		// Basic Auth
		if (($this->config['auth_method'] ?? null) === 'basic' && isset($_SERVER['PHP_AUTH_USER'])) {
			return $_SERVER['PHP_AUTH_USER'];
		}
		// JWT
		if (($this->config['auth_method'] ?? null) === 'jwt') {
			$headers = $this->getHeaders();
			$authHeader = $headers['Authorization'] ?? '';
			if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
				try {
					$decoded = JWT::decode($matches[1], new Key($this->config['jwt_secret'], 'HS256'));
					return $decoded->sub ?? null;
				} catch (\Exception $e) {
				}
			}
		}
		// For API key or other methods, you can add user tracking as needed
		return null;
	}

	public function getCurrentUserRole(): ?string
	{
		// If user authenticated from database, return their role
		if ($this->currentUser) {
			return $this->currentUser['role'] ?? null;
		}

		// Check JWT token for role claim
		if (($this->config['auth_method'] ?? null) === 'jwt') {
			$headers = $this->getHeaders();
			$authHeader = $headers['Authorization'] ?? '';
			if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
				try {
					$decoded = JWT::decode(
						$matches[1],
						new Key($this->config['jwt_secret'], 'HS256')
					);
					// Return role from JWT claim
					return $decoded->role ?? null;
				} catch (\Exception $e) {
					// Token invalid or expired
				}
			}
		}

		// Fallback to config-based role mapping
		$user = $this->getCurrentUser();
		if ($user && !empty($this->config['user_roles'][$user])) {
			return $this->config['user_roles'][$user];
		}

		// For API key authentication, use default role
		if (($this->config['auth_method'] ?? null) === 'apikey' && !empty($this->config['api_key_role'])) {
			return $this->config['api_key_role'];
		}

		return null;
	}

	/**
	 * Authenticate user from database
	 */
	private function authenticateFromDatabase(string $username, string $password): bool
	{
		if (!$this->pdo) {
			return false;
		}

		try {
			$stmt = $this->pdo->prepare(
				"SELECT id, username, email, password_hash, role, active \
				 FROM api_users \
				 WHERE username = :username AND active = 1"
			);
			$stmt->execute(['username' => $username]);
			$user = $stmt->fetch(\PDO::FETCH_ASSOC);

			if (!$user) {
				return false;
			}

			// Verify password hash
			if (!password_verify($password, $user['password_hash'])) {
				return false;
			}

			// Update last login timestamp
			$updateStmt = $this->pdo->prepare(
				"UPDATE api_users SET last_login = NOW() WHERE id = :id"
			);
			$updateStmt->execute(['id' => $user['id']]);

			// Store current user data (including role from database)
			$this->currentUser = [
				'id' => $user['id'],
				'username' => $user['username'],
				'email' => $user['email'],
				'role' => $user['role']
			];

			return true;

		} catch (\PDOException $e) {
			// Log error but don't expose database details
			error_log("Database authentication error: " . $e->getMessage());
			return false;
		}
	}
}
