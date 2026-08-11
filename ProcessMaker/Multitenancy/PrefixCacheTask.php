<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Console\Scheduling\Schedule;
use ProcessMaker\Console\Scheduling\FastSchedule;
use Prometheus\Storage\Redis as PrometheusRedis;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\PrefixCacheTask as SpatiePrefixCacheTask;

class PrefixCacheTask extends SpatiePrefixCacheTask
{
    private const LANDLORD_PROMETHEUS_PREFIX = 'PROMETHEUS_';

    private $originalSettingsPrefix;

    public function makeCurrent(IsTenant $tenant): void
    {
        $cachePrefix = 'tenant_' . $tenant->getKey() . ':';
        $this->setCachePrefix($cachePrefix);

        $this->originalSettingsPrefix = config('cache.stores.cache_settings.prefix');
        $tenantSettingsPrefix = 'tenant_' . $tenant->getKey() . ':' . $this->originalSettingsPrefix;
        config()->set('cache.stores.cache_settings.prefix', $tenantSettingsPrefix);
        $this->storeName = 'cache_settings';
        $this->setCachePrefix($cachePrefix);

        PrometheusRedis::setPrefix($cachePrefix . self::LANDLORD_PROMETHEUS_PREFIX);

        $this->resetScheduleCache();
    }

    public function forgetCurrent(): void
    {
        $this->setCachePrefix($this->originalPrefix);

        config()->set('cache.stores.cache_settings.prefix', $this->originalSettingsPrefix);
        $this->storeName = 'cache_settings';
        $this->setCachePrefix($this->originalPrefix);

        PrometheusRedis::setPrefix(self::LANDLORD_PROMETHEUS_PREFIX);

        $this->resetScheduleCache();
    }

    /**
     * Point the scheduler's mutexes at the freshly prefixed cache store so
     * onOneServer()/withoutOverlapping() locks are written under the current
     * (tenant or landlord) cache prefix.
     */
    private function resetScheduleCache(): void
    {
        if (!app()->resolved(Schedule::class)) {
            return;
        }

        $schedule = app(Schedule::class);

        if ($schedule instanceof FastSchedule) {
            $schedule->resetCache();
        }
    }
}
