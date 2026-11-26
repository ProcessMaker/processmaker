<?php

namespace ProcessMaker\Managers;

use Illuminate\Foundation\PackageManifest;
use ProcessMaker\Support\PluginVendorAssets;

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
            $path = $this->filesystemPathForWebScript($script);
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
            $webPath = $this->publishedPluginScriptWebPath($packageName, $extensionsFile);

            if ($webPath !== null) {
                $this->addScript($webPath);
            }
        }
    }

    /**
     * Prefer tenant-published plugin assets when available, else global public/vendor.
     */
    private function publishedPluginScriptWebPath(string $packageName, string $extensionsFile): ?string
    {
        $relativeJs = '/js/' . $extensionsFile;

        if (PluginVendorAssets::isEnabled()) {
            $tenantId = PluginVendorAssets::currentTenantId();
            $tenantFile = PluginVendorAssets::tenantPublicRoot($tenantId, $packageName) . $relativeJs;
            if (is_file($tenantFile)) {
                return PluginVendorAssets::webPath($tenantId, $packageName, $relativeJs);
            }
        }

        $globalWeb = '/vendor/processmaker/packages/' . $packageName . $relativeJs;
        if (is_file(public_path(ltrim($globalWeb, '/')))) {
            return $globalWeb;
        }

        return null;
    }

    private function filesystemPathForWebScript(string $webScript): string
    {
        $pathOnly = explode('?', $webScript, 2)[0];

        return public_path(ltrim($pathOnly, '/'));
    }

    private function screenBuilderExtensionsFile(string $type): string
    {
        return 'screen-builder-' . strtolower($type) . '-components.js';
    }

    /**
     * Prefer the current tenant's copy on its public disk, else the global public/vendor copy.
     */
    private function publishedPluginScriptWebPath(string $packageName, string $extensionsFile): ?string
    {
        $relativeJs = '/js/' . $extensionsFile;

        if (PluginVendorAssets::isEnabled()) {
            $tenantId = PluginVendorAssets::currentTenantId();
            if ($tenantId !== null && is_file(PluginVendorAssets::tenantPackageRoot($tenantId, $packageName) . $relativeJs)) {
                return PluginVendorAssets::webPath($packageName, $relativeJs);
            }
        }

        $globalWeb = '/vendor/processmaker/packages/' . $packageName . $relativeJs;

        return file_exists(public_path(ltrim($globalWeb, '/'))) ? $globalWeb : null;
    }

    /**
     * Map a script URL to its file: /storage/... lives on the tenant public disk, the rest under public/.
     */
    private function filesystemPathForWebScript(string $webScript): string
    {
        $resolved = PluginVendorAssets::filesystemPathForStorageUrl($webScript);
        if ($resolved !== null) {
            return $resolved;
        }

        return public_path(ltrim(explode('?', $webScript, 2)[0], '/'));
    }

    /**
     * Resolve the web package folder name (e.g. package-plg) from a plugin directory.
     */
    private function resolvePluginPackageName(string $pluginPath, string $folderName): string
    {
        $packageName = $folderName;

        $composerPath = $pluginPath . '/composer.json';
        if (is_file($composerPath)) {
            $data = json_decode((string) file_get_contents($composerPath), true);
            $name = is_array($data) ? ($data['name'] ?? null) : null;
            if (is_string($name) && str_contains($name, '/')) {
                $packageName = substr($name, strrpos($name, '/') + 1);
            }
        }

        return $packageName;
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

        if (!str_starts_with($path, '/')) {
            return '/' . ltrim($path, '/');
        }

        return $this->publicPathRelativeToRoot($path, public_path())
            ?? $this->publicPathRelativeToRoot($path, base_path());
    }

    private function publicPathRelativeToRoot(string $absolutePath, string $root): ?string
    {
        $root = str_replace('\\', '/', realpath($root) ?: $root);
        if (!str_starts_with($absolutePath, $root)) {
            return null;
        }

        return '/' . ltrim(substr($absolutePath, strlen($root)), '/');
    }
}
