<?php
namespace ProcessMaker\Config;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Config\Repository as BaseRepository;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Models\Configuration;

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
        // First check our local array
        if (Arr::has($this->items, $key)) {
            return true;
        }
        // Now check cache
        // First, get the first "part" of the key
        $index = explode('.', $key)[0];
        $data = Cache::get('config:' . $index, null);
        $data = $data ? json_decode($data, true) : [];
        if (Arr::has($data, $key)) {
            return true;
        }
        // Now check database
        try {
            $record = Configuration::where('parameter', $index)->first();
            if ($record) {
                $data = json_decode($record->value, true);
                return(Arr::has($data, $key));
            }
        } catch(Exception $e) {
            // If this exception is thrown, then the database is not available
            return false;
        }
        // Default, return false
        return false;
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
            // It wasn't found, so let's attempt from cache
            $index = explode('.', $key)[0];
            $data = Cache::get('config:' . $index, null);
            $data = $data ? json_decode($data, true) : [];
            $value = Arr::get($data, $key);
            if ($value === null) {
                // It wasn't found, so let's try the database
                // Now check database
                $record = Configuration::where('parameter', $index)->first();
                if ($record) {
                    $data = json_decode($record->value, true);
                    $value = Arr::get($data, $key);
                    if ($value !== null) {
                        // It was found in the database, let's return
                        return $value;
                    } else {
                        // Not found in the section record in database, return null
                        return null;
                    }
                }
                // No matching record in database
                return null;
            } else {
                // Found in cache, let's return it
                return $value;
            }
        } else {
            // Found in our local memory, let's return
            return $value;
        }
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
            $config[$key] = $this->get($key, $default);
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
            
            // First, get the first "part" of the key
            $index = explode('.', $key)[0];


            // Now fetch from database, store it, and then update our cache
            try {
                $record = Configuration::where('parameter', $index)->first();
            } catch(Exception $e) {
                // Database not available, so just continue
                continue;
            }
            // Data represents the updated array with configuration values
            $data = [];
            if ($record) {
                // Update it
                $data = json_decode($record->value, true);
                Arr::set($data, $key, $value);
                $record->value = json_encode($data);
                $record->save();
            } else {
                // Create record in database
                $data = [];
                Arr::set($data, $key, $value);
                Configuration::create([
                    'parameter' => $index,
                    'value' => json_encode($data)
                ]);
            }
            // Now set it in our cache
            Cache::forever('config:' . $index, json_encode($data));
        }
    }

    /**
     * Removes a configuration value from in-memory as well as persistence
     * @param  array|string  $key
     */
    public function forget($key)
    {
        Arr::forget($this->items, $key);

        $index = explode('.', $key)[0];


        $data = [];
        // Update database
        // Update database
        $record = Configuration::where('parameter', $index)->first();
        if ($record) {
            // Only do it if we have a value
            $data = json_decode($record->value, true);
            Arr::forget($data, $key);
            if ($data[$index]) {
                // There's still something in data, so update the record
                $record->value = json_encode($data);
                $record->save();
                // Update cache
                Cache::forever('config:' . $index, json_encode($data));
            } else {
                // The data is empty, so let's delete the record
                $record->delete();
                Cache::forget('config:' . $index);
            }
        } else {
            // Do nothing to database. Be sure to clear cache
            Cache::forget('config:' . $index);
        }
    }
}
