<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use ProcessMaker\Services\MetricsService;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class SwitchTenant implements SwitchTenantTask
{
    /**
     * Make the given tenant current.
     *
     * @param IsTenant $tenant
     * @return void
     */
    public function makeCurrent(IsTenant $tenant): void
    {
        \Log::info('SwitchTenant starting with tenant: ' . $tenant->id);
        $app = app();

        // Set the tenant-specific storage path
        $tenantStoragePath = base_path('storage/tenant_' . $tenant->id);

        $app->setStoragePath($tenantStoragePath);

        // Create the tenant storage directory if it doesn't exist
        // TODO: Move these to somewhere else - should not be run on every request
        if (!file_exists($tenantStoragePath)) {
            mkdir($tenantStoragePath, 0755, true);
        }
        $tennantCacheFolder = base_path('bootstrap/cache/tenant_' . $tenant->id);
        if (!file_exists($tennantCacheFolder)) {
            mkdir($tennantCacheFolder, 0755, true);
        }

        // Reload the config
        putenv('APP_CONFIG_CACHE=' . $tennantCacheFolder . '/config.php');
        (new LoadConfiguration())->bootstrap($app);

        foreach ($tenant->config as $key => $value) {
            $app->config->set($key, $value);
        }

        $app->config->set('database.redis.options.prefix', 'tenant_' . $tenant->id . ':');
        $app->forgetInstance('redis'); // reload redis with the correct prefix
        $app->forgetInstance(MetricsService::class); // reload the metrics service (that uses redis)
    }

    /**
     * Forget the current tenant.
     *
     * @return void
     */
    public function forgetCurrent(): void
    {
    }
}
