<?php
namespace ProcessMaker\Config;

use Illuminate\Support\Arr;
use Illuminate\Config\Repository as BaseRepository;
use Illuminate\Support\Facades\Cache;

/**
 * A Configuration Repository which extends the base Laravel Config Repository
 * to persist any configuration added via set to the database and cache.
 * The priority when fetching is always given to the underlying repository,
 * utilizing cache second, then database last.
 */
class Repository extends BaseRepository
{
    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return Arr::has($this->items, $key) ? true : Cache::has('config:' . $key);
    }

    /**
     * Get the specified configuration value.
     *
     * @param  array|string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $value =  parent::get($key, $default);
        if ($value === null) {
            // Return from Cache
            $value = Cache::get('config:' . $key, $default);
            if ($value !== null) {
                return $value;
            }
            return $default;
        }
        // It was not null, therefore let's return the value
        return $value;
    }

    /**
     * Get many configuration values.
     *
     * @param  array  $keys
     * @return array
     */
    public function getMany($keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                list($key, $default) = [$default, null];
            }

            $value = Arr::get($this->items, $key, $default);
            if ($value === null) {
                // Return from Cache
                $value = Cache::get('config:' . $key, $default);
                if ($value !== null) {
                    $config[$key] = $value;
                }
                $config[$key] = $default;
            }
            // It was not null, therefore let's return the value
            $config[$key] = $value;
        }
        return $config;
    }

    /**
     * Set a given configuration value.
     *
     * @param  array|string  $key
     * @param  mixed   $value
     * @return void
     */
    public function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set($this->items, $key, $value);
            // Also set it in cache
            Cache::forever('config:' . $key, $value);
        }
    }

}
