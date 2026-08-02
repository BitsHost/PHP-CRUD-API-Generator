<?php
/**
 * Hardening tests: TablePolicy, RBAC table visibility, method contract notes.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Security\TablePolicy;
use App\Security\Rbac;
use App\Http\Controllers\ApiController;
use App\Security\RbacGuard;
use App\Database\SchemaInspector;
use App\ApiGenerator;
use App\Support\Validator;

final class HardeningTest extends TestCase
{
    public function testTablePolicyAllowlistAndDenylist(): void
    {
        $policy = new TablePolicy(['users', 'orders', 'api_users'], ['api_users', 'secrets']);

        $this->assertTrue($policy->isAllowed('users'));
        $this->assertTrue($policy->isAllowed('orders'));
        $this->assertFalse($policy->isAllowed('api_users')); // denied wins
        $this->assertFalse($policy->isAllowed('products')); // not in allowlist
        $this->assertSame(['users', 'orders'], $policy->filter(['users', 'orders', 'api_users', 'products']));
    }

    public function testTablePolicyEmptyAllowlistMeansAllExceptDenied(): void
    {
        $policy = new TablePolicy([], ['api_users']);
        $this->assertTrue($policy->isAllowed('users'));
        $this->assertFalse($policy->isAllowed('api_users'));
    }

    public function testRbacFiltersInvisibleTables(): void
    {
        $rbac = new Rbac([
            'readonly' => [
                '*' => ['list', 'read'],
                'api_users' => [],
            ],
            'users_manager' => [
                'users' => ['list', 'read', 'create', 'update'],
            ],
        ], []);

        $all = ['users', 'orders', 'api_users'];
        $this->assertSame(['users', 'orders'], $rbac->filterVisibleTables('readonly', $all));
        $this->assertSame(['users'], $rbac->filterVisibleTables('users_manager', $all));
    }

    public function testTablesEndpointAppliesPolicyAndRbac(): void
    {
        $inspector = $this->createMock(SchemaInspector::class);
        $inspector->method('getTables')->willReturn(['users', 'orders', 'api_users', 'secrets']);

        $api = $this->createMock(ApiGenerator::class);
        $rbac = new Rbac([
            'readonly' => [
                '*' => ['list', 'read'],
                'api_users' => [],
            ],
        ], []);
        $guard = new RbacGuard($rbac);
        $policy = new TablePolicy([], ['api_users', 'secrets']);

        $ctl = new ApiController($inspector, $api, null, $guard, true, $policy, $rbac);
        [$payload, $status] = $ctl->tables('readonly');

        $this->assertSame(200, $status);
        $this->assertSame(['users', 'orders'], $payload);
    }

    public function testTablesEndpointForbiddenWithoutRoleWhenAuthOn(): void
    {
        $inspector = $this->createMock(SchemaInspector::class);
        $inspector->method('getTables')->willReturn(['users']);
        $api = $this->createMock(ApiGenerator::class);
        $rbac = new Rbac(['readonly' => ['*' => ['list']]], []);
        $ctl = new ApiController($inspector, $api, null, new RbacGuard($rbac), true, new TablePolicy(), $rbac);

        [$payload, $status] = $ctl->tables(null);
        $this->assertSame(403, $status);
        $this->assertArrayHasKey('error', $payload);
    }

    public function testDeniedTableBlocksList(): void
    {
        $inspector = $this->createMock(SchemaInspector::class);
        $api = $this->createMock(ApiGenerator::class);
        $rbac = new Rbac(['admin' => ['*' => ['list', 'read', 'create', 'update', 'delete']]], []);
        $ctl = new ApiController(
            $inspector,
            $api,
            null,
            new RbacGuard($rbac),
            true,
            new TablePolicy([], ['api_users']),
            $rbac
        );

        [$payload, $status] = $ctl->list('admin', 'api_users', []);
        $this->assertSame(403, $status);
        $this->assertStringContainsString('not exposed', $payload['error']);
    }

    public function testBetweenOperatorIsValid(): void
    {
        $this->assertTrue(Validator::validateOperator('between'));
    }
}
