<?php

namespace ProcessMaker\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Support\ServiceProvider;
use ProcessMaker\Events\MarkArtisanCachesAsInvalid;
use ProcessMaker\Jobs\RefreshArtisanCaches;
use ProcessMaker\Jobs\TerminateHorizon;
use ProcessMaker\Models\Setting;
use ProcessMaker\Repositories\ConfigRepository;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * Indicates an event or condition exists causing
     * the current cached config to be out of date
     *
     * @var bool
     */
    protected static $shouldCacheConfiguration = false;

    /**
     * Eloquent events which trigger configuration-related updates
     *
     * @var array
     */
    public static $listen = [
        'eloquent.saved: ' . Setting::class,
        'eloquent.deleted: ' . Setting::class,
    ];

    /**
     * Events to listen for which triggers a configuration re-caching
     *
     * @return array
     */
    public static function cacheConfigurationEvents(): array
    {
        return array_merge([MarkArtisanCachesAsInvalid::class], static::$listen);
    }

    public function register(): void
    {
        // Swap out the framework's configuration repository in order to
        // load in our settings from the database and populate the config
        // with their respective keys and values
        $this->app->extend('config', function ($originConfig) {
            return (new ConfigRepository($originConfig))
                ->setConnectionResolver($this->app->make(Resolver::class))
                ->load();
        });

        // When the config:cache command is run, we need to restart
        // horizon to ensure they use the latest version of the
        // cached configuration
        $this->app['events']->listen(CommandFinished::class, function ($event) {
            if ($this->isCacheConfigCommand($event)) {
                //$this->restartHorizon();
            }
        });

        // Listen for the events which signify we need to
        // re-cache the configuration
        foreach (static::cacheConfigurationEvents() as $event) {
            $this->app['events']->listen($event, function () {
                static::$shouldCacheConfiguration = true;
            });
        }

        // Listen for Eloquent "saved" and "deleted" events,
        // since these tell us we need to update the app
        // configuration dynamically ("manually") so it'll
        // reflect those changes for the remainder of the
        // PHP process the update occurred on
        foreach (static::$listen as $event) {
            $this->app['events']->listen($event, function (Setting $setting) {
                $this->app->make('config')
                          ->set([$setting->key => $setting->config]);
            });
        }
    }

    /**
     * Bind setting keys and config values to the global app configuration.
     *
     * @return void
     */
    public function boot(): void
    {
        // Add a callback to run before the app terminates
        // which will dispatch a job to re-cache the
        // configuration if any settings have changed
        $this->app->terminating(function () {
            if (!static::$shouldCacheConfiguration) {
                return;
            }

            if (!job_pending($job = RefreshArtisanCaches::class)) {
                $job::dispatch();
            }
        });
    }

    /**
     * Event is an Artisan command being run which caches the config, e.g. config:cache
     *
     * @param $event
     *
     * @return bool
     */
    public function isCacheConfigCommand($event): bool
    {
        $command = $event->command
            ?? $event->input->getArguments()['command']
            ?? 'default';

        return is_int(strpos($command, 'config:cache'));
    }

    /**
     * Restart the horizon queue manager whenever the configuration is cached so ensure
     * the new configuration is picked up by the supervisor/queue processes.
     */
    public function restartHorizon(): void
    {
        // If there's already a job pending top terminate
        // horizon, we don't need to queue another one
        if (!job_pending($job = TerminateHorizon::class)) {
            $job::dispatch();
        }
    }
}
