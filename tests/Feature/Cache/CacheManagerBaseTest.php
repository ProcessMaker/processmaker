<?php

namespace Tests\Feature\Cache;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use ProcessMaker\Cache\CacheManagerBase;
use ProcessMaker\Cache\CacheManagerException;
use Tests\TestCase;

class CacheManagerBaseTest extends TestCase
{
    protected $cacheManagerBase;

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
        $this->cacheManagerBase = $this->getMockForAbstractClass(CacheManagerBase::class);

        $pattern = 'test-pattern';
        $prefix = config('cache.prefix');
        $keys = [$prefix . ':test-pattern:1', $prefix . ':test-pattern:2'];

        Redis::shouldReceive('connection')
            ->with('redis')
            ->andReturnSelf();

        Redis::shouldReceive('keys')
            ->with($prefix . '*')
            ->andReturn($keys);

        $result = $this->cacheManagerBase->getKeysByPattern($pattern);

        $this->assertCount(2, $result);
        $this->assertEquals($keys, $result);
    }

    public function testGetKeysByPatternWithValidConnectionAndNoMatchingKeys()
    {
        $this->cacheManagerBase = $this->getMockForAbstractClass(CacheManagerBase::class);

        $pattern = 'non-matching-pattern';
        $prefix = config('cache.prefix');
        $keys = [$prefix . ':test-pattern:1', $prefix . ':test-pattern:2'];

        Redis::shouldReceive('connection')
            ->with('redis')
            ->andReturnSelf();

        Redis::shouldReceive('keys')
            ->with($prefix . '*')
            ->andReturn($keys);

        $result = $this->cacheManagerBase->getKeysByPattern($pattern);

        $this->assertCount(0, $result);
    }

    public function testGetKeysByPatternWithInvalidConnection()
    {
        config()->set('cache.default', 'array');

        $this->cacheManagerBase = $this->getMockForAbstractClass(CacheManagerBase::class);

        $this->expectException(CacheManagerException::class);
        $this->expectExceptionMessage('`getKeysByPattern` method only supports Redis connections.');

        $this->cacheManagerBase->getKeysByPattern('pattern');
    }

    public function testGetKeysByPatternWithExceptionDuringKeyRetrieval()
    {
        $this->cacheManagerBase = $this->getMockForAbstractClass(CacheManagerBase::class);

        $pattern = 'test-pattern';
        $prefix = config('cache.prefix');

        Redis::shouldReceive('connection')
            ->with('redis')
            ->andReturnSelf();

        Redis::shouldReceive('keys')
            ->with($prefix . '*')
            ->andThrow(new Exception('Redis error'));

        Log::shouldReceive('info')
            ->with('CacheManagerBase: ' . 'Redis error')
            ->once();

        $result = $this->cacheManagerBase->getKeysByPattern($pattern);

        $this->assertCount(0, $result);
    }
}
