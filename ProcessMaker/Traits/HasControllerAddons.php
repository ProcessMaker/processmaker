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
        $addons = [];
        foreach(static::$addons as $addon) {
            if($addon['method'] === $method) {
                if ($addon['data'] && is_callable($addon['data'])) {
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
        static::$addons[] = $config;
    }
}
