<?php
/**
 * API Controller providing CRUD endpoints and schema discovery.
 *
 * @package   PHP-CRUD-API-Generator
 * @author    BitsHost
 * @copyright 2025 BitsHost
 * @license   MIT License
 * @link      https://bitshost.biz/
 * @created   2025-11-12
 */
namespace App\Http\Controllers;

use App\Database\SchemaInspector;
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
    /**
     * @return array{0:array<int,string>,1:int}
     */
    public function tables(): array { return [$this->inspector->getTables(), 200]; }

    /**
     * @return array{0:array<int,array<string,mixed>>|array{error:string},1:int}
     */
    public function columns(?string $role, ?string $table): array
    {
        if (!$table || !QV::table($table)) {
            return [["error" => "Invalid table name"], 400];
        }
        $this->rbacGuard->guard($this->authEnabled, $role, $table, 'read');
        return [$this->inspector->getColumns($table), 200];
    }

    // ==================== Data endpoints ====================
    /**
     * @param array<string,mixed> $query
     * @return array{0:array<string,mixed>,1:int,2?:array<string,string>}
     */
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
        $allowCache = ($this->cache !== null) && $this->cache->shouldCache($table);
        $cacheKey = $allowCache ? $this->cache->generateKey($table, $opts) : null;
        if ($allowCache && $cacheKey) {
            $result = $this->cache->get($cacheKey);
            if ($result !== null) {
                $headers['X-Cache-Hit'] = 'true';
                $headers['X-Cache-TTL'] = (string)$this->cache->getTtl($table);
            }
        }
        if ($result === null) {
            $result = $this->api->list($table, $opts);
            if ($allowCache && $cacheKey) {
                $this->cache->set($cacheKey, $result, $table);
                $headers['X-Cache-Hit'] = 'false';
                $headers['X-Cache-Stored'] = 'true';
                $headers['X-Cache-TTL'] = (string)$this->cache->getTtl($table);
            }
        }
        return [$result, 200, $headers];
    }

    /**
     * @param array<string,mixed> $query
     * @return array{0:array<string,mixed>,1:int}
     */
    public function count(?string $role, ?string $table, array $query): array
    {
        if (!$table || !QV::table($table)) {
            return [["error" => "Invalid table name"], 400];
        }
        $this->rbacGuard->guard($this->authEnabled, $role, $table, 'list');
        $opts = [ 'filter' => $query['filter'] ?? null ];
        return [$this->api->count($table, $opts), 200];
    }

    /**
     * @param int|string $id
     * @return array{0:array<string,mixed>|array{error:string},1:int}
     */
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

    /**
     * @param array<string,mixed> $data
     * @return array{0:array<string,mixed>|array{error:string},1:int}
     */
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

    /**
     * @param int|string $id
     * @param array<string,mixed> $data
     * @return array{0:array<string,mixed>|array{error:string},1:int}
     */
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

    /**
     * @param int|string $id
     * @return array{0:mixed,1:int}
     */
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

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array{0:mixed,1:int}
     */
    public function bulkCreate(?string $role, ?string $table, array $rows): array
    {
        
        if (!$table || !QV::table($table)) {
            return [["error" => "Invalid or missing table parameter"], 400];
        }
        $this->rbacGuard->guard($this->authEnabled, $role, $table, 'create');
        if (empty($rows)) {
            return [["error" => "Invalid or empty JSON array"], 400];
        }
        $result = $this->api->bulkCreate($table, $rows);
        if ($this->cache) { $this->cache->invalidateTable($table); }
        return [$result, 201];
    }

    /**
     * @param array<int,int|string> $ids
     * @return array{0:mixed,1:int}
     */
    public function bulkDelete(?string $role, ?string $table, array $ids): array
    {
        
        if (!$table || !QV::table($table)) {
            return [["error" => "Invalid or missing table parameter"], 400];
        }
        $this->rbacGuard->guard($this->authEnabled, $role, $table, 'delete');
        if (empty($ids)) {
            return [["error" => 'Invalid or empty ids array. Send JSON with "ids" field.'], 400];
        }
        $result = $this->api->bulkDelete($table, $ids);
        if ($this->cache) { $this->cache->invalidateTable($table); }
        return [$result, 200];
    }
}
