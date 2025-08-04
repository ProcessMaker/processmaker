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

    public function load($locale, $group, $namespace = null)
    {
        \Log::info('TenantAwareTranslationLoader::load', ['locale' => $locale, 'group' => $group, 'namespace' => $namespace]);

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

    public function __call($method, $arguments)
    {
        return $this->loader->$method(...$arguments);
    }
}
