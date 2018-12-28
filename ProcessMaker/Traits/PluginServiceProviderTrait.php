<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Facades\Cache;
use ProcessMaker\Events\ModelerStarting;
use Illuminate\Support\Facades\Event;

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
        if (!$this->isUpdated()) {
            $this->updateVersion();
            $key = str_replace('\\', '_', static::class);
            \Illuminate\Support\Facades\Log::info(static::class . ' updated');
            Cache::forever($key, static::version);
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
            $event->manager->addScript(mix($path, $public));
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
    abstract protected function updateVersion();

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
}
