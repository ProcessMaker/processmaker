<?php

use ProcessMaker\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

if (!function_exists('settings')) {
    /**
     * Get all settings or get a setting value by key
     *
     * @param  string|null  $key
     *
     * @return mixed
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
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

        // Cache each Setting
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
        if ($config = app()->configurationIsCached()) {
            Artisan::call('config:clear');
        }

        if ($routes = app()->routesAreCached()) {
            Artisan::call('route:clear');
        }

        if ($events = app()->eventsAreCached()) {
            Artisan::call('event:clear');
        }

        return [
            'configuration' => $config,
            'routes' => $routes,
            'events' => $events,
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
        if ($caches['configuration'] ?? app()->configurationIsCached()) {
            Artisan::call('config:cache');
        }

        if ($caches['routes'] ?? app()->routesAreCached()) {
            Artisan::call('route:cache');
        }

        if ($caches['events'] ?? app()->eventsAreCached()) {
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
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    function cache_settings(bool $force = false): bool
    {
        try {
            if (Setting::exists()) {
                // Unload this this value if it's been set
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

                // Re-cache all of the existing Settings
                $settings = settings();

                // Grab the cached settings tag
                $cache = cache()->tags('setting');

                // Sets an app config key/value for each Setting
                ($cache->get('all') ?? collect())->each(function (Setting $setting) {
                    $setting->addToConfig();
                });

                // Set the config option to indicate the settings
                // are now available via the configuration
                config(['settings_loaded' => true]);

                if ($force) {
                    // Refresh any cached routes, config, or events
                    refresh_artisan_caches($cleared_caches ?? []);
                }
            }
        } catch (Exception $exception) {
            Log::error('Unable to load settings from database', [
                'message' => $exception->getMessage(),
                'stacktrace' => $exception->getTrace(),
            ]);

            return false;
        }

        return true;
    }
}
