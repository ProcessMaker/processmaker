<?php

namespace ProcessMaker\Repositories;

use Illuminate\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis;
use ProcessMaker\Models\Setting;

class SettingsConfigRepository extends Repository
{
    // private bool $hasDatabaseConnection = false;

    private bool $applicationBooted = false;

    private bool $redisAvailable = false;

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

        $setting = $this->getFromSettings($key);
        if ($setting) {
            return $setting->config;
        }

        return $default;
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
                $config[$key] = $setting->config;
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

        return Setting::byKey($key);
    }

    private function readyToUseSettingsDatabase()
    {
        return $this->applicationBooted() && $this->redisAvailable();
    }

    private function applicationBooted()
    {
        return $this->applicationBooted;
    }

    public function setApplicationBooted()
    {
        return $this->applicationBooted = true;
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
}
