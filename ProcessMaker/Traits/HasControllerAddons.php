<?php

namespace ProcessMaker\Traits;

trait HasControllerAddons
{
    private static $addons = [];

    /**
     * Get configured addons for this controller
     *
     * @param string $method filter to identify the type of addon we are interested on
     * @param array $data data that the controller will pass to the addon views
     *
     * @return array
     */
    protected function getPluginAddons($method, array $data)
    {
        if (!isset(static::$addons)) {
            return;
        }

        $addons = [];
        foreach (static::$addons as $addon) {
            // The addon must have the requested method and must be associated to the current controller
            if ($addon['method'] === $method && $addon['scope'] === get_class($this)) {
                if (isset($addon['data']) && is_callable($addon['data'])) {
                    $data = call_user_func($addon['data'], $data);
                }
                $addon['content'] = isset($addon['view']) && !isset($addon['content'])
                    ? view($addon['view'], $data)->render() : (isset($addon['content'])
                    ? $addon['content'] : '');
                $addon['script'] = isset($addon['script']) ? view($addon['script'], $data)->render() : '';
                $addons[] = $addon;
            }
        }

        return $addons;
    }

    /**
     * Register a controller addon
     *
     * @param array $config
     *
     * @return void
     */
    public static function registerAddon(array $config)
    {
        // Add the controller to which the addon is attached
        $config['scope'] = static::class;
        static::$addons[] = $config;
    }
}
