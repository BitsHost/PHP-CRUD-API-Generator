<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Security\TenantContext;
use App\Security\TenantPolicy;
use App\ApiGenerator;
use App\Database\SchemaInspector;
use App\Http\Controllers\ApiController;
use App\Security\Rbac;
use App\Security\RbacGuard;
use App\Security\TablePolicy;

final class MultitenancyTest extends TestCase
{
    public function testControllerDeniesTenantTableWithoutColumn(): void
    {
        $inspector = $this->createMock(SchemaInspector::class);
        $inspector->method('hasColumn')->willReturn(false);
        $inspector->method('getTables')->willReturn(['cars']);

        $api = $this->createMock(ApiGenerator::class);
        $api->expects($this->once())->method('setTenantScope');

        $rbac = new Rbac(['admin' => ['*' => ['list', 'read', 'create', 'update', 'delete']]], []);
        $ctl = new ApiController(
            $inspector,
            $api,
            null,
            new RbacGuard($rbac),
            false,
            new TablePolicy([], []),
            $rbac,
            new TenantPolicy([
                'enabled' => true,
                'column' => 'tenant_id',
                'tables' => ['*'],
                'require_column' => true,
            ]),
            new TenantContext('9')
        );

        [$payload, $status] = $ctl->list(null, 'cars', []);
        $this->assertSame(403, $status);
        $this->assertArrayHasKey('error', $payload);
        $this->assertStringContainsString('tenant_id', $payload['error']);
    }

    public function testControllerAllowsWhenColumnPresent(): void
    {
        $inspector = $this->createMock(SchemaInspector::class);
        $inspector->method('hasColumn')->willReturn(true);
        $inspector->method('getTables')->willReturn(['cars']);

        $api = $this->createMock(ApiGenerator::class);
        $api->expects($this->once())->method('setTenantScope');
        $api->expects($this->once())->method('list')->willReturn([
            'data' => [['id' => 1, 'tenant_id' => '9']],
            'meta' => ['total' => 1, 'page' => 1, 'page_size' => 20, 'pages' => 1],
        ]);

        $rbac = new Rbac(['admin' => ['*' => ['list', 'read', 'create', 'update', 'delete']]], []);
        $ctl = new ApiController(
            $inspector,
            $api,
            null,
            new RbacGuard($rbac),
            false,
            new TablePolicy([], []),
            $rbac,
            new TenantPolicy([
                'enabled' => true,
                'column' => 'tenant_id',
                'tables' => ['cars'],
                'require_column' => true,
            ]),
            new TenantContext('9')
        );

        [$payload, $status] = $ctl->list(null, 'cars', []);
        $this->assertSame(200, $status);
        $this->assertSame('9', $payload['data'][0]['tenant_id']);
    }

    public function testDisabledMultitenancyDoesNotRequireTenant(): void
    {
        $inspector = $this->createMock(SchemaInspector::class);
        $api = $this->createMock(ApiGenerator::class);
        $api->expects($this->once())->method('setTenantScope');
        $api->expects($this->once())->method('list')->willReturn([
            'data' => [],
            'meta' => ['total' => 0, 'page' => 1, 'page_size' => 20, 'pages' => 0],
        ]);

        $rbac = new Rbac(['admin' => ['*' => ['list']]], []);
        $ctl = new ApiController(
            $inspector,
            $api,
            null,
            new RbacGuard($rbac),
            false,
            new TablePolicy([], []),
            $rbac,
            new TenantPolicy(['enabled' => false]),
            null
        );

        [$payload, $status] = $ctl->list(null, 'cars', []);
        $this->assertSame(200, $status);
        $this->assertSame([], $payload['data']);
    }

    public function testTenantContextId(): void
    {
        $ctx = new TenantContext('acme-42');
        $this->assertSame('acme-42', $ctx->getId());
    }
}
