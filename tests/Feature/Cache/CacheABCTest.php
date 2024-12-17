<?php

namespace Tests\Feature\Cache;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use ProcessMaker\Cache\CacheABC;
use Tests\TestCase;

class CacheABCTest extends TestCase
{
    protected $cacheABC;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'redis');
    }

    protected function tearDown(): void
    {
        config()->set('cache.default', 'array');

        parent::tearDown();
    }

    public function testGetKeysByPatternWithValidConnectionAndMatchingKeys()
    {
        $this->cacheABC = $this->getMockForAbstractClass(CacheABC::class);

        $pattern = 'test-pattern';
        $prefix = config('cache.prefix');
        $keys = [$prefix . ':test-pattern:1', $prefix . ':test-pattern:2'];

        Redis::shouldReceive('connection')
            ->with('redis')
            ->andReturnSelf();

        Redis::shouldReceive('keys')
            ->with($prefix . '*')
            ->andReturn($keys);

        $result = $this->cacheABC->getKeysByPattern($pattern);

        $this->assertCount(2, $result);
        $this->assertEquals($keys, $result);
    }

    public function testGetKeysByPatternWithValidConnectionAndNoMatchingKeys()
    {
        $this->cacheABC = $this->getMockForAbstractClass(CacheABC::class);

        $pattern = 'non-matching-pattern';
        $prefix = config('cache.prefix');
        $keys = [$prefix . ':test-pattern:1', $prefix . ':test-pattern:2'];

        Redis::shouldReceive('connection')
            ->with('redis')
            ->andReturnSelf();

        Redis::shouldReceive('keys')
            ->with($prefix . '*')
            ->andReturn($keys);

        $result = $this->cacheABC->getKeysByPattern($pattern);

        $this->assertCount(0, $result);
    }

    public function testGetKeysByPatternWithInvalidConnection()
    {
        config()->set('cache.default', 'array');

        $this->cacheABC = $this->getMockForAbstractClass(CacheABC::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('`getKeysByPattern` method only supports Redis connections.');

        $this->cacheABC->getKeysByPattern('pattern');
    }

    public function testGetKeysByPatternWithExceptionDuringKeyRetrieval()
    {
        $this->cacheABC = $this->getMockForAbstractClass(CacheABC::class);

        $pattern = 'test-pattern';
        $prefix = config('cache.prefix');

        Redis::shouldReceive('connection')
            ->with('redis')
            ->andReturnSelf();

        Redis::shouldReceive('keys')
            ->with($prefix . '*')
            ->andThrow(new Exception('Redis error'));

        Log::shouldReceive('info')
            ->with('CacheABC' . 'Redis error')
            ->once();

        $result = $this->cacheABC->getKeysByPattern($pattern);

        $this->assertCount(0, $result);
    }
}
