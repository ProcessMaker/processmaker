<?php

namespace ProcessMaker\Managers;

class PackageManager
{
    private $packages;

    public function __construct()
    {
        $this->packages = [];
    }

    /**
     * Register package
     *
     * @param $name string name of package
     */
    public function addPackage($name)
    {
        $this->packages[$name] = [];
    }

    /**
     * List packages
     *
     */
    public function listPackages()
    {
        $list = array_keys($this->packages);
        sort($list);
        return $list;
    }

    /**
     * Verify if package is registered
     * @param $name
     * @return bool
     */
    public function isRegistered($name)
    {
        return isset($this->packages[$name]);
    }

    /**
     * Remove package
     *
     * @param $name
     */
    public function remove($name)
    {
        unset($this->packages[$name]);
    }

}