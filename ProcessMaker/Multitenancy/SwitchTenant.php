<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Support\Facades\DB;
use PDO;
use ProcessMaker\Services\MetricsService;
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
        \Log::info('SwitchTenant starting with tenant: ' . $tenant->id);

        $this->setTenantDatabaseConnection($tenant);

        // Set the tenant-specific storage path
        $tenantStoragePath = base_path('storage/tenant_' . $tenant->id);

        $app = app();
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

        /**
         * CACHE
         */
        $app->config->set('database.redis.options.prefix', 'tenant_' . $tenant->id . ':');

        // reload redis with the correct prefix
        $app->forgetInstance('redis');

        // remove the resolved redis instance from the cache container
        $app->make('cache')->forgetDriver('redis');

        // cache_settings is a store that uses the redis driver so it also needs to be removed
        $app->make('cache')->forgetDriver('cache_settings');

        // The MetricsService is created using the redis driver so it needs to be reloaded
        $app->forgetInstance(MetricsService::class);
    }

    /**
     * Forget the current tenant.
     *
     * @return void
     */
    public function forgetCurrent(): void
    {
    }

    /**
     * Set the tenant database connection.
     *
     * Copied from laravel-multitenancy's src/Tasks/SwitchTenantDatabaseTask.php
     *
     * @param IsTenant $tenant
     * @return void
     */
    private function setTenantDatabaseConnection(IsTenant $tenant): void
    {
        $tenantConnectionName = $this->tenantDatabaseConnectionName();

        $tenantDBKey = "database.connections.{$tenantConnectionName}";

        $databaseName = $tenant->getDatabaseName();
        $username = $tenant->username;
        $password = $tenant->password;

        $setConfig = [
            "{$tenantDBKey}.database" => $databaseName,
        ];
        if ($username) {
            $setConfig["{$tenantDBKey}.username"] = $username;
        }
        if ($password) {
            $setConfig["{$tenantDBKey}.password"] = $password;
        }

        config($setConfig);

        app('db')->extend($tenantConnectionName, function ($config, $name) use ($databaseName, $username, $password) {
            $config['database'] = $databaseName;
            if ($username) {
                $config['username'] = $username;
            }
            if ($password) {
                $config['password'] = $password;
            }

            return app('db.factory')->make($config, $name);
        });

        DB::purge($tenantConnectionName);

        // Octane will have an old `db` instance in the Model::$resolver.
        Model::setConnectionResolver(app('db'));
    }
}
