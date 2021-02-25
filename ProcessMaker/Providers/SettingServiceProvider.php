<?php

namespace ProcessMaker\Providers;

use Adbar\Dot;
use Artisan;
use DB;
use Exception;
use Log;
use Illuminate\Support\ServiceProvider;
use ProcessMaker\Models\Setting;

class SettingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $path = app_path().'/Helpers/SettingsHelper.php';
        require_once($path);
    }
    
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (! config('settings-loaded')) {
            try {
                $settings = Setting::select('key', 'config', 'format')->get();
                foreach ($settings as $setting) {
                    config([$setting->key => $setting->config]);
                }
                config(['settings_loaded' => true]);
            } catch (Exception $e) {
                Log::error('Unable to load settings from database', [
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }
}
