<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use ProcessMaker\Events\ModelerStarting;
use Illuminate\Support\Facades\Event;
use ProcessMaker\Managers\PackageManager;

/**
 * Add functionality to control a PM plug-in
 *
 */
trait PluginServiceProviderTrait
{

    private $modelerScripts = [];

    /**
     * Boot the PM plug-in.
     */
    protected function completePluginBoot()
    {
        if (defined("static::version") && !$this->isUpdated()) {
            $this->updateVersion();
            $key = str_replace('\\', '_', static::class);
            Cache::forever($key, static::version);
        }
        if (defined("static::name")) {
            $this->registerPackage(static::name);
        }
        Event::listen(ModelerStarting::class, [$this, 'modelerStarting']);
    }

    /**
     * Executed during modeler starting
     *
     * @param \ProcessMaker\Events\ModelerStarting $event
     */
    public function modelerStarting(ModelerStarting $event)
    {
        foreach ($this->modelerScripts as $path => $public) {
            if (File::exists(public_path() . '/' . $public)) {
                $event->manager->addScript(mix($path, $public));
            }
        }
    }

    /**
     * Register a custom javascript for the modeler
     *
     * @param string $path
     * @param string $public
     */
    protected function registerModelerScript($path, $public)
    {
        $this->modelerScripts[$path] = $public;
    }

    /**
     * Executed once when the plug-in version was changed.
     *
     */
    protected function updateVersion()
    {

    }

    /**
     * Check if the plug-in was updated
     */
    protected function isUpdated()
    {
        $key = str_replace('\\', '_', static::class);
        return static::version === Cache::get($key, '0.0.0');
    }

    /**
     * Register a seeder that should be executed when php artisan db:seed is called.
     *
     * @param string $seederClass
     */
    protected function registerSeeder($seederClass)
    {
        LoadPluginSeeders::registerSeeder($seederClass);
    }

    /**
     * Register package installed
     *
     * @param $package
     */
    protected function registerPackage($package)
    {
        App::make(PackageManager::class)->addPackage($package);
    }

    /**
     * Verify package is in register installed
     *
     * @param $name
     * @return bool
     */
    public function isRegisteredPackage($package)
    {
        return App::make(PackageManager::class)->isRegistered($package);
    }

    /**
     * Remove package of list installed
     *
     * @param $name
     */
    public function removePackage($package)
    {
        App::make(PackageManager::class)->remove($package);
    }
}
