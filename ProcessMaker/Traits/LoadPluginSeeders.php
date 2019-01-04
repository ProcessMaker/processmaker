<?php

namespace ProcessMaker\Traits;

/**
 * Include the seeders of the registered plugins.
 *
 */
trait LoadPluginSeeders
{

    private static $pluginSeeders = [];

    /**
     * Register a seeder that should be executed when callPluginSeeders()
     *
     * @param string $seederClass
     */
    public static function registerSeeder($seederClass)
    {
        self::$pluginSeeders[] = $seederClass;
    }

    /**
     * Execute the registered seeders.
     *
     */
    protected function callPluginSeeders()
    {
        foreach (self::$pluginSeeders as $class) {
            $this->call($class);
        }
    }

    /**
     * Get the array of registered seeders.
     *
     * @return array
     */
    protected function getPluginSeeders()
    {
        return self::$pluginSeeders;
    }
}
