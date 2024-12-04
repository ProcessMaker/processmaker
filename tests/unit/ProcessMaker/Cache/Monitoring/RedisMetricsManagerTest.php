<?php

namespace Tests\Unit\ProcessMaker\Cache\Monitoring;

use Illuminate\Support\Facades\Redis;
use ProcessMaker\Cache\Monitoring\RedisMetricsManager;
use Tests\TestCase;

class RedisMetricsManagerTest extends TestCase
{
    protected RedisMetricsManager $metrics;

    protected string $testKey = 'test_key';

    protected string $metricsPrefix = 'cache:metrics:';

    protected function setUp(): void
    {
        parent::setUp();
        $this->metrics = new RedisMetricsManager();

        // Clear any existing metrics before each test
        $this->metrics->resetMetrics();
    }

    public function testRecordHit()
    {
        $time = 0.1;
        $this->metrics->recordHit($this->testKey, $time);

        $baseKey = $this->metricsPrefix . $this->testKey;
        $hits = Redis::hget($baseKey, 'hits');
        $times = Redis::lrange($baseKey . ':hit_times', 0, -1);

        $this->assertEquals(1, $hits);
        $this->assertCount(1, $times);
        $this->assertEquals($time, (float) $times[0]);
    }

    public function testRecordHitLimitsTimesList()
    {
        // Record more than 100 hits
        for ($i = 0; $i < 110; $i++) {
            $this->metrics->recordHit($this->testKey, 0.1);
        }

        $baseKey = $this->metricsPrefix . $this->testKey;
        $times = Redis::lrange($baseKey . ':hit_times', 0, -1);

        // Should only keep last 100 times
        $this->assertCount(100, $times);
    }

    public function testRecordMiss()
    {
        $time = 0.2;
        $this->metrics->recordMiss($this->testKey, $time);

        $baseKey = $this->metricsPrefix . $this->testKey;
        $misses = Redis::hget($baseKey, 'misses');
        $times = Redis::lrange($baseKey . ':miss_times', 0, -1);

        $this->assertEquals(1, $misses);
        $this->assertCount(1, $times);
        $this->assertEquals($time, (float) $times[0]);
    }

    public function testRecordMissLimitsTimesList()
    {
        // Record more than 100 misses
        for ($i = 0; $i < 110; $i++) {
            $this->metrics->recordMiss($this->testKey, 0.2);
        }

        $baseKey = $this->metricsPrefix . $this->testKey;
        $times = Redis::lrange($baseKey . ':miss_times', 0, -1);

        // Should only keep last 100 times
        $this->assertCount(100, $times);
    }

    public function testRecordWrite()
    {
        $size = 1024;
        $this->metrics->recordWrite($this->testKey, $size);

        $baseKey = $this->metricsPrefix . $this->testKey;
        $storedSize = Redis::hget($baseKey, 'memory');
        $lastWrite = Redis::hget($baseKey, 'last_write');

        $this->assertEquals($size, $storedSize);
        $this->assertNotNull($lastWrite);
        $this->assertIsNumeric($lastWrite);
        $this->assertLessThanOrEqual(microtime(true), (float) $lastWrite);
    }

    public function testGetHitRate()
    {
        // Record 2 hits and 1 miss
        $this->metrics->recordHit($this->testKey, 0.1);
        $this->metrics->recordHit($this->testKey, 0.1);
        $this->metrics->recordMiss($this->testKey, 0.2);

        $hitRate = $this->metrics->getHitRate($this->testKey);
        $this->assertEqualsWithDelta(2 / 3, $hitRate, 0.0001);
    }

    public function testGetHitRateWithNoAccesses()
    {
        $hitRate = $this->metrics->getHitRate($this->testKey);
        $this->assertEquals(0, $hitRate);
    }

    public function testGetMissRate()
    {
        // Record 2 hits and 1 miss
        $this->metrics->recordHit($this->testKey, 0.1);
        $this->metrics->recordHit($this->testKey, 0.1);
        $this->metrics->recordMiss($this->testKey, 0.2);

        $missRate = $this->metrics->getMissRate($this->testKey);
        $this->assertEqualsWithDelta(1 / 3, $missRate, 0.0001);
    }

    public function testGetMissRateWithNoAccesses()
    {
        $missRate = $this->metrics->getMissRate($this->testKey);
        $this->assertEquals(0, $missRate);
    }

    public function testGetHitAvgTime()
    {
        $this->metrics->recordHit($this->testKey, 0.1);
        $this->metrics->recordHit($this->testKey, 0.3);

        $avgTime = $this->metrics->getHitAvgTime($this->testKey);
        $this->assertEqualsWithDelta(0.2, $avgTime, 0.0001);
    }

    public function testGetHitAvgTimeWithNoHits()
    {
        $avgTime = $this->metrics->getHitAvgTime($this->testKey);
        $this->assertEquals(0, $avgTime);
    }

    public function testGetMissAvgTime()
    {
        $this->metrics->recordMiss($this->testKey, 0.2);
        $this->metrics->recordMiss($this->testKey, 0.4);

        $avgTime = $this->metrics->getMissAvgTime($this->testKey);
        $this->assertEqualsWithDelta(0.3, $avgTime, 0.0001);
    }

    public function testGetMissAvgTimeWithNoMisses()
    {
        $avgTime = $this->metrics->getMissAvgTime($this->testKey);
        $this->assertEquals(0, $avgTime);
    }

    public function testGetTopKeys()
    {
        // Setup test data
        $this->metrics->recordHit('key1', 0.1);
        $this->metrics->recordHit('key1', 0.1);
        $this->metrics->recordMiss('key1', 0.2);
        $this->metrics->recordWrite('key1', 1000);

        $this->metrics->recordHit('key2', 0.1);
        $this->metrics->recordWrite('key2', 500);

        $topKeys = $this->metrics->getTopKeys(2);

        $this->assertCount(2, $topKeys);
        $this->assertEquals('key1', $topKeys['key1']['key']);
        $this->assertEquals(3, $topKeys['key1']['total_accesses']);
        $this->assertEquals(1000, $topKeys['key1']['memory_usage']);
        $this->assertEqualsWithDelta(2 / 3, $topKeys['key1']['hit_ratio'], 0.0001);
        $this->assertEqualsWithDelta(1 / 3, $topKeys['key1']['miss_ratio'], 0.0001);
    }

    public function testGetTopKeysWithNoKeys()
    {
        $topKeys = $this->metrics->getTopKeys(5);
        $this->assertEmpty($topKeys);
    }

    public function testGetMemoryUsage()
    {
        $size = 2048;
        $this->metrics->recordWrite($this->testKey, $size);

        $usage = $this->metrics->getMemoryUsage($this->testKey);

        $this->assertEquals($size, $usage['current_size']);
        $this->assertNotNull($usage['last_write']);
        $this->assertIsFloat($usage['last_write']);
    }

    public function testGetMemoryUsageForNonexistentKey()
    {
        $usage = $this->metrics->getMemoryUsage('nonexistent_key');

        $this->assertEquals(0, $usage['current_size']);
        $this->assertNull($usage['last_write']);
    }

    public function testResetMetrics()
    {
        // Add some test data
        $this->metrics->recordHit($this->testKey, 0.1);
        $this->metrics->recordMiss($this->testKey, 0.2);
        $this->metrics->recordWrite($this->testKey, 1024);

        // Reset metrics
        $this->metrics->resetMetrics();

        // Verify all metrics are cleared
        $keys = Redis::keys($this->metricsPrefix . '*');
        $this->assertEmpty($keys);
    }

    public function testGetSummary()
    {
        // Setup test data
        $this->metrics->recordHit('key1', 0.1);
        $this->metrics->recordHit('key1', 0.3);
        $this->metrics->recordMiss('key1', 0.2);
        $this->metrics->recordWrite('key1', 1000);

        $this->metrics->recordHit('key2', 0.2);
        $this->metrics->recordWrite('key2', 500);

        $summary = $this->metrics->getSummary();

        $this->assertArrayHasKey('keys', $summary);
        $this->assertArrayHasKey('overall_hit_ratio', $summary);
        $this->assertArrayHasKey('overall_miss_ratio', $summary);
        $this->assertArrayHasKey('avg_hit_time', $summary);
        $this->assertArrayHasKey('avg_miss_time', $summary);
        $this->assertArrayHasKey('total_memory_usage', $summary);
        $this->assertEquals(2, $summary['total_keys']);
        $this->assertEquals(1500, $summary['total_memory_usage']);
        $this->assertEqualsWithDelta(0.75, $summary['overall_hit_ratio'], 0.0001);
        $this->assertEqualsWithDelta(0.25, $summary['overall_miss_ratio'], 0.0001);
    }

    public function testGetSummaryWithNoData()
    {
        $summary = $this->metrics->getSummary();

        $this->assertEmpty($summary['keys']);
        $this->assertEquals(0, $summary['total_keys']);
        $this->assertEquals(0, $summary['total_memory_usage']);
        $this->assertEquals(0, $summary['overall_hit_ratio']);
        $this->assertEquals(0, $summary['overall_miss_ratio']);
        $this->assertEquals(0, $summary['avg_hit_time']);
        $this->assertEquals(0, $summary['avg_miss_time']);
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        $this->metrics->resetMetrics();
        parent::tearDown();
    }
}
