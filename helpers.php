<?php

use Illuminate\Support\Facades\Auth;
use Laravel\Horizon\Repositories\RedisJobRepository;
use ProcessMaker\Events\MarkArtisanCachesAsInvalid;
use ProcessMaker\SanitizeHelper;

if (!function_exists('job_pending')) {
    /**
     * Check if a given job (by instance or class) is already pending in the queue.
     *
     * @param $job
     *
     * @return bool
     */
    function job_pending($job): bool
    {
        if (is_object($job)) {
            $job = class_basename($job);
        }

        $queue = app(RedisJobRepository::class)->getRecent();

        return $queue->filter(
            function ($queued) use ($job) {
                return $queued->name === $job
                    && ('delayed' === $queued->status
                    || 'pending' === $queued->status);
            }
        )->isNotEmpty();
    }
}

if (!function_exists('settings')) {
    /**
     * Forwards call to the config() helper.
     *
     * TODO Remove this helper function as it exists now only for backwards compatability
     *
     * @param $key
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     * @return array|mixed
     */
    function settings($key = null)
    {
        return $key ? config()->get($key) : config()->all();
    }
}

if (!function_exists('default_color')) {
    /**
     * Returns the default color value.
     *
     * @param $key
     *
     * @return string
     */
    function default_color($key)
    {
        return config('app.default_colors.' . $key);
    }
}

if (!function_exists('color')) {
    /**
     * Returns the color value from the settings.
     *
     * @param $key
     *
     * @return string
     */
    function color($key)
    {
        if ($colors = config('css-override.variables')) {
            $colors = json_decode($colors);
            foreach ($colors as $color) {
                if ($color->id === '$' . $key) {
                    return $color->value;
                }
            }
        }
        
        return default_color($key);
    }
}

if (!function_exists('sidebar_class')) {
    /**
     * Returns the class for the sidebar based on admin settings.
     *
     * @return boolean
     */
    function sidebar_class()
    {
        if (config('css-override.variables')) {
            $defaults = ['#0872C2', '#2773F3'];
            if (! in_array(color('primary'), $defaults)) {
                return 'sidebar-custom';
            }
        }
        
        return 'sidebar-default';
    }
}

if (!function_exists('refresh_artisan_caches')) {
    /**
     * Re-caches artisan config, routes, and/or events when they are already cached.
     */
    function refresh_artisan_caches(): void
    {
        MarkArtisanCachesAsInvalid::dispatch();
    }
}

if (!function_exists('lavaryMenuArray')) {
    /**
     * Convert the Laravy menu into associative array.
     *
     * @param \Lavary\Menu\Item $menu
     * @param mixed             $includeSubMenus
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
     * Convert the Laravy menu into json string.
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
     * Check if a package exists based on its provider name.
     *
     * @param $name
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return bool
     */
    function hasPackage($name)
    {
        $list = app()->make(ProcessMaker\Managers\PackageManager::class)->listPackages();

        return in_array($name, $list);
    }
}

if (!function_exists('pmUser')) {
    /**
     * Check both the web and api middleware for an existing user.
     *
     * @return null|\ProcessMaker\Models\User
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
     * Store data into store/app/private.
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
     */
    function removeTemporalData($uid)
    {
        $path = storage_path('app/private/' . $uid);

        if (file_exists($path)) {
            @unlink($path);
        }
    }
}

if (!function_exists('shouldShow')) {
    /**
     * @param string $element
     *
     * @return bool
     */
    function shouldShow($element)
    {
        if (Session::has('visibilitySettings')) {
            return Session::get('visibilitySettings')->{$element} ?? true;
        } else {
            return true;
        }
    }
}
