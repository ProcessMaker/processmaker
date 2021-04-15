<?php

use Illuminate\Support\Arr;
use ProcessMaker\Models\Setting;

if (! function_exists('settings')) {

    function settings($key = null)
    {
        if ($key) {
            if (config()->has($key)) {
                return config($key);
            } else {
                $nested = [];
                
                try {
                    $settings = Setting::get();
                } catch (\Exception $e) {
                    return null;
                }
                
                foreach ($settings as $setting) {
                    Arr::set($nested, $setting->key, $setting->config);
                }
                
                return Arr::get($nested, $key);
            }
        } else {
            $nested = [];
            
            try {
                $settings = Setting::get();
            } catch (\Exception $e) {
                return null;
            }
            
            foreach ($settings as $setting) {
                Arr::set($nested, $setting->key, $setting->config);
            }
            
            return $nested;
        }
    }
}
