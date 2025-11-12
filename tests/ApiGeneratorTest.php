<?php
use PHPUnit\Framework\TestCase;
use App\Database\Database as Database;
use App\ApiGenerator;
use App\Database\SchemaInspector as SchemaInspector;

class ApiGeneratorTest extends TestCase
{
    private Database $db;
    private ApiGenerator $api;
    private string $table = 'test_table';

    public static function setUpBeforeClass(): void
    {
        $dbConfig = require __DIR__ . '/../config/db.php';
    $pdo = (new App\Database\Database($dbConfig))->getPdo();
        $pdo->exec("DROP TABLE IF EXISTS test_table");
        $pdo->exec("CREATE TABLE test_table (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))");
    }

    public static function tearDownAfterClass(): void
    {
        $dbConfig = require __DIR__ . '/../config/db.php';
    $pdo = (new App\Database\Database($dbConfig))->getPdo();
        $pdo->exec("DROP TABLE IF EXISTS test_table");
    }

    protected function setUp(): void
    {
        $dbConfig = require __DIR__ . '/../config/db.php';
    $this->db = new App\Database\Database($dbConfig);
        $this->api = new App\ApiGenerator($this->db->getPdo());
    }

    public function testCreateAndRead(): void
    {
        $row = $this->api->create($this->table, ['name' => 'Alice']);
        $this->assertEquals('Alice', $row['name']);
        $read = $this->api->read($this->table, $row['id']);
        $this->assertEquals('Alice', $read['name']);
    }

    public function testUpdate(): void
    {
        $row = $this->api->create($this->table, ['name' => 'Bob']);
        $updated = $this->api->update($this->table, $row['id'], ['name' => 'Bobby']);
        $this->assertEquals('Bobby', $updated['name']);
    }

    public function testDelete(): void
    {
        $row = $this->api->create($this->table, ['name' => 'Charlie']);
        $deleted = $this->api->delete($this->table, $row['id']);
        $this->assertTrue($deleted);
    }

    public function testList(): void
    {
        $this->api->create($this->table, ['name' => 'Daisy']);
        $list = $this->api->list($this->table);
        $this->assertArrayHasKey('data', $list);
    }
}