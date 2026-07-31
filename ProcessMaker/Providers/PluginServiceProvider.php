<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use ProcessMaker\Events\TenantResolved;
use RuntimeException;

/**
 * Service provider for loading and registering plugins.
 */
class PluginServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Load plugins when the tenant is resolved
        Event::listen(TenantResolved::class, function ($event) {
            $this->loadPlugins();
        });
    }

    /**
     * Load all plugins from the storage/plugins directory.
     */
    protected function loadPlugins(): void
    {
        $pluginsFolder = storage_path('plugins');
        $pluginPaths = glob($pluginsFolder . '/*', GLOB_ONLYDIR);

        foreach ($pluginPaths as $pluginPath) {
            // Ignore plugins that start with _
            if (str_starts_with(basename($pluginPath), '_')) {
                continue;
            }
            $this->loadPlugin($pluginPath);
        }
    }

    /**
     * Load a single plugin from the given path.
     *
     * @param string $pluginPath
     */
    protected function loadPlugin(string $pluginPath): void
    {
        $composerJsonPath = $pluginPath . '/composer.json';

        if (!file_exists($composerJsonPath)) {
            return;
        }

        $composerJson = $this->loadComposerJson($composerJsonPath);
        $this->registerAutoloader($pluginPath, $composerJson);
        $this->registerServiceProviders($composerJson);
    }

    /**
     * Load and decode composer.json file.
     *
     * @param string $composerJsonPath
     * @return array
     */
    protected function loadComposerJson(string $composerJsonPath): array
    {
        $content = file_get_contents($composerJsonPath);
        $composerJson = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                "Invalid JSON in composer.json at {$composerJsonPath}: " . json_last_error_msg()
            );
        }

        return $composerJson;
    }

    /**
     * Register PSR-4 autoloader for the plugin.
     *
     * @param string $pluginPath
     * @param array $composerJson
     */
    protected function registerAutoloader(string $pluginPath, array $composerJson): void
    {
        $autoloadPath = $pluginPath . '/vendor/autoload.php';

        if (!file_exists($autoloadPath)) {
            return;
        }

        $loader = require_once $autoloadPath;
        if ($loader === true) {
            // Already loaded and presumably registered
            return;
        }

        $psr4Namespaces = Arr::get($composerJson, 'autoload.psr-4', []);

        foreach ($psr4Namespaces as $namespace => $path) {
            $fullPath = $pluginPath . '/' . $path;

            if (!is_dir($fullPath)) {
                throw new RuntimeException(
                    "Plugin {$pluginPath} has a PSR-4 path that does not exist: {$path}"
                );
            }

            $loader->addPsr4($namespace, $fullPath);
        }
    }

    /**
     * Register Laravel service providers defined in the plugin's composer.json.
     *
     * @param array $composerJson
     */
    protected function registerServiceProviders(array $composerJson): void
    {
        $providers = Arr::get($composerJson, 'extra.laravel.providers', []);

        foreach ($providers as $provider) {
            app()->register($provider);
        }
    }
}
