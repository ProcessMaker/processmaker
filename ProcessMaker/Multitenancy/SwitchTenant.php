<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Multitenancy\Broadcasting\TenantAwareBroadcastManager;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class SwitchTenant implements SwitchTenantTask
{
    use UsesMultitenancyConfig;

    public static $originalConfig = null;

    /**
     * Make the given tenant current.
     *
     * @param IsTenant $tenant
     * @return void
     */
    public function makeCurrent(IsTenant $tenant): void
    {
        \Log::info('SwitchTenant starting with tenant: ' . $tenant->id, ['domain' => request()->getHost()]);

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

        // Any config that relies on the original value needs to be saved in a static variable.
        // Otherwise, it will use the last tenant's modified value. This mostly only affects
        // the worker queue jobs since it reuses the same process.

        if (!self::$originalConfig) {
            self::$originalConfig = [
                'scheme' => parse_url(config('app.url'), PHP_URL_SCHEME),
                'cache.stores.cache_settings.prefix' => config('cache.stores.cache_settings.prefix'),
                'app.instance' => config('app.instance') ?? config('database.connections.landlord.database'),
                'script-runner-microservice.callback' => config('script-runner-microservice.callback'),
            ];
        }

        // We cant reload config here with (new LoadConfiguration())->bootstrap($app);
        // because it overrides dynamic configs set in packages (like docker-executor-php)
        // Instead, override each necessary config value on the fly.

        $appUrlHost = parse_url(config('app.url'), PHP_URL_HOST);
        $newConfig = [
            'filesystems.disks.local.root' => storage_path('app'),
            'filesystems.disks.public.root' => storage_path('app/public'),
            'filesystems.disks.profile.root' => storage_path('app/public/profile'),
            'filesystems.disks.settings.root' => storage_path('app/public/setting'),
            'filesystems.disks.private_settings.root' => storage_path('app/private/settings'),
            'filesystems.disks.web_services.root' => storage_path('app/private/web_services'),
            'filesystems.disks.tmp.root' => storage_path('app/public/tmp'),
            'filesystems.disks.samlidp.root' => storage_path('samlidp'),
            'filesystems.disks.decision_tables.root' => storage_path('decision-tables'),
            'l5-swagger.defaults.paths.docs' => storage_path('api-docs'),
            'cache.stores.cache_settings.prefix' =>  'tenant_id_' . $tenant->id . ':' . self::$originalConfig['cache.stores.cache_settings.prefix'],
            'app.instance' => self::$originalConfig['app.instance'] . '_' . $tenant->id,
            'script-runner-microservice.callback' => str_replace($appUrlHost, $tenant->domain, self::$originalConfig['script-runner-microservice.callback']),
        ];
        config($newConfig);

        // Set config from the entry in the tenants table
        $config = $tenant->config;
        if (isset($config['app.key'])) {
            // Decrypt using the landlord APP_KEY in the .env file.
            // All encryption after this will use the tenant's key.
            $config['app.key'] = Crypt::decryptString($config['app.key']);
        }
        config($config);

        // Prefix broadcasts with tenant id
        $app->singleton(BroadcastManager::class, fn ($app) => new TenantAwareBroadcastManager($app));
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
