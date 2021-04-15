<?php

namespace ProcessMaker\Observers;

use Artisan;
use ProcessMaker\Models\Setting;

class SettingObserver
{
    /**
     * Handle the setting "created" event.
     *
     * @param  \ProcessMaker\Models\Setting  $setting
     * @return void
     */
    public function saving(Setting $setting)
    {
        $config = $setting->getAttributes()['config'];

        if ($config === "null" || $config === null) {
            $setting->config = null;
            return;
        }

        switch ($setting->format) {
            case 'text':
            case 'textarea':
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
                    } catch (\Exception $e) {
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
                    } catch (\Exception $e) {
                        $return = $config;
                    }
                } else {
                    $return = json_encode($config);
                }

                $setting->config = $return;
                break;
        }

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

    /**
     * Handle the setting "updated" event.
     *
     * @param  \ProcessMaker\Models\Setting  $setting
     * @return void
     */
    public function updated(Setting $setting)
    {
        Artisan::call('config:cache');
    }

    /**
     * Handle the setting "deleted" event.
     *
     * @param  \ProcessMaker\Models\Setting  $setting
     * @return void
     */
    public function deleted(Setting $setting)
    {
        Artisan::call('config:cache');
    }
}
