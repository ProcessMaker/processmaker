<?php

namespace ProcessMaker\Traits;

use ProcessMaker\Managers\ControllerAddonsRegistry;

trait HasControllerAddons
{
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
        return app(ControllerAddonsRegistry::class)->getAddons(static::class, $method, $data);
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
        app(ControllerAddonsRegistry::class)->register(static::class, $config);
    }
}
