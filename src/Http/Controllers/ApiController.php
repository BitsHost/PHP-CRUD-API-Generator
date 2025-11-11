<?php
namespace App\Http\Controllers;

use App\SchemaInspector;
use App\ApiGenerator;
use App\Cache\CacheManager;
use App\Security\RbacGuard;
use App\Support\QueryValidator as QV;

class ApiController
{
    public function __construct(
        private SchemaInspector $inspector,
        private ApiGenerator $api,
        private ?CacheManager $cache,
        private RbacGuard $rbacGuard,
        private bool $authEnabled
    ) {}

    // ==================== Schema endpoints ====================
    public function tables(): array { return [$this->inspector->getTables(), 200]; }

    public function columns(?string $role, ?string $table): array
    {
        if (!$table || !QV::table($table)) {
            return [["error" => "Invalid table name"], 400];
        }
        $this->rbacGuard->guard($this->authEnabled, $role, $table, 'read');
        return [$this->inspector->getColumns($table), 200];
    }

    // ==================== Data endpoints ====================
    public function list(?string $role, ?string $table, array $query): array
    {
        if (!$table || !QV::table($table)) {
            return [["error" => "Invalid table name"], 400];
        }
        $this->rbacGuard->guard($this->authEnabled, $role, $table, 'list');
        $opts = [
            'filter' => $query['filter'] ?? null,
            'sort' => $query['sort'] ?? null,
            'page' => QV::page($query['page'] ?? 1),
            'page_size' => QV::pageSize($query['page_size'] ?? 20),
            'fields' => $query['fields'] ?? null,
        ];
        if (isset($opts['sort']) && !QV::sort($opts['sort'])) {
            return [["error" => "Invalid sort parameter"], 400];
        }

        $headers = [];
        $result = null;
        if ($this->cache && $this->cache->shouldCache($table)) {
            $cacheKey = $this->cache->generateKey($table, $opts);
            $result = $this->cache->get($cacheKey);
            if ($result !== null) {
                $headers['X-Cache-Hit'] = 'true';
                $headers['X-Cache-TTL'] = (string)$this->cache->getTtl($table);
            }
        }
        if ($result === null) {
            $result = $this->api->list($table, $opts);
            if ($this->cache && $this->cache->shouldCache($table)) {
                $this->cache->set($cacheKey, $result, $table);
                $headers['X-Cache-Hit'] = 'false';
                $headers['X-Cache-Stored'] = 'true';
                $headers['X-Cache-TTL'] = (string)$this->cache->getTtl($table);
            }
        }
        return [$result, 200, $headers];
    }

    public function count(?string $role, ?string $table, array $query): array
    {
        if (!$table || !QV::table($table)) {
            return [["error" => "Invalid table name"], 400];
        }
        $this->rbacGuard->guard($this->authEnabled, $role, $table, 'list');
        $opts = [ 'filter' => $query['filter'] ?? null ];
        return [$this->api->count($table, $opts), 200];
    }

    public function read(?string $role, ?string $table, $id): array
    {
        if (!$table || !QV::table($table)) {
            return [["error" => "Invalid table name"], 400];
        }
        if (!QV::id($id)) {
            return [["error" => "Invalid id parameter"], 400];
        }
        $this->rbacGuard->guard($this->authEnabled, $role, $table, 'read');
        return [$this->api->read($table, $id), 200];
    }

    public function create(?string $role, ?string $table, array $data): array
    {
        if (!$table || !QV::table($table)) {
            return [["error" => "Invalid or missing table parameter"], 400];
        }
        $this->rbacGuard->guard($this->authEnabled, $role, $table, 'create');
        $result = $this->api->create($table, $data);
        if ($this->cache) { $this->cache->invalidateTable($table); }
        return [$result, 201];
    }

    public function update(?string $role, ?string $table, $id, array $data): array
    {
        if (!$table || !QV::table($table)) {
            return [["error" => "Invalid or missing table parameter"], 400];
        }
        if (!QV::id($id)) {
            return [["error" => "Invalid or missing id parameter"], 400];
        }
        $this->rbacGuard->guard($this->authEnabled, $role, $table, 'update');
        $result = $this->api->update($table, $id, $data);
        if ($this->cache) { $this->cache->invalidateTable($table); }
        return [$result, 200];
    }

    public function delete(?string $role, ?string $table, $id): array
    {
        if (!$table || !QV::table($table)) {
            return [["error" => "Invalid table name"], 400];
        }
        if (!QV::id($id)) {
            return [["error" => "Invalid id parameter"], 400];
        }
        $this->rbacGuard->guard($this->authEnabled, $role, $table, 'delete');
        $result = $this->api->delete($table, $id);
        if ($this->cache) { $this->cache->invalidateTable($table); }
        return [$result, 200];
    }

    public function bulkCreate(?string $role, ?string $table, array $rows): array
    {
        if (!$table || !QV::table($table)) {
            return [["error" => "Invalid or missing table parameter"], 400];
        }
        $this->rbacGuard->guard($this->authEnabled, $role, $table, 'create');
        if (!is_array($rows) || empty($rows)) {
            return [["error" => "Invalid or empty JSON array"], 400];
        }
        $result = $this->api->bulkCreate($table, $rows);
        if ($this->cache) { $this->cache->invalidateTable($table); }
        return [$result, 201];
    }

    public function bulkDelete(?string $role, ?string $table, array $ids): array
    {
        if (!$table || !QV::table($table)) {
            return [["error" => "Invalid or missing table parameter"], 400];
        }
        $this->rbacGuard->guard($this->authEnabled, $role, $table, 'delete');
        if (!isset($ids) || !is_array($ids) || empty($ids)) {
            return [["error" => 'Invalid or empty ids array. Send JSON with "ids" field.'], 400];
        }
        $result = $this->api->bulkDelete($table, $ids);
        if ($this->cache) { $this->cache->invalidateTable($table); }
        return [$result, 200];
    }
}
