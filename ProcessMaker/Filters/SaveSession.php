<?php

namespace ProcessMaker\Filters;

use Illuminate\Support\Facades\Cache;

class SaveSession 
{
    /**
     * Get filter configuration.
     * 
     * @param String $name
     * @param User $user
     * @return type
     */
    public static function getFilterConfiguration(String $name, Object $user) 
    {
        $key = self::getKey($user, $name);

        return self::get($key, []);
    }

    /**
     * Store filter configuration.
     * 
     * @param String $name
     * @param User $user
     * @param Array $array
     * @return type
     */
    public static function storeFilterConfiguration(String $name, Object $user, Array $array) 
    {
        $key = self::getKey($user, $name);
        Cache::pull($key);

        return self::get($key, $array);
    }
    
    /**
     * Retrieve cached data; this is preserved for a week.
     * 
     * @param Array $json
     * @return Array
     */
    private static function get($key, $json) 
    {
        $valueInCache = Cache::remember($key, now()->addWeek(), function () use($json) {
            return $json;
        });
        return $valueInCache;
    }

    /**
     * Get key cache remember.
     * 
     * @param User $user
     * @param string $name
     * @return string
     */
    private static function getKey($user, $name)
    {
        $key = str_replace("-", "_", "user-{$user->id}-{$user->uuid}-{$name}");
        return $key;
    }
}