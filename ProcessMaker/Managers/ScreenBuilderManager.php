<?php

namespace ProcessMaker\Managers;

use Illuminate\Foundation\PackageManifest;

class ScreenBuilderManager
{
    private $javascriptRegistry;

    /**
     * Start our screen builder manager, creating an empty javascript registry
     *
     * @param $formType type of the form that is using the manager (DISPLAY, FORM, etc)
     */
    public function __construct()
    {
        $this->javascriptRegistry = [];
    }

    /**
     * Add a new script to the modeler load.  These scripts can then interact with the modeler
     * during it's startup lifecycle to do this such as register new node types.
     *
     * @param string $script Path to the javascript to load
     * @return void
     */
    public function addScript($script)
    {
        if (!in_array($script, $this->javascriptRegistry)) {
            $this->javascriptRegistry[] = $script;
        }
    }

    /**
     * Retrieve the list of scripts that have been added. This is used in the modeler blade
     * to execute each script in a script tag before the modeler is started.
     *
     * @return array Collection of paths to scripts to load
     */
    public function getScripts()
    {
        $scripts = [];
        foreach ($this->javascriptRegistry as $script) {
            $path = public_path($script);
            $time = file_exists($path) ? filemtime($path) : 0;
            $scripts[] = $script . ($time ? "?t=$time" : '');
        }

        return $scripts;
    }

    public function addPackageScripts($type = 'DISPLAY')
    {
        $this->addComposerPackageScripts($type);
        $this->addStoragePluginScripts($type);
    }

    /**
     * Load screen-builder extension scripts from Composer packages under vendor/processmaker/packages.
     */
    private function addComposerPackageScripts(string $type): void
    {
        $extensionsFile = $this->screenBuilderExtensionsFile($type);

        $directories = glob('vendor/processmaker/packages/*', GLOB_ONLYDIR);
        $installed = app(PackageManifest::class)->list();
        $directories = array_values(array_filter($directories, function ($directory) use ($installed) {
            $package = 'processmaker/' . basename($directory);

            return in_array($package, $installed);
        }));
        foreach ($directories as $directory) {
            $extensionsFullName = $directory . '/js/' . $extensionsFile;
            foreach (glob($extensionsFullName) ?: [] as $file) {
                $publicPath = $this->normalizePublicScriptPath($file);
                if ($publicPath !== null) {
                    $this->addScript($publicPath);
                }
            }
        }
    }

    /**
     * Load screen-builder extension scripts for enabled plugins in storage/plugins when
     * assets were published to public/vendor/processmaker/packages/{name}/js/.
     */
    private function addStoragePluginScripts(string $type): void
    {
        $extensionsFile = $this->screenBuilderExtensionsFile($type);
        $pluginsFolder = storage_path('plugins');

        if (!is_dir($pluginsFolder)) {
            return;
        }

        foreach (glob($pluginsFolder . '/*', GLOB_ONLYDIR) ?: [] as $pluginPath) {
            $folderName = basename($pluginPath);
            if (str_starts_with($folderName, '_')) {
                continue;
            }

            $packageName = $this->resolvePluginPackageName($pluginPath, $folderName);
            $relativePath = '/vendor/processmaker/packages/' . $packageName . '/js/' . $extensionsFile;

            if (file_exists(public_path($relativePath))) {
                $this->addScript($relativePath);
            }
        }
    }

    private function screenBuilderExtensionsFile(string $type): string
    {
        return 'screen-builder-' . strtolower($type) . '-components.js';
    }

    /**
     * Resolve the web package folder name (e.g. package-plg) from a plugin directory.
     */
    private function resolvePluginPackageName(string $pluginPath, string $folderName): string
    {
        $composerPath = $pluginPath . '/composer.json';
        if (!is_file($composerPath)) {
            return $folderName;
        }

        $content = file_get_contents($composerPath);
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return $folderName;
        }

        $name = $data['name'] ?? null;
        if (is_string($name) && str_contains($name, '/')) {
            return substr($name, strrpos($name, '/') + 1);
        }

        return $folderName;
    }

    /**
     * Convert a filesystem path to a public-relative script path with a leading slash.
     */
    private function normalizePublicScriptPath(string $filesystemPath): ?string
    {
        $path = str_replace('\\', '/', $filesystemPath);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, '/')) {
            $publicRoot = str_replace('\\', '/', realpath(public_path()) ?: public_path());
            if (str_starts_with($path, $publicRoot)) {
                return '/' . ltrim(substr($path, strlen($publicRoot)), '/');
            }

            $baseRoot = str_replace('\\', '/', realpath(base_path()) ?: base_path());
            if (str_starts_with($path, $baseRoot)) {
                return '/' . ltrim(substr($path, strlen($baseRoot)), '/');
            }

            return null;
        }

        return '/' . ltrim($path, '/');
    }
}
