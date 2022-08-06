<?php

use ProcessMaker\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

if (!function_exists('settings')) {
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
        if (!$cache->has('all')) {
            if (!Schema::hasTable('settings')) {
                return [];
            }

            $cache->put('all', Setting::get(), 60 * 60 * 24 * 7);
        }

        $nested = [];

        foreach ($cache->get('all') ?? [] as $setting) {
            Arr::set($nested, $setting->key, $setting->config);
        }

        // No key present means we should return all
        // cached settings
        if (!$key) {
            return $nested;
        }

        return Arr::get($nested, $key);
    }
}

if (!function_exists('flush_settings')) {
    /**
     * Flush the ProcessMaker settings from the cache and global configuration.
     *
     * @return bool
     * @throws \Exception
     */
    function flush_settings(): bool
    {
        return cache()->tags('setting')->flush();
    }
}

if (!function_exists('clear_artisan_caches')) {
    /**
     * Clears any available/cacheable artisan commands and
     * returns with which were cached to before clearing
     *
     * @return array
     */
    function clear_artisan_caches(): array
    {
        if ($configuration = app()->configurationIsCached()) {
            Artisan::call('config:clear');
        }

        if ($routes = app()->routesAreCached()) {
            Artisan::call('route:clear');
        }

        if ($events = app()->eventsAreCached()) {
            Artisan::call('event:clear');
        }

        return [
            'configuration' => $configuration ?? false,
            'routes' => $routes ?? false,
            'events' => $events ?? false,
        ];
    }
}

if (!function_exists('refresh_artisan_caches')) {
    /**
     * Refreshes identified caches (configuration, routes, and/or events)
     *
     * @param  array  $caches
     *
     * @return void
     */
    function refresh_artisan_caches(array $caches = []): void
    {
        if (!array_key_exists('configuration', $caches)) {
            $caches['configuration'] = app()->configurationIsCached();
        }

        if (!array_key_exists('routes', $caches)) {
            $caches['routes'] = app()->routesAreCached();
        }

        if (!array_key_exists('events', $caches)) {
            $caches['events'] = app()->eventsAreCached();
        }

        if ($caches['configuration']) {
            Artisan::call('config:cache');
        }

        if ($caches['routes']) {
            Artisan::call('route:cache');
        }

        if ($caches['events']) {
            Artisan::call('event:cache');
        }
    }
}

if (!function_exists('cache_settings')) {
    /**
     * Cache all ProcessMaker settings
     *
     * @param  bool  $force
     *
     * @return bool
     */
    function cache_settings(bool $force = false): bool
    {
        try {
            if (Setting::exists()) {
                $cache = cache()->tags('setting');

                if (config()->has('settings_loaded')) {
                    config()->set('settings_loaded', false);
                }

                if ($force) {
                    // Flush the settings cache
                    flush_settings();

                    // Clear any cached events, routes,
                    // and/or the configuration itself
                    $cleared_caches = clear_artisan_caches();
                }

                // Calling the settings() helper function will
                // automatically cache all Setting models that
                // are available if they aren't cached yet
                if (!$cache->has('all')) {
                    $settings = settings();
                    $cache = cache()->tags('setting');
                }

                if ($force) {
                    // Refresh any cached routes, config, or events
                    refresh_artisan_caches($cleared_caches ?? []);
                }

                // Iterating through each and calling the byKey()
                // static method will also cache the config value
                // automatically, making them available through
                // the global config() helper function.
                ($cache->get('all') ?? collect())->each(function (Setting $setting) {
                    $setting->addToConfig();
                });

                // Set the config option to indicate the settings
                // are now available via the configuration
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
