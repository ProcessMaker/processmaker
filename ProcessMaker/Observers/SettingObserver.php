<?php

namespace ProcessMaker\Observers;

use Exception;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Cache\Settings\SettingCacheFactory;
use ProcessMaker\Models\Setting;

class SettingObserver
{
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

        $settingCache = SettingCacheFactory::getSettingsCache();
        // Invalidate the setting cache
        $key = $settingCache->createKey(['key' => $setting->key]);
        $settingCache->invalidate(['key' => $key]);
    }

    /**
     * Handle the setting "deleted" event.
     *
     * @param  Setting  $setting
     * @return void
     */
    public function deleted(Setting $setting): void
    {
        $settingCache = SettingCacheFactory::getSettingsCache();
        // Invalidate the setting cache
        $key = $settingCache->createKey(['key' => $setting->key]);
        $settingCache->invalidate(['key' => $key]);
    }

    /**
     * Handle the setting "updated" event.
     *
     * @param  Setting  $setting
     * @return void
     */
    public function updated(Setting $setting): void
    {
        $this->updateConfigurationCache($setting);
    }

    /**
     * Updates the configuration file with the new value of the setting and then cache the updated configuration.
     *
     * @param Setting setting
     *
     * @return void
     */
    private function updateConfigurationCache(Setting $setting): void
    {
        if (app()->configurationIsCached() && $setting->config !== config([$setting->key])) {
            config([$setting->key => $setting->config]);

            \Artisan::call('config:cache');
        }
    }
}
