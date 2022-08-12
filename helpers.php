<?php

use ProcessMaker\SanitizeHelper;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

if (!function_exists('settings')) {
    /**
     * Forwards call to the config() helper.
     *
     * TODO Remove this helper function as it exists now only for backwards compatability
     *
     * @param $key
     *
     * @return array|mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    function settings($key = null)
    {
        return $key ? config()->get($key) : config()->all();
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
        Artisan::call('clear-compiled', $options = [
            '--no-interaction' => true,
            '--quiet' => true,
            '--env' => app()->environment(),
        ]);

        if ($caches['configuration'] ?? app()->configurationIsCached()) {
            Artisan::call('config:cache', $options);
        }

        if ($caches['routes'] ?? app()->routesAreCached()) {
            Artisan::call('route:cache', $options);
        }

        if ($caches['events'] ?? app()->eventsAreCached()) {
            Artisan::call('event:cache', $options);
        }
    }
}

if (!function_exists('lavaryMenuArray')) {
    /**
     * Convert the Laravy menu into associative array
     *
     * @param \Lavary\Menu\Item $menu
     *
     * @return array
     */
    function lavaryMenuArray($menu, $includeSubMenus = false)
    {
        $children = [];
        $subMenus = $includeSubMenus ? $menu->children() : null;

        if ($subMenus) {
            foreach ($subMenus as $child) {
                $children[] = lavaryMenuArray($child);
            }
        }

        return [
            'title' => __($menu->title),
            'id' => $menu->id,
            'attributes' => $menu->attributes,
            'url' => $menu->url(),
            'children' => $children,
        ];
    }
}

if (!function_exists('lavaryMenuJson')) {
    /**
     * Convert the Laravy menu into json string
     *
     * @param \Lavary\Menu\Item $menu
     *
     * @return false|string
     */
    function lavaryMenuJson($menu)
    {
        return json_encode(lavaryMenuArray($menu, true));
    }
}

if (!function_exists('hasPackage')) {
    /**
     * Check if a package exists based on its provider name
     *
     * @param $name
     *
     * @return bool
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    function hasPackage($name)
    {
        $list = app()->make(ProcessMaker\Managers\PackageManager::class)->listPackages();

        return in_array($name, $list);
    }
}

if (!function_exists('')) {
    /**
     * Check both the web and api middleware for an existing user
     *
     * @return \ProcessMaker\Models\User|null
     */
    function pmUser()
    {
        if (Auth::user()) {
            return Auth::user();
        }

        if (Auth::guard('api')->user()) {
            return Auth::guard('api')->user();
        }

        return null;
    }
}

if (!function_exists('sanitizeVueExp')) {
    /**
     * @param $expression
     *
     * @return array|string|string[]
     */
    function sanitizeVueExp($expression)
    {
        return SanitizeHelper::sanitizeVueExp($expression);
    }
}

if (!function_exists('packTemporalData')) {
    /**
     * Store data into store/app/private
     *
     * @param $data
     *
     * @return string
     */
    function packTemporalData($data)
    {
        $uid = uniqid('data_', true);
        $path = storage_path('app/private/' . $uid);

        file_put_contents($path, json_encode($data));

        return $uid;
    }

}

if (!function_exists('unpackTemporalData')) {
    /**
     * @param $uid
     *
     * @return array|mixed
     */
    function unpackTemporalData($uid)
    {
        $path = storage_path('app/private/' . $uid);

        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }

        return [];
    }
}

if (!function_exists('removeTemporalData')) {
    /**
     * @param $uid
     *
     * @return void
     */
    function removeTemporalData($uid)
    {
        $path = storage_path('app/private/' . $uid);

        if (file_exists($path)) {
            unlink($path);
        }
    }
}
