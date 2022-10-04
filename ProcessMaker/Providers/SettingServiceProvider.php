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
     * Events to listen for which triggers a configuration re-caching
     *
     * @var array
     */
    public static $listenFor = [
        'eloquent.saved: ' . Setting::class,
        'eloquent.deleted: ' . Setting::class,
        MarkArtisanCachesAsInvalid::class,
    ];

    public function register(): void
    {
        $this->app->extend('config', function ($originConfig) {
            return (new ConfigRepository($originConfig))
                ->setConnectionResolver($this->app->make(Resolver::class))
                ->load();
        });

        $this->app['events']->listen(CommandFinished::class, function ($event) {
            if ($this->isCacheConfigCommand($event)) {
                $this->restartHorizon();
            }
        });

        foreach (static::$listenFor as $event) {
            $this->app['events']->listen($event, function () {
                static::$shouldCacheConfiguration = true;
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
            if (static::$shouldCacheConfiguration) {
                RefreshArtisanCaches::dispatch();
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
        if (!job_pending(TerminateHorizon::class)) {
            TerminateHorizon::dispatch();
        }
    }
}
