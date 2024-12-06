<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\ServiceProvider;
use ProcessMaker\Cache\Monitoring\CacheMetricsDecorator;
use ProcessMaker\Cache\Monitoring\CacheMetricsInterface;
use ProcessMaker\Cache\Monitoring\RedisMetricsManager;
use ProcessMaker\Cache\Screens\LegacyScreenCacheAdapter;
use ProcessMaker\Cache\Screens\ScreenCacheFactory;
use ProcessMaker\Cache\Screens\ScreenCacheManager;
use ProcessMaker\Cache\Settings\SettingCacheManager;
use ProcessMaker\Managers\ScreenCompiledManager;

class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register the metrics manager
        $this->app->singleton(RedisMetricsManager::class);
        $this->app->bind(CacheMetricsInterface::class, RedisMetricsManager::class);

        // Register screen cache with metrics
        $this->app->singleton(ScreenCacheManager::class, function ($app) {
            $cache = new ScreenCacheManager(
                $app['cache'],
                $app->make(ScreenCompiledManager::class)
            );

            return new CacheMetricsDecorator(
                $cache,
                $app->make(RedisMetricsManager::class)
            );
        });

        // Register settings cache with metrics
        $this->app->singleton(SettingCacheManager::class, function ($app) {
            $cache = new SettingCacheManager($app['cache']);

            return new CacheMetricsDecorator(
                $cache,
                $app->make(RedisMetricsManager::class)
            );
        });

        // Register legacy screen cache with metrics
        $this->app->bind(LegacyScreenCacheAdapter::class, function ($app) {
            $cache = new LegacyScreenCacheAdapter(
                $app->make(ScreenCompiledManager::class)
            );

            return new CacheMetricsDecorator(
                $cache,
                $app->make(RedisMetricsManager::class)
            );
        });

        // Update the screen cache factory to use the metrics-enabled instances
        $this->app->extend(ScreenCacheFactory::class, function ($factory, $app) {
            return new class($app) extends ScreenCacheFactory {
                protected $app;

                public function __construct($app)
                {
                    $this->app = $app;
                }

                public static function create(): ScreenCacheInterface
                {
                    $manager = config('screens.cache.manager', 'legacy');

                    if ($manager === 'new') {
                        return app(ScreenCacheManager::class);
                    }

                    return app(LegacyScreenCacheAdapter::class);
                }
            };
        });
    }

    public function boot(): void
    {
        // Register the metrics commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \ProcessMaker\Console\Commands\CacheMetricsCommand::class,
                \ProcessMaker\Console\Commands\CacheMetricsSummaryCommand::class,
                \ProcessMaker\Console\Commands\CacheMetricsPopulateCommand::class,
                \ProcessMaker\Console\Commands\CacheMetricsClearCommand::class,
            ]);
        }
    }
}
