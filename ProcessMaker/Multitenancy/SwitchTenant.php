<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
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

        // Set the app.url config
        $app->config->set('app.url', $app->config->get('app.protocol') . '://' . $tenant->domain);

        if ($tenant->config_overrides) {
            foreach ($tenant->config_overrides as $key => $value) {
                $app->config->set($key, $value);
            }
        }
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
