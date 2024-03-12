<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use ProcessMaker\Events\ModelerStarting;
use ProcessMaker\Events\ScriptBuilderStarting;
use ProcessMaker\Managers\IndexManager;
use ProcessMaker\Managers\LoginManager;
use ProcessMaker\Managers\PackageManager;

/**
 * Add functionality to control a PM plug-in
 */
trait PluginServiceProviderTrait
{
    private $modelerScripts = [];

    private $scriptBuilderScripts = [];

    /**
     * Boot the PM plug-in.
     */
    protected function completePluginBoot()
    {
        if (defined('static::name')) {
            $this->registerPackage(static::name);
        }

        Event::listen(ModelerStarting::class, [$this, 'modelerStarting']);
        Event::listen(ScriptBuilderStarting::class, [$this, 'scriptBuilderStarting']);
    }

    /**
     * Executed during modeler starting
     *
     * @param  \ProcessMaker\Events\ModelerStarting  $event
     *
     * @throws \Exception
     */
    public function modelerStarting(ModelerStarting $event)
    {
        foreach ($this->modelerScripts as $path => $config) {
            if (File::exists(public_path() . '/' . $config['script_src'])) {
                $event->manager->addScript(mix($path, $config['script_src']), $config['script_params']);
            }
        }
    }

    /**
     * Register a custom script for the modeler with optional parameters.
     *
     * This method registers a script for the modeler along with optional parameters.
     * The script path and its corresponding public URL are added to the modeler scripts registry,
     * and if additional parameters are provided, they are included in the registration.
     *
     * @param string $path The path to the script file.
     * @param string $public The public URL of the script.
     * @param array $params Additional parameters for configuring the script (optional).
     * @return void
     */
    protected function registerModelerScript($path, $public, array $params = [])
    {
        // Register the script path and public URL along with optional parameters
        $this->modelerScripts[$path] = [
            'script_src' => $public,
            'script_params' => $params,
        ];
    }

    /**
     * Executed once when the plug-in version was changed.
     */
    protected function updateVersion()
    {
        //
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
     * Register index
     *
     * @param $name string name of index
     * @param $model string path of model
     * @param $callback callback function to perform indexing
     */
    protected function registerIndex($name, $model, $callback)
    {
        App::make(IndexManager::class)->add($name, $model, $callback);
    }

    /**
     * Register login addon
     *
     * @param $name string name of view file
     * @param $data array data to pass to the view
     */
    protected function registerLoginAddon($view, $data = [])
    {
        App::make(LoginManager::class)->add($view, $data);
    }

    /**
     * Do not display standard login form
     */
    protected function blockStandardLogin()
    {
        App::make(LoginManager::class)->block();
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

    /**
     * Executed during script builder starting
     *
     * @param  \ProcessMaker\Events\ScriptBuilderStarting  $event
     *
     * @throws \Exception
     */
    public function scriptBuilderStarting(ScriptBuilderStarting $event)
    {
        foreach ($this->scriptBuilderScripts as $path => $public) {
            if (File::exists(public_path() . '/' . $public)) {
                $event->manager->addScript(mix($path, $public));
            }
        }
    }

    /**
     * Load upgrade migrations from a provided directory/ies
     *
     * @param $paths
     *
     * @return void
     */
    public function loadUpgradeMigrationsFrom($paths)
    {
        $this->callAfterResolving('upgrade', function ($migrator) use ($paths) {
            foreach ((array) $paths as $path) {
                $migrator->path($path);
            }
        });
    }

    /**
     * Register a custom javascript for the script builder
     *
     * @param string $path
     * @param string $public
     */
    protected function registerJsToScriptBuilder($path, $public)
    {
        $this->scriptBuilderScripts[$path] = $public;
    }

    /**
     * Update config so l5-swagger knows where to look for @OA annotations
     *
     * @param array $paths
     *
     * @return void
     */
    public function registerOpenApiAnnotationPaths(array $paths)
    {
        if (!app()->runningInConsole()) {
            return;
        }

        $configString = 'l5-swagger.documentations.default.paths.annotations';

        config([
            $configString => array_merge(
                config($configString),
                $paths
            ),
        ]);
    }
}
