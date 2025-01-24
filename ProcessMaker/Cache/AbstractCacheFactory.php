<?php

namespace ProcessMaker\Cache;

use Illuminate\Cache\CacheManager;
use ProcessMaker\Cache\Monitoring\CacheMetricsDecorator;
use ProcessMaker\Cache\Monitoring\CacheMetricsInterface;
use ProcessMaker\Cache\Monitoring\PrometheusMetricsManager;

abstract class AbstractCacheFactory implements CacheFactoryInterface
{
    protected static ?CacheInterface $testInstance = null;

    /**
     * Set a test instance for the factory
     *
     * @param CacheInterface|null $instance
     */
    public static function setTestInstance(?CacheInterface $instance): void
    {
        static::$testInstance = $instance;
    }

    /**
     * Create a new cache instance with metrics monitoring
     *
     * @param CacheManager $cacheManager
     * @param CacheMetricsInterface $metrics
     * @return CacheInterface
     */
    public static function create(CacheManager $cacheManager, CacheMetricsInterface $metrics): CacheInterface
    {
        if (static::$testInstance !== null) {
            return static::$testInstance;
        }

        // Create base cache instance
        $cache = static::createInstance($cacheManager);

        // Wrap with metrics decorator
        return new CacheMetricsDecorator($cache, $metrics);
    }

    /**
     * Get the current cache instance
     *
     * @return CacheInterface
     */
    protected static function getInstance(): CacheInterface
    {
        return static::create(app('cache'), app()->make(PrometheusMetricsManager::class));
    }

    /**
     * Create the specific cache instance
     *
     * @param CacheManager $cacheManager
     * @return CacheInterface
     */
    abstract protected static function createInstance(CacheManager $cacheManager): CacheInterface;
}
