<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Translation\FileLoader;

class TenantAwareTranslationLoader implements Loader
{
    protected $loader;

    protected $tenantId;

    public function __construct(FileLoader $loader, $tenantId)
    {
        $this->loader = $loader;
        $this->tenantId = $tenantId;
    }

    public function jsonPaths()
    {
        $paths = $this->loader->jsonPaths();
        $paths = array_map(function ($path) {
            return $path . '/tenant_' . $this->tenantId;
        }, $paths);

        return $paths;
    }

    /**
     * Methods below are to satisfy the Loader interface.
     */
    public function load($locale, $group, $namespace = null)
    {
        return $this->loader->load($locale, $group, $namespace);
    }

    public function addNamespace($namespace, $hint)
    {
        $this->loader->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path)
    {
        $this->loader->addJsonPath($path);
    }

    public function namespaces()
    {
        return $this->loader->namespaces();
    }

    /**
     * Proxy all other methods to the original loader.
     */
    public function __call($method, $arguments)
    {
        return $this->loader->$method(...$arguments);
    }
}
