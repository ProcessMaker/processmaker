<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    public function register()
    {
        require_once app_path('/Helpers/SettingsHelper.php');
    }

    /**
     * Bootstrap services.
     *
     * @throws \Exception
     */
    public function boot()
    {
        if (! config('settings_loaded')) {
            cache_settings();
        }
    }
}
