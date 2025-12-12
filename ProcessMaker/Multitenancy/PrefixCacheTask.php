<?php

namespace ProcessMaker\Multitenancy;

use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\PrefixCacheTask as SpatiePrefixCacheTask;

class PrefixCacheTask extends SpatiePrefixCacheTask
{
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
    }

    public function forgetCurrent(): void
    {
        $this->setCachePrefix($this->originalPrefix);

        config()->set('cache.stores.cache_settings.prefix', $this->originalSettingsPrefix);
        $this->storeName = 'cache_settings';
        $this->setCachePrefix($this->originalPrefix);
    }
}
