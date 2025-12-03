<?php

namespace ProcessMaker\Observers;

use Exception;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Cache\Settings\SettingCacheFactory;
use ProcessMaker\Jobs\RefreshArtisanCaches;
use ProcessMaker\Models\Setting;

class SettingObserver
{
    private static $added_refresh_artisan_caches = false;

    /**
     * Handle the setting "created" event.
     *
     * @param  Setting  $setting
     * @return void
     */
    public function saving(Setting $setting)
    {
        $config = $setting->getAttributes()['config'];

        if ($config === 'null' || $config === null) {
            $setting->config = null;

            $this->invalidateSettingCache($setting);

            return;
        }

        switch ($setting->format) {
            case 'text':
            case 'textarea':
            case 'file':
            case 'choice':
                $setting->config = $config;
                break;
            case 'boolean':
                $setting->config = filter_var($config, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'object':
                if (is_string($config)) {
                    try {
                        $return = json_decode($config);
                        $return = json_encode($return);
                    } catch (Exception $e) {
                        $return = $config;
                    }
                } else {
                    $return = json_encode($config);
                }

                $setting->config = $return;
                break;
            case 'array':
            case 'checkboxes':
            default:
                if (is_string($config)) {
                    try {
                        $return = json_decode($config, true);
                        $return = json_encode($return);
                    } catch (Exception $e) {
                        $return = $config;
                    }
                } else {
                    $return = json_encode($config);
                }

                $setting->config = $return;
                break;
        }

        $this->invalidateSettingCache($setting);
    }

    /**
     * Handle the setting "deleted" event.
     *
     * @param  Setting  $setting
     * @return void
     */
    public function deleted(Setting $setting): void
    {
        $this->invalidateSettingCache($setting);
    }

    private function invalidateSettingCache(Setting $setting)
    {
        $settingCache = SettingCacheFactory::getSettingsCache();
        // Invalidate the setting cache
        $key = $settingCache->createKey(['key' => $setting->key]);
        $settingCache->invalidate(['key' => $key]);

        // Check to see if we already added the refresh to the app's terminating queue.
        // This is important for install commands when multiple settings are being created/updated.
        if (self::$added_refresh_artisan_caches) {
            return;
        }

        // Use app()->terminating to ensure the cache is refreshed after the settings have been saved.
        app()->terminating(function () {
            // Do this synchronously so we dont have to wait after settings have been saved.
            // This command runs pretty fast and only when an admin is updating settings.
            RefreshArtisanCaches::dispatchSync();
        });

        self::$added_refresh_artisan_caches = true;
    }
}
