<?php

namespace ProcessMaker\Providers;

use Throwable;
use ProcessMaker\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
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
    public function boot()
    {
        if (!$this->app->configurationIsCached()) {
            $this->loadSettingsFromDatabase($this->app->get('config'));
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
