<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Security\TenantPolicy;
use App\Security\TenantContext;
use App\Security\TenantResolver;
use App\Auth\Authenticator;
use App\Database\SchemaInspector;

final class TenantPolicyTest extends TestCase
{
    public function testDisabledByDefault(): void
    {
        $policy = new TenantPolicy([]);
        $this->assertFalse($policy->isEnabled());
        $this->assertFalse($policy->isTableConfigured('cars'));
    }

    public function testTableAllowlist(): void
    {
        $policy = new TenantPolicy([
            'enabled' => true,
            'tables' => ['cars', 'locations'],
        ]);
        $this->assertTrue($policy->isTableConfigured('cars'));
        $this->assertFalse($policy->isTableConfigured('payments'));
    }

    public function testWildcardTables(): void
    {
        $policy = new TenantPolicy(['enabled' => true, 'tables' => ['*']]);
        $this->assertTrue($policy->isTableConfigured('anything'));
    }

    public function testAppliesToRequiresColumn(): void
    {
        $inspector = $this->createMock(SchemaInspector::class);
        $inspector->method('hasColumn')->willReturnCallback(
            fn(string $table, string $col) => $table === 'cars' && $col === 'tenant_id'
        );

        $policy = new TenantPolicy([
            'enabled' => true,
            'column' => 'tenant_id',
            'require_column' => true,
            'tables' => ['*'],
        ]);

        $this->assertTrue($policy->appliesTo('cars', $inspector));
        $this->assertFalse($policy->appliesTo('meta', $inspector));
        $denied = $policy->denyIfNotReady('meta', $inspector);
        $this->assertNotNull($denied);
        $this->assertSame(403, $denied[1]);
    }

    public function testTenantContextRejectsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TenantContext('  ');
    }

    public function testResolverReturnsNullWhenDisabled(): void
    {
        $policy = new TenantPolicy(['enabled' => false]);
        $resolver = new TenantResolver($policy);
        $auth = new Authenticator(['auth_enabled' => false]);
        $this->assertNull($resolver->resolve($auth));
    }

    public function testResolverFailsClosedWhenEnabledWithoutTenant(): void
    {
        $policy = new TenantPolicy([
            'enabled' => true,
            'source' => 'jwt',
        ]);
        $resolver = new TenantResolver($policy);
        $auth = new Authenticator([
            'auth_enabled' => true,
            'auth_method' => 'apikey',
            'api_keys' => ['k'],
        ]);
        $this->expectException(\RuntimeException::class);
        $resolver->resolve($auth);
    }

    public function testResolverUsesApiKeyTenant(): void
    {
        $policy = new TenantPolicy([
            'enabled' => true,
            'source' => 'jwt',
            'api_key_tenant' => '42',
        ]);
        $resolver = new TenantResolver($policy);
        $auth = new Authenticator([
            'auth_enabled' => true,
            'auth_method' => 'apikey',
            'api_keys' => ['k'],
        ]);
        $ctx = $resolver->resolve($auth);
        $this->assertNotNull($ctx);
        $this->assertSame('42', $ctx->getId());
    }
}
