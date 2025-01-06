<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\ServiceProvider;
use ProcessMaker\Cache\Monitoring\CacheMetricsInterface;
use ProcessMaker\Cache\Screens\LegacyScreenCacheAdapter;
use ProcessMaker\Cache\Screens\ScreenCacheFactory;
use ProcessMaker\Cache\Screens\ScreenCacheManager;
use ProcessMaker\Cache\Settings\SettingCacheFactory;
use ProcessMaker\Cache\Settings\SettingCacheManager;
use ProcessMaker\Managers\ScreenCompiledManager;

class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register screen cache with metrics
        $this->app->singleton(ScreenCacheManager::class, function ($app) {
            return ScreenCacheFactory::create(
                $app['cache'],
                $app->make(CacheMetricsInterface::class)
            );
        });

        // Register settings cache with metrics
        $this->app->singleton(SettingCacheManager::class, function ($app) {
            return SettingCacheFactory::create(
                $app['cache'],
                $app->make(CacheMetricsInterface::class)
            );
        });

        // Register legacy screen cache with metrics
        $this->app->bind(LegacyScreenCacheAdapter::class, function ($app) {
            return ScreenCacheFactory::create(
                $app['cache'],
                $app->make(CacheMetricsInterface::class)
            );
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
