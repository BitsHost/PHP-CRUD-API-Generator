<?php
/**
 * Resolve TenantContext from auth / headers for the current request.
 *
 * @package   PHP-CRUD-API-Generator
 * @author    BitsHost
 * @copyright 2026 BitsHost
 * @license   MIT License
 */
declare(strict_types=1);

namespace App\Security;

use App\Auth\Authenticator;

/**
 * Fail closed when multitenancy is enabled but no tenant can be resolved.
 */
final class TenantResolver
{
    public function __construct(private TenantPolicy $policy)
    {
    }

    /**
     * @return TenantContext|null Null when multitenancy is disabled
     * @throws \RuntimeException When enabled but tenant cannot be resolved
     */
    public function resolve(Authenticator $auth): ?TenantContext
    {
        if (!$this->policy->isEnabled()) {
            return null;
        }

        $id = match ($this->policy->getSource()) {
            'header' => $this->fromHeader(),
            'request_attr' => $this->fromRequestAttr(),
            default => $this->fromJwtOrAuth($auth),
        };

        if ($id === null || $id === '') {
            throw new \RuntimeException(
                'Multitenancy is enabled but no tenant id could be resolved for this request'
            );
        }

        return new TenantContext($id);
    }

    private function fromJwtOrAuth(Authenticator $auth): ?string
    {
        // Prefer JWT claim when present
        $claim = $this->policy->getClaim();
        $fromJwt = $auth->getJwtClaim($claim);
        if ($fromJwt !== null && $fromJwt !== '') {
            return (string)$fromJwt;
        }

        // Basic auth: map username → tenant
        $user = $auth->getCurrentUser();
        if ($user !== null) {
            $map = $this->policy->getBasicUserTenants();
            if (isset($map[$user]) && $map[$user] !== '') {
                return $map[$user];
            }
        }

        // API key: single configured tenant
        $method = $auth->config['auth_method'] ?? null;
        if ($method === 'apikey' && $this->policy->getApiKeyTenant() !== null) {
            return $this->policy->getApiKeyTenant();
        }

        // Optional trusted header fallback only if source was jwt but claim missing
        // (gateway may inject header after auth). Keep strict: only when header source.
        return null;
    }

    private function fromHeader(): ?string
    {
        $name = $this->policy->getHeader();
        $headers = $this->getHeaders();
        $value = $headers[$name] ?? $headers[strtolower($name)] ?? null;
        if ($value === null) {
            // Common CGI variants
            $cgi = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $value = $_SERVER[$cgi] ?? null;
        }
        return $value !== null ? trim((string)$value) : null;
    }

    private function fromRequestAttr(): ?string
    {
        // Allow a front controller / upMVC to set this before routing
        if (isset($GLOBALS['PHPCRUD_TENANT_ID'])) {
            return trim((string)$GLOBALS['PHPCRUD_TENANT_ID']);
        }
        return isset($_SERVER['PHPCRUD_TENANT_ID'])
            ? trim((string)$_SERVER['PHPCRUD_TENANT_ID'])
            : null;
    }

    /**
     * @return array<string,string>
     */
    private function getHeaders(): array
    {
        if (function_exists('getallheaders')) {
            /** @var array<string,string> $h */
            $h = getallheaders();
            return $h;
        }
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$header] = (string)$value;
            }
        }
        return $headers;
    }
}
