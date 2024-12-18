<?php

namespace Tests\Unit\ProcessMaker\Cache\Monitoring;

use Mockery;
use ProcessMaker\Cache\CacheInterface;
use ProcessMaker\Cache\Monitoring\CacheMetricsDecorator;
use ProcessMaker\Cache\Monitoring\CacheMetricsInterface;
use ProcessMaker\Cache\Screens\ScreenCacheInterface;
use Tests\TestCase;

class CacheMetricsDecoratorTest extends TestCase
{
    protected $cache;

    protected $metrics;

    protected $decorator;

    protected $testKey = 'test_key';

    protected $testValue = 'test_value';

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks that implement both interfaces
        $this->cache = Mockery::mock(CacheInterface::class . ', ' . ScreenCacheInterface::class);
        $this->metrics = Mockery::mock(CacheMetricsInterface::class);

        // Create decorator with mocks
        $this->decorator = new CacheMetricsDecorator($this->cache, $this->metrics);
    }

    public function testGetWithHit()
    {
        // Setup expectations for cache hit
        $this->cache->shouldReceive('has')
            ->once()
            ->with($this->testKey)
            ->andReturn(true);

        $this->cache->shouldReceive('get')
            ->once()
            ->with($this->testKey, null)
            ->andReturn($this->testValue);

        $this->metrics->shouldReceive('recordHit')
            ->once()
            ->withArgs(function ($key, $time) {
                return $key === $this->testKey && is_float($time);
            });

        // Execute and verify
        $result = $this->decorator->get($this->testKey);
        $this->assertEquals($this->testValue, $result);
    }

    public function testGetWithMiss()
    {
        $default = 'default_value';

        // Setup expectations for cache miss
        $this->cache->shouldReceive('has')
            ->once()
            ->with($this->testKey)
            ->andReturn(false);

        $this->cache->shouldReceive('get')
            ->once()
            ->with($this->testKey, $default)
            ->andReturn($default);

        $this->metrics->shouldReceive('recordMiss')
            ->once()
            ->withArgs(function ($key, $time) {
                return $key === $this->testKey && is_float($time);
            });

        // Execute and verify
        $result = $this->decorator->get($this->testKey, $default);
        $this->assertEquals($default, $result);
    }

    public function testSetSuccess()
    {
        $ttl = 3600;

        // Setup expectations
        $this->cache->shouldReceive('set')
            ->once()
            ->with($this->testKey, $this->testValue, $ttl)
            ->andReturn(true);

        $this->metrics->shouldReceive('recordWrite')
            ->once()
            ->withArgs(function ($key, $size) {
                return $key === $this->testKey && is_int($size) && $size > 0;
            });

        // Execute and verify
        $result = $this->decorator->set($this->testKey, $this->testValue, $ttl);
        $this->assertTrue($result);
    }

    public function testSetFailure()
    {
        // Setup expectations
        $this->cache->shouldReceive('set')
            ->once()
            ->with($this->testKey, $this->testValue, null)
            ->andReturn(false);

        $this->metrics->shouldNotReceive('recordWrite');

        // Execute and verify
        $result = $this->decorator->set($this->testKey, $this->testValue);
        $this->assertFalse($result);
    }

    public function testDelete()
    {
        // Setup expectations
        $this->cache->shouldReceive('delete')
            ->once()
            ->with($this->testKey)
            ->andReturn(true);

        // Execute and verify
        $result = $this->decorator->delete($this->testKey);
        $this->assertTrue($result);
    }

    public function testClear()
    {
        // Setup expectations
        $this->cache->shouldReceive('clear')
            ->once()
            ->andReturn(true);

        // Execute and verify
        $result = $this->decorator->clear();
        $this->assertTrue($result);
    }

    public function testHas()
    {
        // Setup expectations
        $this->cache->shouldReceive('has')
            ->once()
            ->with($this->testKey)
            ->andReturn(true);

        // Execute and verify
        $result = $this->decorator->has($this->testKey);
        $this->assertTrue($result);
    }

    public function testMissing()
    {
        // Setup expectations
        $this->cache->shouldReceive('missing')
            ->once()
            ->with($this->testKey)
            ->andReturn(true);

        // Execute and verify
        $result = $this->decorator->missing($this->testKey);
        $this->assertTrue($result);
    }

    public function testCalculateSizeWithString()
    {
        $value = 'test';
        $result = $this->invokeCalculateSize($value);
        $this->assertEquals(strlen($value), $result);
    }

    public function testCalculateSizeWithArray()
    {
        $value = ['test' => 'value'];
        $result = $this->invokeCalculateSize($value);
        $this->assertEquals(strlen(serialize($value)), $result);
    }

    public function testCalculateSizeWithObject()
    {
        $value = new \stdClass();
        $value->test = 'value';
        $result = $this->invokeCalculateSize($value);
        $this->assertEquals(strlen(serialize($value)), $result);
    }

    public function testCalculateSizeWithInteger()
    {
        $value = 42;
        $result = $this->invokeCalculateSize($value);
        $this->assertEquals(PHP_INT_SIZE, $result);
    }

    public function testCalculateSizeWithFloat()
    {
        $value = 3.14;
        $result = $this->invokeCalculateSize($value);
        $this->assertEquals(8, $result);
    }

    public function testCalculateSizeWithBoolean()
    {
        $value = true;
        $result = $this->invokeCalculateSize($value);
        $this->assertEquals(1, $result);
    }

    public function testCalculateSizeWithNull()
    {
        $value = null;
        $result = $this->invokeCalculateSize($value);
        $this->assertEquals(0, $result);
    }

    protected function invokeCalculateSize($value)
    {
        $method = new \ReflectionMethod(CacheMetricsDecorator::class, 'calculateSize');
        $method->setAccessible(true);

        return $method->invoke($this->decorator, $value);
    }

    public function testCreateKey()
    {
        // Setup expectations
        $this->cache->shouldReceive('createKey')
            ->once()
            ->with([
                'process_id' => 1,
                'process_version_id' => 2,
                'language' => 'en',
                'screen_id' => 3,
                'screen_version_id' => 4,
            ])
            ->andReturn('screen_1_2_en_3_4');

        // Execute and verify
        $key = $this->decorator->createKey([
            'process_id' => 1,
            'process_version_id' => 2,
            'language' => 'en',
            'screen_id' => 3,
            'screen_version_id' => 4,
        ]);
        $this->assertEquals('screen_1_2_en_3_4', $key);
    }

    public function testCreateKeyWithNonScreenCache()
    {
        // Create a mock that only implements CacheInterface
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('createKey')
            ->once()
            ->andThrow(new \BadMethodCallException('Method Mockery_0_ProcessMaker_Cache_CacheInterface::createKey() does not exist on this mock object'));

        $metrics = Mockery::mock(CacheMetricsInterface::class);
        $decorator = new CacheMetricsDecorator($cache, $metrics);

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method Mockery_0_ProcessMaker_Cache_CacheInterface::createKey() does not exist on this mock object');

        $decorator->createKey([
            'process_id' => 1,
            'process_version_id' => 2,
            'language' => 'en',
            'screen_id' => 3,
            'screen_version_id' => 4,
        ]);
    }

    public function testInvalidateSuccess()
    {
        // Test parameters
        $params = ['screen_id' => 5, 'language' => 'es'];

        // Setup expectations for invalidate
        $this->cache->shouldReceive('invalidate')
            ->once()
            ->with($params)
            ->andReturn(true);

        // Execute and verify
        $result = $this->decorator->invalidate($params);
        $this->assertNull($result);
    }

    public function testInvalidateFailure()
    {
        // Test parameters
        $params = ['screen_id' => 5, 'language' => 'es'];

        // Setup expectations for invalidate to fail
        $this->cache->shouldReceive('invalidate')
            ->once()
            ->with($params)
            ->andReturnNull();

        // Execute and verify
        $result = $this->decorator->invalidate($params);
        $this->assertNull($result);
    }

    public function testInvalidateWithNonScreenCache()
    {
        // Create a mock that only implements CacheInterface
        $cache = Mockery::mock(CacheInterface::class);
        $metrics = Mockery::mock(CacheMetricsInterface::class);
        $decorator = new CacheMetricsDecorator($cache, $metrics);

        $this->expectException(Mockery\Exception\BadMethodCallException::class);
        $this->expectExceptionMessage('Method Mockery_2_ProcessMaker_Cache_CacheInterface::invalidate() does not exist on this mock object');

        // Execute with test parameters
        $decorator->invalidate(['screen_id' => 5, 'language' => 'es']);
        $this->assertNull($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
