<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Env;
use Laravel\Passport\ApiTokenCookieFactory;
use Laravel\Passport\ClientRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use ProcessMaker\Application;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class SwitchTenant implements SwitchTenantTask
{
    use UsesMultitenancyConfig;

    public static $landlordValues = null;

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

        // Save the landlord values for later use
        if (!self::$landlordValues) {
            self::$landlordValues = $app->make('config')->all();
        }

        // Set the tenant's domain in the request headers. Used for things like the global url() helper.
        request()->headers->set('host', $tenant->domain);

        $this->overrideConfigs($app, $tenant);
    }

    /**
     * Forget the current tenant.
     *
     * @return void
     */
    public function forgetCurrent(): void
    {
        $app = app();
        $app->useStoragePath(base_path('storage'));

        $this->setConfig('logging.channels.daily.path', storage_path('logs/processmaker.log'));
        $app->make('log')->reset();
        $app->forgetInstance('log');

        $app->useLangPath(resource_path('lang'));

        // app key / encrypter
        $this->setConfig('app.key', $this->landlordConfig('app.key'));
        $this->flushTenantSensitiveSingletons($app);
    }

    /**
     * Drop container instances that captured the previous tenant's APP_KEY or oauth keys.
     *
     * Passport's ResourceServer/AuthorizationServer and Encrypter are singletons.
     * Under Octane they can outlive a tenant switch and keep signing or verifying
     * laravel_token cookies with the wrong key (API 401s).
     */
    private function flushTenantSensitiveSingletons(Application $app): void
    {
        $app->forgetInstance('encrypter');
        $app->forgetInstance(ResourceServer::class);
        $app->forgetInstance(AuthorizationServer::class);
        $app->forgetInstance(ClientRepository::class);
        $app->forgetInstance(ApiTokenCookieFactory::class);

        if ($app->resolved('auth.driver')) {
            $app->forgetInstance('auth.driver');
        }

        if ($app->resolved('auth')) {
            $app->make('auth')->forgetGuards();
        }
    }

    private function landlordConfig($key)
    {
        return Arr::get(self::$landlordValues, $key);
    }

    private function setConfig($key, $value)
    {
        app()->make('config')->set($key, $value);
    }

    private function setEnvironmentVariable($key, $value)
    {
        putenv("$key=$value");
        $_SERVER[$key] = $value;
        $_ENV[$key] = $value;
    }

    private function overrideConfigs(Application $app, IsTenant $tenant)
    {
        $this->setEnvironmentVariable('APP_URL', $tenant->config['app.url']);

        $this->setConfig('app.instance', $tenant->database);
        $this->setConfig('app.url', $tenant->config['app.url']);

        // Microservice callback url
        if (!isset($tenant->config['script-runner-microservice.callback'])) {
            $this->setConfig('script-runner-microservice.callback', str_replace(
                $this->landlordConfig('app.url'),
                $tenant->config['app.url'],
                $this->landlordConfig('script-runner-microservice.callback')
            ));
        }

        // Filesystem roots
        $landlordStoragePath = base_path('storage');
        $newStoragePath = base_path('storage/tenant_' . $tenant->id);
        foreach ($this->landlordConfig('filesystems.disks') as $disk => $config) {
            if (isset($config['root'])) {
                $this->setConfig('filesystems.disks.' . $disk . '.root', str_replace(
                    $landlordStoragePath,
                    $newStoragePath,
                    $config['root']
                ));
            }
            // URLs
            if (isset($config['url'])) {
                $this->setConfig('filesystems.disks.' . $disk . '.url', str_replace(
                    $this->landlordConfig('app.url'),
                    $tenant->config['app.url'],
                    $config['url']
                ));
            }
        }
        $app->useStoragePath($newStoragePath);

        // Lang path
        $app->useLangPath(resource_path('lang/tenant_' . $tenant->id));
        $this->setConfig('filesystems.disks.lang.root', lang_path());

        // app key / encrypter
        $landlordEncrypter = $app->make('encrypter');
        $this->setConfig('app.key', $landlordEncrypter->decryptString($tenant->config['app.key']));
        $this->flushTenantSensitiveSingletons($app);

        // Logging
        $this->setConfig('logging.channels.daily.path', storage_path('logs/processmaker.log'));
        $app->make('log')->reset();
        $app->forgetInstance('log');

        // url() helper
        app(UrlGenerator::class)->useOrigin($tenant->config['app.url']);

        // NOTE: Cache prefix and cache settings prefix are handled in PrefixCacheTask

        if (!isset($tenant->config['app.docker_host_url'])) {
            // There is no specific override in the tenant's config so set it to the app url
            $this->setConfig('app.docker_host_url', $tenant->config['app.url']);
        }

        // Set config from the entry in the tenants table
        $config = $tenant->config;
        foreach ($config as $key => $value) {
            if ($key === 'app.key' || $key === 'app.url') {
                continue;
            }
            $this->setConfig($key, $value);
        }
    }
}
