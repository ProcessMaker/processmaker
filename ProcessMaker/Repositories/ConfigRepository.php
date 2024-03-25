<?php

namespace ProcessMaker\Repositories;

use ArrayAccess;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\Setting;
use Throwable;

class ConfigRepository implements ArrayAccess, ConfigContract
{
    /**
     * All of the configuration items.
     *
     * @var array
     */
    protected $items = [];

    protected $resolver;

    /**
     * Create a new configuration repository.
     *
     * @param  array  $items
     * @return void
     */
    public function __construct(ConfigContract $originConfig)
    {
        $this->items = $originConfig->all();
    }

    public function setConnectionResolver(Resolver $resolver): self
    {
        $this->resolver = $resolver;

        return $this;
    }

    /**
     * Load all setting values into the configuration
     *
     * @return void
     */
    public function load(bool $force = false): self
    {
        if (!$force && app()->configurationIsCached()) {
            return $this;
        }

        // Make sure we have a database connection
        if (Setting::getConnectionResolver() === null) {
            Setting::setConnectionResolver($this->resolver);
        }

        // Query only what we need from the database and
        // bind the key/config value to the global
        // app config for each
        try {
            foreach (Setting::select('id', 'key', 'config', 'format')->get() as $setting) {
                $this->set($setting->key, $setting->config);
            }
        } catch (Throwable $exception) {
            Log::error($exception->getMessage());
            // Catch an exception being thrown when no
            // database connection is available
        }

        return $this;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return Arr::has($this->items, $key);
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

        return Arr::get($this->items, $key, $default);
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
                [$key, $default] = [$default, null];
            }

            $config[$key] = Arr::get($this->items, $key, $default);
        }

        return $config;
    }

    /**
     * Set a given configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set($this->items, $key, $value);
        }
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function prepend($key, $value)
    {
        $array = $this->get($key);

        array_unshift($array, $value);

        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value)
    {
        $array = $this->get($key);

        $array[] = $value;

        $this->set($key, $array);
    }

    /**
     * Get all of the configuration items for the application.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get a configuration option.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Unset a configuration option.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->set($key, null);
    }
}
