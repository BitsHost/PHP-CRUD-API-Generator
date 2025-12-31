<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Security\RateLimiter;

/**
 * Rate Limiter Tests
 * 
 * Tests the rate limiting functionality including:
 * - Basic rate limiting
 * - Request counting
 * - Reset functionality
 * - Header generation
 * - Cleanup operations
 */
class RateLimiterTest extends TestCase
{
    private RateLimiter $limiter;
    private string $testStorageDir;

    protected function setUp(): void
    {
        // Use a test-specific storage directory
        $this->testStorageDir = sys_get_temp_dir() . '/test_rate_limits_' . uniqid();
        
        $this->limiter = new RateLimiter([
            'enabled' => true,
            'max_requests' => 5,
            'window_seconds' => 2,
            'storage_dir' => $this->testStorageDir
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test storage directory
        if (is_dir($this->testStorageDir)) {
            $files = glob($this->testStorageDir . '/*') ?: [];
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testStorageDir);
        }
    }

    public function testBasicRateLimiting(): void
    {
        $identifier = 'test_user_1';

        // First 5 requests should pass
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue(
                $this->limiter->checkLimit($identifier),
                "Request $i should be allowed"
            );
        }

        // 6th request should fail
        $this->assertFalse(
            $this->limiter->checkLimit($identifier),
            "Request 6 should be rate limited"
        );
    }

    public function testRequestCount(): void
    {
        $identifier = 'test_user_2';

        // Make 3 requests
        $this->limiter->checkLimit($identifier);
        $this->limiter->checkLimit($identifier);
        $this->limiter->checkLimit($identifier);

        // Should have 3 requests
        $this->assertEquals(3, $this->limiter->getRequestCount($identifier));
    }

    public function testRemainingRequests(): void
    {
        $identifier = 'test_user_3';

        // Initially should have 5 remaining (max_requests)
        $this->assertEquals(5, $this->limiter->getRemainingRequests($identifier));

        // After 2 requests, should have 3 remaining
        $this->limiter->checkLimit($identifier);
        $this->limiter->checkLimit($identifier);
        $this->assertEquals(3, $this->limiter->getRemainingRequests($identifier));
    }

    public function testRateLimitReset(): void
    {
        $identifier = 'test_user_4';

        // Fill up the rate limit
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->checkLimit($identifier);
        }

        // Should be rate limited
        $this->assertFalse($this->limiter->checkLimit($identifier));

        // Reset the rate limit
        $this->limiter->reset($identifier);

        // Should be allowed again
        $this->assertTrue($this->limiter->checkLimit($identifier));
    }

    public function testWindowExpiration(): void
    {
        $identifier = 'test_user_5';

        // Fill up the rate limit
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->checkLimit($identifier);
        }

        // Should be rate limited
        $this->assertFalse($this->limiter->checkLimit($identifier));

        // Wait for window to expire (2 seconds + buffer)
        sleep(3);

        // Should be allowed again after window expires
        $this->assertTrue($this->limiter->checkLimit($identifier));
    }

    public function testHeaders(): void
    {
        $identifier = 'test_user_6';

        // Make 2 requests
        $this->limiter->checkLimit($identifier);
        $this->limiter->checkLimit($identifier);

        $headers = $this->limiter->getHeaders($identifier);

        // Check header values
        $this->assertEquals('5', $headers['X-RateLimit-Limit']);
        $this->assertEquals('3', $headers['X-RateLimit-Remaining']);
        $this->assertEquals('2', $headers['X-RateLimit-Window']);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    public function testDisabledRateLimiting(): void
    {
        $limiter = new RateLimiter([
            'enabled' => false,
            'max_requests' => 2,
            'window_seconds' => 60
        ]);

        $identifier = 'test_user_7';

        // Should allow unlimited requests when disabled
        for ($i = 0; $i < 10; $i++) {
            $this->assertTrue($limiter->checkLimit($identifier));
        }

        // Request count should be 0 when disabled
        $this->assertEquals(0, $limiter->getRequestCount($identifier));
    }

    public function testCustomLimits(): void
    {
        $identifier = 'test_user_8';

        // Use custom limits (3 requests per window)
        $this->assertTrue($this->limiter->checkLimit($identifier, 3));
        $this->assertTrue($this->limiter->checkLimit($identifier, 3));
        $this->assertTrue($this->limiter->checkLimit($identifier, 3));
        
        // 4th request should fail
        $this->assertFalse($this->limiter->checkLimit($identifier, 3));
    }

    public function testMultipleIdentifiers(): void
    {
        $user1 = 'test_user_9';
        $user2 = 'test_user_10';

        // Fill user1's limit
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->checkLimit($user1);
        }

        // user1 should be limited
        $this->assertFalse($this->limiter->checkLimit($user1));

        // user2 should still be allowed
        $this->assertTrue($this->limiter->checkLimit($user2));
    }

    public function testResetTime(): void
    {
        $identifier = 'test_user_11';

        // Make a request
        $this->limiter->checkLimit($identifier);

        // Reset time should be approximately window_seconds (2 seconds)
        $resetTime = $this->limiter->getResetTime($identifier);
        $this->assertGreaterThan(0, $resetTime);
        $this->assertLessThanOrEqual(2, $resetTime);
    }

    public function testCleanup(): void
    {
        $identifier1 = 'test_user_12';
        $identifier2 = 'test_user_13';

        // Make requests for both identifiers
        $this->limiter->checkLimit($identifier1);
        $this->limiter->checkLimit($identifier2);

        // Initially should have 2 files
    $filesBefore = glob($this->testStorageDir . '/ratelimit_*.dat') ?: [];
        $this->assertCount(2, $filesBefore);

        // Wait a moment to ensure files have different timestamps
        sleep(1);

        // Cleanup files older than 0 seconds (all files older than 1 second)
        $deleted = $this->limiter->cleanup(0);
        $this->assertGreaterThanOrEqual(2, $deleted);

        // Should have no files after cleanup (or very few if timing is tight)
    $filesAfter = glob($this->testStorageDir . '/ratelimit_*.dat') ?: [];
        $this->assertLessThanOrEqual(0, count($filesAfter));
    }
}
