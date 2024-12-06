<?php

namespace Tests\Unit\ProcessMaker\Cache\Screens;

use Illuminate\Support\Facades\Config;
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

        $cache = ScreenCacheFactory::create();

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

        $cache = ScreenCacheFactory::create();

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

        $newCache = ScreenCacheFactory::create();
        $this->verifyMetricsDecorator($newCache, ScreenCacheManager::class);

        // Test with legacy adapter
        Config::set('screens.cache.manager', 'legacy');
        $legacyCache = ScreenCacheFactory::create();
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

    protected function tearDown(): void
    {
        ScreenCacheFactory::setTestInstance(null);
        parent::tearDown();
    }
}
