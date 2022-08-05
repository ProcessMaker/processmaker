<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\Setting;

if (! function_exists('settings')) {
    /**
     * @param  string|null  $key
     *
     * @return array|null
     * @throws \Exception
     */
    function settings(string $key = null)
    {
        // Check the global config() to see
        // if it contains the Setting value
        if ($key) {
            if (config()->has($key)) {
                return config()->get($key);
            }
        }

        $cache = cache()->tags('setting');

        // Cache all Setting models
        if (! $cache->has('all')) {
            $cache->put('all', Setting::get(), 60 * 60 * 24 * 7);
        }

        $nested = [];

        foreach ($cache->get('all') as $setting) {
            Arr::set($nested, $setting->key, $setting->config);
        }

        // No key present means we should return all
        // cached settings
        if (! $key) {
            return $nested;
        }

        return Arr::get($nested, $key);
    }
}

if (! function_exists('flush_settings')) {
    /**
     * Flush the ProcessMaker settings from the cache and global configuration.
     *
     * @return void
     * @throws \Exception
     */
    function flush_settings()
    {
        cache()->tags('setting')->flush();

        if (app()->configurationIsCached()) {
            Artisan::call('config:cache');
        }

        if (app()->routesAreCached()) {
            Artisan::call('route:cache');
        }

        if (app()->eventsAreCached()) {
            Artisan::call('event:cache');
        }
    }
}

if (! function_exists('cache_settings')) {
    /**
     * Cache all ProcessMaker settings
     *
     * @param  bool  $force
     *
     * @return bool
     */
    function cache_settings(bool $force = false)
    {
        try {
            if ((new Setting())->exists()) {
                $cache = cache()->tags('setting');

                // If $force is true, flush the settings cache
                if ($force) {
                    flush_settings();
                }

                // Calling the settings() helper function will
                // automatically cache all Setting models that
                // are available if they aren't cached yet
                if (! $cache->has('all')) {
                    $settings = settings();
                    $cache = cache()->tags('setting');
                }

                // Iterating through each and calling the byKey()
                // static method will also cache the config value
                // automatically, making them available through
                // the global config() helper function.
                $cache->get('all')->each(function (Setting $setting) {
                    $setting->addToConfig();
                });

                config(['settings_loaded' => true]);
            }
        } catch (Exception $e) {
            Log::error('Unable to load settings from database', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
