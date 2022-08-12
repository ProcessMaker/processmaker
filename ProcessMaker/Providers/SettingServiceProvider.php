<?php

namespace ProcessMaker\Providers;

use Throwable;
use ProcessMaker\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Jobs\TerminateHorizon;
use Laravel\Horizon\Console\TerminateCommand;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Config\Repository as RepositoryContract;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * Register an event listener for the service provider
     *
     * @return void
     */
    public function register()
    {
        $this->app['events']->listen(CommandFinished::class, [$this, 'configurationWasCached']);
    }

    /**
     * Bootstrap settings into the global app configuration
     *
     * @return void
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function boot()
    {
        if (!$this->app->configurationIsCached()) {
            $this->loadSettingsFromDatabase($this->app->get('config'));
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

        // If the command that was run cached the configuration,
        // then dispatch a job to (ironically) restart horizon
        if (is_int(strpos($command, 'config:cache'))) {
            TerminateHorizon::dispatch();
        }
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
            // Attempt to connect to the database and check
            // for the settings table, if it doesn't exist,
            // then bail
            if (!Schema::hasTable('settings')) {
                return;
            }

            // It's also possible a database connection has
            // not be established, such as when running
            // composer install. We need to catch that
            // exception and then bail.
        } catch (Throwable $exception) {
            return;
        }

        // Query only what we need from the database and
        // set each item as a key/value pair in the
        // global app config
        foreach (Setting::select('id', 'key', 'config')->get() as $setting) {
            $repository->set($setting->key, $setting->config);
        }
    }
}
