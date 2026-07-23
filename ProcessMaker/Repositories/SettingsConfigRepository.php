<?php

namespace ProcessMaker\Repositories;

use Illuminate\Config\Repository;
use Illuminate\Support\Arr;
use ProcessMaker\Models\Setting;

class SettingsConfigRepository extends Repository
{
    /**
     * Request-scoped cache for settings loaded from the database.
     * In Octane, this prevents mutation of the global config array.
     *
     * @var array
     */
    private array $settingCache = [];

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        if (Arr::has($this->items, $key)) {
            return true;
        }

        return $this->getFromSettings($key) ? true : false;
    }

    /**
     * Get the specified configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        if ($key === 'session.lifetime') {
            $settingValue = $this->getFromSettings($key);

            return $settingValue ?: Arr::get($this->items, $key) ?: $default ?: 120;
        }

        // Check if we already resolved this key in the current request
        if (Arr::has($this->settingCache, $key)) {
            return Arr::get($this->settingCache, $key);
        }

        if (Arr::has($this->items, $key)) {
            return Arr::get($this->items, $key);
        }

        return $this->getFromSettings($key) ?? $default;
    }

    /**
     * Get many configuration values.
     *
     * @param  array<string|int,mixed>  $keys
     * @return array<string,mixed>
     */
    public function getMany($keys)
    {
        $config = [];
        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            // Check request-scoped cache first
            if (Arr::has($this->settingCache, $key)) {
                $config[$key] = Arr::get($this->settingCache, $key);
            } elseif (Arr::has($this->items, $key)) {
                $config[$key] = Arr::get($this->items, $key);
            } elseif ($setting = $this->getFromSettings($key)) {
                $config[$key] = $setting;
            } else {
                $config[$key] = $default;
            }
        }

        return $config;
    }

    /**
     * Fetch a setting from the database and cache it locally.
     *
     * IMPORTANT: This no longer mutates `$this->items` (the global config array).
     * Instead, it uses a local request-scoped cache (`$this->settingCache`).
     * This prevents config leaks between requests in Octane.
     *
     * @param  string  $key
     * @return mixed
     */
    private function getFromSettings($key)
    {
        if (!Setting::readyToUseSettingsDatabase()) {
            return null;
        }

        // Return from local cache if already fetched in this request
        if (array_key_exists($key, $this->settingCache)) {
            return $this->settingCache[$key];
        }

        $setting = Setting::byKey($key);

        if ($setting !== null) {
            // Store in local cache instead of mutating global config
            $this->settingCache[$key] = $setting->config;

            return $setting->config;
        }

        // If the key is a dot notation, we can try to get the first part
        // and then use the dot notation to get the value if it's an array.
        $parts = explode('.', $key);
        if (count($parts) > 1) {
            $firstKey = array_shift($parts);
            $setting = Setting::byKey($firstKey);
            if ($setting && $setting->format === 'array') {
                $subPath = implode('.', $parts);
                $value = Arr::get($setting->config, $subPath);

                // Store in local cache
                $this->settingCache[$key] = $value;

                return $value;
            }
        }

        // Store null in local cache to avoid repeated DB queries
        $this->settingCache[$key] = null;

        return null;
    }
}
