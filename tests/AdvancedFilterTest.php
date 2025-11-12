<?php
use PHPUnit\Framework\TestCase;
use App\Database\Database as Database;
use App\ApiGenerator;

class AdvancedFilterTest extends TestCase
{
    private Database $db;
    private ApiGenerator $api;
    private string $table = 'filter_test_table';

    public static function setUpBeforeClass(): void
    {
        $dbConfig = require __DIR__ . '/../config/db.php';
    $pdo = (new App\Database\Database($dbConfig))->getPdo();
        $pdo->exec("DROP TABLE IF EXISTS filter_test_table");
        $pdo->exec("CREATE TABLE filter_test_table (
            id INT AUTO_INCREMENT PRIMARY KEY, 
            name VARCHAR(255),
            age INT,
            email VARCHAR(255),
            status VARCHAR(50)
        )");
        
        // Insert test data
        $pdo->exec("INSERT INTO filter_test_table (name, age, email, status) VALUES 
            ('Alice', 25, 'alice@example.com', 'active'),
            ('Bob', 30, 'bob@gmail.com', 'active'),
            ('Charlie', 20, 'charlie@gmail.com', 'inactive'),
            ('David', 35, 'david@example.com', 'pending'),
            ('Eve', 28, 'eve@gmail.com', 'active')
        ");
    }

    public static function tearDownAfterClass(): void
    {
        $dbConfig = require __DIR__ . '/../config/db.php';
    $pdo = (new App\Database\Database($dbConfig))->getPdo();
        $pdo->exec("DROP TABLE IF EXISTS filter_test_table");
    }

    protected function setUp(): void
    {
        $dbConfig = require __DIR__ . '/../config/db.php';
    $this->db = new App\Database\Database($dbConfig);
        $this->api = new App\ApiGenerator($this->db->getPdo());
    }

    public function testFieldSelection(): void
    {
        $result = $this->api->list($this->table, ['fields' => 'id,name']);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        if (!empty($result['data'])) {
            $firstRow = $result['data'][0];
            $this->assertArrayHasKey('id', $firstRow);
            $this->assertArrayHasKey('name', $firstRow);
            // Should not have other fields
            $this->assertCount(2, $firstRow);
        }
    }

    public function testFilterEquals(): void
    {
        $result = $this->api->list($this->table, ['filter' => 'name:eq:Alice']);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result['data']));
        $this->assertEquals('Alice', $result['data'][0]['name']);
    }

    public function testFilterGreaterThan(): void
    {
        $result = $this->api->list($this->table, ['filter' => 'age:gt:28']);
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result['data'])); // Bob (30) and David (35)
        foreach ($result['data'] as $row) {
            $this->assertGreaterThan(28, $row['age']);
        }
    }

    public function testFilterLessThan(): void
    {
        $result = $this->api->list($this->table, ['filter' => 'age:lt:25']);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result['data'])); // Charlie (20)
        $this->assertEquals('Charlie', $result['data'][0]['name']);
    }

    public function testFilterLike(): void
    {
        $result = $this->api->list($this->table, ['filter' => 'email:like:%@gmail.com']);
        $this->assertIsArray($result);
        $this->assertEquals(3, count($result['data'])); // Bob, Charlie, Eve
        foreach ($result['data'] as $row) {
            $this->assertStringContainsString('@gmail.com', $row['email']);
        }
    }

    public function testFilterIn(): void
    {
        $result = $this->api->list($this->table, ['filter' => 'name:in:Alice|Bob|Charlie']);
        $this->assertIsArray($result);
        $this->assertEquals(3, count($result['data']));
        $names = array_column($result['data'], 'name');
        $this->assertContains('Alice', $names);
        $this->assertContains('Bob', $names);
        $this->assertContains('Charlie', $names);
    }

    public function testFilterNotIn(): void
    {
        $result = $this->api->list($this->table, ['filter' => 'status:notin:inactive|pending']);
        $this->assertIsArray($result);
        $this->assertEquals(3, count($result['data'])); // Alice, Bob, Eve (all active)
        foreach ($result['data'] as $row) {
            $this->assertEquals('active', $row['status']);
        }
    }

    public function testMultipleFilters(): void
    {
        $result = $this->api->list($this->table, [
            'filter' => 'age:gte:25,status:eq:active'
        ]);
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result['data'])); // Alice (25), Bob (30), Eve (28)
        foreach ($result['data'] as $row) {
            $this->assertGreaterThanOrEqual(25, $row['age']);
            $this->assertEquals('active', $row['status']);
        }
    }

    public function testCombinedFieldsAndFilters(): void
    {
        $result = $this->api->list($this->table, [
            'fields' => 'name,age',
            'filter' => 'age:gt:20',
            'sort' => 'age'
        ]);
        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result['data']));
        foreach ($result['data'] as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('age', $row);
            $this->assertGreaterThan(20, $row['age']);
        }
    }

    public function testBackwardCompatibility(): void
    {
        // Old format: col:value should still work
        $result = $this->api->list($this->table, ['filter' => 'name:Alice']);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result['data']));
        $this->assertEquals('Alice', $result['data'][0]['name']);
    }

    public function testCount(): void
    {
        $result = $this->api->count($this->table);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertEquals(5, $result['count']); // We inserted 5 records
    }

    public function testCountWithFilter(): void
    {
        $result = $this->api->count($this->table, ['filter' => 'status:eq:active']);
        $this->assertIsArray($result);
        $this->assertEquals(3, $result['count']); // Alice, Bob, Eve are active
    }

    public function testCountWithMultipleFilters(): void
    {
        $result = $this->api->count($this->table, ['filter' => 'age:gte:25,status:eq:active']);
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, $result['count']); // At least Alice (25) and Bob (30)
    }
}
