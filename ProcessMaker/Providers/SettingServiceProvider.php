<?php

namespace ProcessMaker\Providers;

use Throwable;
use Exception;
use RuntimeException;
use ProcessMaker\Models\Setting;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Jobs\TerminateHorizon;
use ProcessMaker\Events\SettingsLoaded;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Events\CommandFinished;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Illuminate\Contracts\Config\Repository as RepositoryContract;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap settings into the global app configuration
     *
     * @return void
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function boot(): void
    {
        if (!$this->app->configurationIsCached()) {
            $this->loadSettingsFromDatabase($this->app->get('config'));
        }
    }

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
     *
     * @return void
     */
    protected function loadSettingsFromDatabase(RepositoryContract $repository): void
    {
        try {
            // Bind the settings keys and values to
            // the app configuration repository
            if ($repository->get($key = 'app.settings.loaded') !== true) {

                // Query only what we need from the database and
                // bind the key/config value to the global
                // app config for each
                foreach (Setting::select('id', 'key', 'config', 'format')->get() as $setting) {
                    $repository->set($setting->key, $setting->config);
                }

                // Mark all settings as bound to the config
                $repository->set($key, true);
            }
        } catch (RuntimeException $exception) {
            // Log the exception for debugging
            Log::notice('Exception caught while loading settings into app configuration', [
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTrace(),
            ]);

            // It's also possible a database connection has
            // not be established, such as when running
            // composer install. We need to catch that
            // exception and then bail.
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

        // If the command that was run cached or cleared
        // the cached configuration, then dispatch a
        // job to (ironically) restart horizon
        if (is_int(strpos($command, 'config:cache'))) {
            TerminateHorizon::dispatch();
        }
    }
}
