<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Broadcasting\BroadcastManager;
use ProcessMaker\Application;
use ProcessMaker\Multitenancy\Broadcasting\TenantAwareBroadcastManager;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class SwitchTenant implements SwitchTenantTask
{
    use UsesMultitenancyConfig;

    /**
     * Make the given tenant current.
     *
     * @param IsTenant $tenant
     * @return void
     */
    public function makeCurrent(IsTenant $tenant): void
    {
        $app = app();

        \Log::debug('SwitchTenant: ' . $tenant->id, ['domain' => request()->getHost()]);

        // Set the tenant's domain in the request headers. Used for things like the global url() helper.
        request()->headers->set('host', $tenant->domain);

        // Use tenant's translation files
        $app->useLangPath(resource_path('lang/tenant_' . $tenant->id));

        $this->overrideConfigs($app, $tenant);

        // Extend BroadcastManager to our custom implementation that prefixes the channel names with the tenant id.
        $app->extend(BroadcastManager::class, function ($manager, $app) use ($tenant) {
            return new TenantAwareBroadcastManager($app, $tenant->id);
        });
    }

    /**
     * Forget the current tenant.
     *
     * @return void
     */
    public function forgetCurrent(): void
    {
    }

    private function overrideConfigs(Application $app, IsTenant $tenant)
    {
        if ($app->configurationIsCached()) {
            return;
        }

        $newConfig = [
            'app.instance' => config('app.instance') . '_' . $tenant->id,
        ];

        if (!isset($tenant->config['cache.stores.cache_settings.prefix'])) {
            $newConfig['cache.stores.cache_settings.prefix'] =
                'tenant_id_' . $tenant->id . ':' . $tenant->getOriginalValue('CACHE_SETTING_PREFIX');
        }

        if (!isset($tenant->config['script-runner-microservice.callback'])) {
            $newConfig['script-runner-microservice.callback'] = str_replace(
                $tenant->getOriginalValue('APP_URL'),
                config('app.url'),
                $tenant->getOriginalValue('SCRIPT_MICROSERVICE_CALLBACK')
            );
        }

        if (!isset($tenant->config['app.docker_host_url'])) {
            // There is no specific override in the tenant's config so set it to the app url
            $newConfig['app.docker_host_url'] = config('app.url');
        }

        // Set config from the entry in the tenants table
        $config = $tenant->config;
        foreach ($config as $key => $value) {
            if ($key === 'app.key' || $key === 'app.url') {
                continue;
            }
            $newConfig[$key] = $value;
        }

        config($newConfig);
    }
}
