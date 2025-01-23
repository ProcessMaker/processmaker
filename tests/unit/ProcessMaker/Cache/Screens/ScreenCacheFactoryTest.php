<?php

namespace Tests\Unit\ProcessMaker\Cache\Screens;

use Illuminate\Cache\CacheManager;
use Illuminate\Support\Facades\Config;
use ProcessMaker\Cache\CacheInterface;
use ProcessMaker\Cache\Monitoring\CacheMetricsDecorator;
use ProcessMaker\Cache\Monitoring\RedisMetricsManager;
use ProcessMaker\Cache\Screens\LegacyScreenCacheAdapter;
use ProcessMaker\Cache\Screens\ScreenCacheFactory;
use ProcessMaker\Cache\Screens\ScreenCacheManager;
use ProcessMaker\Managers\ScreenCompiledManager;
use Tests\TestCase;

class ScreenCacheFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ScreenCacheFactory::setTestInstance(null);

        // Bind necessary dependencies
        $this->app->singleton(ScreenCompiledManager::class);
        $this->app->singleton(RedisMetricsManager::class);
    }

    public function testCreateNewCacheManager()
    {
        Config::set('screens.cache.manager', 'new');

        // Create a mock for ScreenCacheManager
        $mockManager = $this->createMock(ScreenCacheManager::class);
        $this->app->instance(ScreenCacheManager::class, $mockManager);

        $cache = ScreenCacheFactory::create(app('cache'), app(RedisMetricsManager::class));

        // Should be wrapped with metrics decorator
        $this->assertInstanceOf(CacheMetricsDecorator::class, $cache);

        // Get the underlying cache implementation
        $reflection = new \ReflectionClass($cache);
        $property = $reflection->getProperty('cache');
        $property->setAccessible(true);
        $underlyingCache = $property->getValue($cache);

        // Verify it's the new cache manager
        $this->assertInstanceOf(ScreenCacheManager::class, $underlyingCache);
    }

    public function testCreateLegacyCacheAdapter()
    {
        Config::set('screens.cache.manager', 'legacy');

        $cache = ScreenCacheFactory::create(app('cache'), app(RedisMetricsManager::class));

        // Should be wrapped with metrics decorator
        $this->assertInstanceOf(CacheMetricsDecorator::class, $cache);

        // Get the underlying cache implementation
        $reflection = new \ReflectionClass($cache);
        $property = $reflection->getProperty('cache');
        $property->setAccessible(true);
        $underlyingCache = $property->getValue($cache);

        // Verify it's the legacy adapter
        $this->assertInstanceOf(LegacyScreenCacheAdapter::class, $underlyingCache);
    }

    public function testMetricsIntegrationWithBothAdapters()
    {
        // Test with new cache manager
        Config::set('screens.cache.manager', 'new');

        // Create a mock for ScreenCacheManager
        $mockManager = $this->createMock(ScreenCacheManager::class);
        $this->app->instance(ScreenCacheManager::class, $mockManager);

        $newCache = ScreenCacheFactory::create(app('cache'), app(RedisMetricsManager::class));
        $this->verifyMetricsDecorator($newCache, ScreenCacheManager::class);

        // Test with legacy adapter
        Config::set('screens.cache.manager', 'legacy');
        $legacyCache = ScreenCacheFactory::create(app('cache'), app(RedisMetricsManager::class));
        $this->verifyMetricsDecorator($legacyCache, LegacyScreenCacheAdapter::class);
    }

    protected function verifyMetricsDecorator($cache, $expectedCacheClass)
    {
        // Verify it's a metrics decorator
        $this->assertInstanceOf(CacheMetricsDecorator::class, $cache);

        // Get reflection for property access
        $reflection = new \ReflectionClass($cache);

        // Check cache implementation
        $cacheProperty = $reflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $this->assertInstanceOf($expectedCacheClass, $cacheProperty->getValue($cache));

        // Check metrics manager
        $metricsProperty = $reflection->getProperty('metrics');
        $metricsProperty->setAccessible(true);
        $this->assertInstanceOf(RedisMetricsManager::class, $metricsProperty->getValue($cache));
    }

    /**
     * Test invalidate with new cache manager
     *
     * @test
     */
    public function testInvalidateWithNewCacheManager()
    {
        Config::set('screens.cache.manager', 'new');

        // Create a mock for ScreenCacheManager
        $mockManager = $this->createMock(ScreenCacheManager::class);
        $mockManager->expects($this->once())
            ->method('invalidate')
            ->with(['screen_id' => 5, 'language' => 'es']);

        $this->app->instance(ScreenCacheManager::class, $mockManager);

        $cache = ScreenCacheFactory::create(
            app('cache'),
            app(RedisMetricsManager::class)
        );

        $cache->invalidate(['screen_id' => 5, 'language' => 'es']);

        // No assertion needed since we verified the method was called with expects()
    }

    /**
     * Test invalidate with legacy cache adapter
     *
     * @test
     */
    public function testInvalidateWithLegacyCache()
    {
        Config::set('screens.cache.manager', 'legacy');

        // Create mock for ScreenCompiledManager
        $mockCompiledManager = $this->createMock(ScreenCompiledManager::class);
        $mockCompiledManager->expects($this->once())
            ->method('deleteScreenCompiledContent')
            ->with(5, 'es')
            ->willReturn(true);

        $this->app->instance(ScreenCompiledManager::class, $mockCompiledManager);

        $cache = ScreenCacheFactory::create(app('cache'), app(RedisMetricsManager::class));
        $result = $cache->invalidate(['screen_id' => 5, 'language' => 'es']);

        $this->assertNull($result);
    }

    /**
     * Test getScreenCache method returns same instance as create
     *
     * @test
     */
    public function testGetScreenCacheReturnsSameInstanceAsCreate()
    {
        // Get instances using both methods
        $instance1 = ScreenCacheFactory::create(app('cache'), app(RedisMetricsManager::class));
        $instance2 = ScreenCacheFactory::getScreenCache();

        // Verify they are the same type and have same metrics wrapper
        $this->assertInstanceOf(CacheMetricsDecorator::class, $instance1);
        $this->assertInstanceOf(CacheMetricsDecorator::class, $instance2);

        // Get underlying cache implementations
        $reflection = new \ReflectionClass(CacheMetricsDecorator::class);
        $property = $reflection->getProperty('cache');
        $property->setAccessible(true);

        $cache1 = $property->getValue($instance1);
        $cache2 = $property->getValue($instance2);

        // Verify underlying implementations are of same type
        $this->assertEquals(get_class($cache1), get_class($cache2));
    }

    /**
     * Test factory respects test instance
     *
     * @test
     */
    public function testFactoryRespectsTestInstance()
    {
        // Create a mock for ScreenCacheInterface
        $mockInterface = $this->createMock(CacheInterface::class);

        // Set the test instance in the factory
        ScreenCacheFactory::setTestInstance($mockInterface);

        // Retrieve the instance from the factory
        $instance = ScreenCacheFactory::create(app('cache'), app(RedisMetricsManager::class));

        // Assert that the instance is the mock we set
        $this->assertSame($mockInterface, $instance);
    }

    /**
     * Test metrics decoration is applied correctly
     *
     * @test
     */
    public function testMetricsDecorationIsAppliedCorrectly()
    {
        // Test with both cache types
        $cacheTypes = ['new', 'legacy'];

        foreach ($cacheTypes as $type) {
            Config::set('screens.cache.manager', $type);

            $cache = ScreenCacheFactory::create(app('cache'), app(RedisMetricsManager::class));

            // Verify outer wrapper is metrics decorator
            $this->assertInstanceOf(CacheMetricsDecorator::class, $cache);

            // Get and verify metrics instance
            $reflection = new \ReflectionClass(CacheMetricsDecorator::class);
            $metricsProperty = $reflection->getProperty('metrics');
            $metricsProperty->setAccessible(true);

            $metrics = $metricsProperty->getValue($cache);
            $this->assertInstanceOf(RedisMetricsManager::class, $metrics);
        }
    }

    /**
     * Test factory with invalid configuration
     *
     * @test
     */
    public function testFactoryWithInvalidConfiguration()
    {
        Config::set('screens.cache.manager', 'invalid');

        // Should default to legacy cache
        $cache = ScreenCacheFactory::create(app('cache'), app(RedisMetricsManager::class));

        $reflection = new \ReflectionClass(CacheMetricsDecorator::class);
        $property = $reflection->getProperty('cache');
        $property->setAccessible(true);

        $underlyingCache = $property->getValue($cache);
        $this->assertInstanceOf(LegacyScreenCacheAdapter::class, $underlyingCache);
    }

    protected function tearDown(): void
    {
        ScreenCacheFactory::setTestInstance(null);
        parent::tearDown();
    }
}
