<?php

namespace ProcessMaker\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use ProcessMaker\Events\SettingsLoaded;
use ProcessMaker\Jobs\TerminateHorizon;
use ProcessMaker\Models\Setting;
use RuntimeException;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * Register an event listener for the service provider.
     */
    public function register(): void
    {
        $this->app['events']->listen(CommandFinished::class, function (CommandFinished $event) {
            $this->configurationWasCached($event);
        });
    }

    /**
     * Bind setting keys and config values to the global app configuration.
     */
    public function boot(Repository $repository, Resolver $resolver): void
    {
        try {
            // Bind the settings keys and values to
            // the app configuration repository
            if (true !== $repository->get($key = 'app.settings.loaded')) {
                // Set up the database connection
                Setting::setConnectionResolver($resolver);

                // Query only what we need from the database and
                // bind the key/config value to the global
                // app config for each
                foreach (Setting::select('id', 'key', 'config', 'format')->get() as $setting) {
                    $repository->set($setting->key, $setting->config);
                }

                // Mark all settings as bound to the config
                $repository->set($key, true);
            }

            // It's also possible a database connection has
            // not been established, such as when running
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
            if (true === $repository->get($key)) {
                SettingsLoaded::dispatch($repository);
            }
        }
    }

    /**
     * Restart the horizon queue manager whenever the configuration is cached so ensure
     * the new configuration is picked up by the supervisor/queue processes.
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
        if (!job_pending(TerminateHorizon::class)) {
            TerminateHorizon::dispatch();
        }
    }
}
