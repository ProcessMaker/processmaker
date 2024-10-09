<?php

namespace ProcessMaker\Managers;

use File;

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

    /**
     * Look into current packages and create a language file for each one
     * 
     * @param $code The language code to create a language file of
     */
    public function createLanguageFile($code)
    {
        // Get current packages
        $packages = app()->translator->getLoader()->jsonPaths();

        foreach ($packages as $package) {
            if (!file_exists("{$package}/en.json")) {
                return ;
            } else {
                // If language does not exist, clone en.json without values
                if (!file_exists("{$package}/{$code}.json")) {
                    $enJson = file_get_contents("{$package}/en.json");
                    $data = json_decode($enJson, true);

                    // Clear values from array
                    foreach ($data as $key => &$value) {
                        $value = '';
                    }

                    // Get empty file with only keys, empty values 
                    $baseFile = json_encode($data);

                    // Create file in package
                    file_put_contents("{$package}/{$code}.json", $baseFile);
                }
            }
        }
    }

    /**
     * Delete a language file form all currently installed packages
     * 
     * @param $code The language code of which to delete the language file 
     */
    public function deleteLanguageFile($code)
    {
         // Get current packages
         $packages = app()->translator->getLoader()->jsonPaths();

         foreach ($packages as $package) {
            // Check if file exists in package
            if (File::exists("{$package}/{$code}.json")) {
                // Delete file
                File::delete("{$package}/{$code}.json");
            }
        }
    }
}