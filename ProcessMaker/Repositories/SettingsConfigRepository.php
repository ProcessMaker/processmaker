<?php

namespace ProcessMaker\Repositories;

use Illuminate\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Setting;

class SettingsConfigRepository extends Repository
{
    private bool $redisAvailable = false;

    private bool $settingsTableExists = false;

    private bool $readyToUseSettingsDatabase = false;

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
        if (!$this->readyToUseSettingsDatabase()) {
            return null;
        }

        $setting = Setting::byKey($key);

        if ($setting !== null) {
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

    private function readyToUseSettingsDatabase()
    {
        if (!$this->readyToUseSettingsDatabase) {
            $this->readyToUseSettingsDatabase =
                $this->databaseAvailable() &&
                $this->redisAvailable() &&
                $this->settingsTableExists();
        }

        return $this->readyToUseSettingsDatabase;
    }

    private function databaseAvailable()
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    private function redisAvailable()
    {
        if (!$this->redisAvailable) {
            try {
                Redis::connection()->ping();
                $this->redisAvailable = true;
            } catch (\Exception $e) {
                $this->redisAvailable = false;
            }
        }

        return $this->redisAvailable;
    }

    private function settingsTableExists()
    {
        if (!$this->settingsTableExists) {
            $this->settingsTableExists = Schema::hasTable('settings');
        }

        return $this->settingsTableExists;
    }
}
