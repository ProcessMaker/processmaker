<?php

namespace ProcessMaker\Repositories;

use Illuminate\Config\Repository;
use Illuminate\Support\Arr;
use ProcessMaker\Models\Setting;

class SettingsConfigRepository extends Repository
{
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

            return $settingValue ?? $default;
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

            if (Arr::has($this->items, $key)) {
                $config[$key] = Arr::get($this->items, $key);
            } elseif ($setting = $this->getFromSettings($key)) {
                $config[$key] = $setting;
            } else {
                $config[$key] = $default;
            }
        }

        return $config;
    }

    private function getFromSettings($key)
    {
        if (!Setting::readyToUseSettingsDatabase()) {
            return null;
        }

        $setting = Setting::byKey($key);

        if ($setting !== null) {
            Arr::set($this->items, $key, $setting->config);

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

                return Arr::get($setting->config, $subPath);
            }
        }

        return null;
    }
}
