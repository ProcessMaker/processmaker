<?php

namespace ProcessMaker\Observers;

use Exception;
use ProcessMaker\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

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
    }

    public function saved(Setting $setting)
    {
        try {
            refresh_artisan_caches(clear_artisan_caches());
        } catch (Exception $exception) {
            Log::error('Could not cache configuration.', [
                'message' => $exception->getMessage(),
                'file' =>$exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }
    }

    /**
     * Handle the setting "deleted" event.
     *
     * @param  \ProcessMaker\Models\Setting  $setting
     * @return void
     */
    public function deleted(Setting $setting)
    {
        try {
            refresh_artisan_caches(clear_artisan_caches());
        } catch (Exception $exception) {
            Log::error('Could not cache configuration.', [
                'message' => $exception->getMessage(),
                'file' =>$exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }
    }
}
