<?php

namespace ProcessMaker\Filters;

use Illuminate\Support\Facades\Cache;

class SaveSession
{
    /**
     * Retrieve cached data; this is preserved for a week.
     * @param array $json
     * @return array
     */
    private static function get($key, $json)
    {
        return Cache::remember($key, now()->addWeek(), function () use ($json) {
            return $json;
        });
    }

    /**
     * Get key cache remember
     * @param User $user
     * @param string $name
     * @return string
     */
    private static function getKey($user, $name)
    {
        return str_replace('-', '_', "user-{$user->id}-{$user->uuid}-{$name}");
    }

    /**
     * Get filter configuration.
     * @param string $name
     * @param User $user
     * @return type
     */
    public static function getConfigFilter(String $name, Object $user)
    {
        $key = self::getKey($user, $name);

        $default = ['filters' => []];

        return self::get($key, $default);
    }

    /**
     * Store filter configuration.
     * @param string $name
     * @param User $user
     * @param array $array
     * @return type
     */
    public static function setConfigFilter(String $name, Object $user, array $array)
    {
        $key = self::getKey($user, $name);
        Cache::pull($key);
        $array = self::clearInvalidFilters($array);

        return self::get($key, $array);
    }

    private static function clearInvalidFilters($array)
    {
        if (!array_key_exists('filters', $array)) {
            return $array;
        }

        $cleanedFilters = [];
        foreach ($array['filters'] as $filter) {
            if (
                array_key_exists('_column_field', $filter) &&
                $filter['_column_field'] !== 'undefined' &&
                $filter['_column_field'] !== '' &&
                $filter['_column_field'] !== null
            ) {
                $cleanedFilters[] = $filter;
            }
        }
        $array['filters'] = $cleanedFilters;

        return $array;
    }
}
