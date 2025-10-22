<?php

namespace App;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Authenticator
{
    public array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

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

    public function requireAuth()
    {
        if (!$this->authenticate()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

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
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    private function requireBasicAuth()
    {
        header('WWW-Authenticate: Basic realm="API"');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // ... existing code ...

    public function getCurrentUser(): ?string
    {
        // Basic Auth
        if ($this->config['auth_method'] === 'basic' && isset($_SERVER['PHP_AUTH_USER'])) {
            return $_SERVER['PHP_AUTH_USER'];
        }
        // JWT
        if ($this->config['auth_method'] === 'jwt') {
            $headers = $this->getHeaders();
            $authHeader = $headers['Authorization'] ?? '';
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                try {
                    $decoded = \Firebase\JWT\JWT::decode($matches[1], new \Firebase\JWT\Key($this->config['jwt_secret'], 'HS256'));
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
        $user = $this->getCurrentUser();
        if ($user && !empty($this->config['user_roles'][$user])) {
            return $this->config['user_roles'][$user];
        }
        // For API key, assign a default role (optional)
        return null;
    }
}
