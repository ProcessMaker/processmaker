<?php

namespace ProcessMaker\Providers;

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
        if (!Schema::hasTable('settings')) {
            return;
        }

        foreach (Setting::select('id', 'key', 'config')->get() as $setting) {
            $repository->set($setting->key, $setting->config);
        }
    }
}
