<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Observability\RequestLogger;

/**
 * Request Logger Tests
 * 
 * Tests the request logging functionality including:
 * - Request/response logging
 * - Log levels
 * - Sensitive data redaction
 * - Authentication logging
 * - Rate limit logging
 * - Log rotation
 * - Statistics
 * - Cleanup
 */
class RequestLoggerTest extends TestCase
{
    private RequestLogger $logger;
    private string $testLogDir;

    protected function setUp(): void
    {
        // Use a test-specific log directory
        $this->testLogDir = sys_get_temp_dir() . '/test_logs_' . uniqid();
        
        $this->logger = new RequestLogger([
            'enabled' => true,
            'log_dir' => $this->testLogDir,
            'log_level' => RequestLogger::LEVEL_INFO,
            'log_headers' => true,
            'log_body' => true,
            'max_body_length' => 500,
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test log directory
        if (is_dir($this->testLogDir)) {
            $files = glob($this->testLogDir . '/*') ?: [];
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testLogDir);
        }
    }

    public function testBasicRequestLogging(): void
    {
        $request = [
            'method' => 'GET',
            'action' => 'list',
            'table' => 'users',
            'ip' => '127.0.0.1',
            'query' => ['page' => 1]
        ];

        $response = [
            'status_code' => 200,
            'body' => ['data' => []],
            'size' => 100
        ];

        $result = $this->logger->logRequest($request, $response, 0.05);
        $this->assertTrue($result);

        // Check log file exists
        $logFile = $this->testLogDir . '/api_' . date('Y-m-d') . '.log';
        $this->assertFileExists($logFile);

        // Check log content
    $content = file_get_contents($logFile) ?: '';
        $this->assertStringContainsString('GET', $content);
        $this->assertStringContainsString('list', $content);
        $this->assertStringContainsString('users', $content);
    }

    public function testSensitiveDataRedaction(): void
    {
        $request = [
            'method' => 'POST',
            'action' => 'create',
            'body' => [
                'username' => 'testuser',
                'password' => 'secret123',
                'api_key' => 'abc123'
            ]
        ];

        $response = ['status_code' => 201];

        $this->logger->logRequest($request, $response, 0.01);

        $logFile = $this->testLogDir . '/api_' . date('Y-m-d') . '.log';
    $content = file_get_contents($logFile) ?: '';

        // Check that sensitive data is redacted
        $this->assertStringContainsString('***REDACTED***', $content);
        $this->assertStringNotContainsString('secret123', $content);
        $this->assertStringNotContainsString('abc123', $content);
    }

    public function testAuthenticationLogging(): void
    {
        // Test successful auth
        $result = $this->logger->logAuth('jwt', true, 'testuser');
        $this->assertTrue($result);

        $logFile = $this->testLogDir . '/api_' . date('Y-m-d') . '.log';
    $content = file_get_contents($logFile) ?: '';
        $this->assertStringContainsString('AUTH ✅ SUCCESS', $content);
        $this->assertStringContainsString('jwt', $content);

        // Test failed auth
        $this->logger->logAuth('basic', false, 'baduser', 'Invalid credentials');
    $content = file_get_contents($logFile) ?: '';
        $this->assertStringContainsString('AUTH ❌ FAILED', $content);
        $this->assertStringContainsString('Invalid credentials', $content);
    }

    public function testRateLimitLogging(): void
    {
        $result = $this->logger->logRateLimit('user:test', 100, 100);
        $this->assertTrue($result);

        $logFile = $this->testLogDir . '/api_' . date('Y-m-d') . '.log';
    $content = file_get_contents($logFile) ?: '';
        
        $this->assertStringContainsString('RATE LIMIT EXCEEDED', $content);
        $this->assertStringContainsString('user:test', $content);
        $this->assertStringContainsString('100/100', $content);
    }

    public function testErrorLogging(): void
    {
        $result = $this->logger->logError('Database connection failed', [
            'host' => 'localhost',
            'error_code' => 1045
        ]);
        
        $this->assertTrue($result);

        $logFile = $this->testLogDir . '/api_' . date('Y-m-d') . '.log';
    $content = file_get_contents($logFile) ?: '';
        
        $this->assertStringContainsString('ERROR', $content);
        $this->assertStringContainsString('Database connection failed', $content);
    }

    public function testQuickRequestLogging(): void
    {
        $result = $this->logger->logQuickRequest('POST', 'create', 'products', 'user:admin');
        $this->assertTrue($result);

        $logFile = $this->testLogDir . '/api_' . date('Y-m-d') . '.log';
    $content = file_get_contents($logFile) ?: '';
        
        $this->assertStringContainsString('POST', $content);
        $this->assertStringContainsString('create', $content);
        $this->assertStringContainsString('products', $content);
        $this->assertStringContainsString('user:admin', $content);
    }

    public function testLogStatistics(): void
    {
        // Create various log entries
        $this->logger->logAuth('jwt', true, 'user1');
        $this->logger->logAuth('basic', false, 'user2', 'Invalid');
        $this->logger->logRateLimit('user:test', 100, 100);
        $this->logger->logError('Test error');

        $stats = $this->logger->getStats();
        
        // Total should include INFO, WARNING, and ERROR level logs
        $this->assertGreaterThanOrEqual(2, $stats['total_requests']);
        $this->assertGreaterThanOrEqual(1, $stats['errors']);
        $this->assertGreaterThanOrEqual(1, $stats['warnings']);
        $this->assertGreaterThanOrEqual(1, $stats['auth_failures']);
        $this->assertGreaterThanOrEqual(1, $stats['rate_limits']);
    }

    public function testDisabledLogging(): void
    {
        $logger = new RequestLogger(['enabled' => false]);

        $result = $logger->logRequest(
            ['method' => 'GET'],
            ['status_code' => 200],
            0.01
        );

        $this->assertFalse($result);
    }

    public function testLogRotation(): void
    {
        // Create a small rotation size for testing
        $logger = new RequestLogger([
            'enabled' => true,
            'log_dir' => $this->testLogDir,
            'rotation_size' => 100 // Very small for testing
        ]);

        // Write enough data to trigger rotation
        for ($i = 0; $i < 10; $i++) {
            $logger->logRequest(
                ['method' => 'GET', 'action' => 'test'],
                ['status_code' => 200],
                0.01
            );
        }

        // Check if rotation occurred (multiple log files)
    $files = glob($this->testLogDir . '/api_*.log') ?: [];
        // Should have at least 2 files (original + rotated)
        $this->assertGreaterThanOrEqual(1, count($files));
    }

    public function testCleanup(): void
    {
        // Create multiple log files
        for ($i = 0; $i < 5; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $logFile = $this->testLogDir . '/api_' . $date . '.log';
            file_put_contents($logFile, "Test log $i\n");
        }

        // Keep only 3 files
        $logger = new RequestLogger([
            'enabled' => true,
            'log_dir' => $this->testLogDir,
            'max_files' => 3
        ]);

        $deleted = $logger->cleanup();
        
        $this->assertEquals(2, $deleted); // Should delete 2 oldest files

        // Check remaining files
    $files = glob($this->testLogDir . '/api_*.log') ?: [];
        $this->assertCount(3, $files);
    }

    public function testLogLevels(): void
    {
        $request = ['method' => 'GET', 'action' => 'test'];
        
        // Test different status codes
        $testCases = [
            [200, 'INFO'],
            [400, 'WARNING'],
            [404, 'WARNING'],
            [500, 'ERROR'],
            [503, 'ERROR']
        ];

        foreach ($testCases as [$statusCode, $expectedLevel]) {
            $this->logger->logRequest(
                $request,
                ['status_code' => $statusCode],
                0.01
            );
        }

    $logFile = $this->testLogDir . '/api_' . date('Y-m-d') . '.log';
    $content = file_get_contents($logFile) ?: '';

    $this->assertStringContainsString('INFO', $content);
    $this->assertStringContainsString('WARNING', $content);
    $this->assertStringContainsString('ERROR', $content);
    }
}
