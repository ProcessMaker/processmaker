<?php

namespace ProcessMaker\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\ConnectionResolverInterface as ConnectionResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use ProcessMaker\Events\SettingsLoaded;
use ProcessMaker\Jobs\TerminateHorizon;
use ProcessMaker\Models\Setting;
use ProcessMaker\Repositories\RedisJobRepository;
use RuntimeException;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * Register an event listener for the service provider
     *
     * @return void
     */
    public function register(): void
    {
        $this->app['events']->listen(CommandFinished::class, [$this, 'configurationWasCached']);
    }

    /**
     * Bind setting keys and config values to the global app configuration
     *
     * @param  \Illuminate\Contracts\Config\Repository  $repository
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     *
     * @return void
     */
    public function boot(ConfigRepository $repository, ConnectionResolver $resolver): void
    {
        if ($this->app->configurationIsCached()) {
            return;
        }

        try {
            // Bind the settings keys and values to
            // the app configuration repository
            if ($repository->get($key = 'app.settings.loaded') !== true) {
                // Set up the database connection
                $this->bindConnectionResolver($resolver);

                // Query only what we need from the database and
                // bind the key/config value to the global
                // app config for each
                foreach (Setting::select('id', 'key', 'config', 'format')->get() as $setting) {
                    if ($repository->get($setting->key) !== $setting->config) {
                        $repository->set($setting->key, $setting->config);
                    }
                }

                // Mark all settings as bound to the config
                $repository->set($key, true);
            }

            // It's also possible a database connection has
            // not be established, such as when running
            // composer install. We need to catch that
            // exception and then bail.
        } catch (RuntimeException $exception) {
            // Log the exception for debugging
            Log::notice('Exception caught while loading settings into app configuration', [
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTrace(),
            ]);

            // Mark the settings as not loaded
            // into the configuration
            $repository->set($key, false);
        } finally {
            // Fire off the SettingsLoaded event to indicate
            // they are ready in the config()
            if ($repository->get($key) === true) {
                SettingsLoaded::dispatch($repository);
            }
        }
    }

    /**
     * Make and bind a database connection resolver for Settings models
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     *
     * @return void
     */
    public function bindConnectionResolver(ConnectionResolver $resolver): void
    {
        Setting::setConnectionResolver($resolver);
    }

    /**
     * Restart the horizon queue manager whenever the configuration is cached so ensure
     * the new configuration is picked up by the supervisor/queue processes
     *
     * @param  \Illuminate\Console\Events\CommandFinished  $event
     *
     * @return void
     */
    public function configurationWasCached(CommandFinished $event): void
    {
        $command = $event->command ?? $event->input->getArguments()['command'] ?? 'default';

        // Check if the command just run cached the
        // app configuration, which is the only
        // one we care about
        if (!is_int(strpos($command, 'config:cache'))) {
            return;
        }

        // If there's already a job pending top terminate
        // horizon, we don't need to queue another one
        if (!app(RedisJobRepository::class)->isPending(TerminateHorizon::class)) {
            TerminateHorizon::dispatch();
        }
    }
}
