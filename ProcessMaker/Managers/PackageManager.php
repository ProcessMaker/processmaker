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
     * Get all json translations registered from packages
     *
     * @return array
     */
    public function getJsonTranslationsRegistered() : array
    {
        return app()->translator->getLoader()->jsonPaths();
    }

    /**
     * Look into current packages and create a language file for each one
     *
     * @param $code The language code to create a language file of
     */
    public function createLanguageFile($code)
    {
        // Get current packages
        $packages = $this->getJsonTranslationsRegistered();

        foreach ($packages as $package) {
            if (!file_exists("{$package}/en.json")) {
                return;
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
                    $baseFile = json_encode($data, JSON_PRETTY_PRINT);

                    // Create file in package
                    file_put_contents("{$package}/{$code}.json", $baseFile);

                    // save new language backup
                    if (File::exists("{$package}.orig")) {
                        file_put_contents("{$package}.orig/{$code}.json", $baseFile);
                    }

                    //Add files gitignore
                    if (File::exists(base_path('resources') . '/.gitignore')) {
                        copy(base_path('resources') . '/.gitignore', $package . '/../.gitignore');
                    }
                    if (File::exists(lang_path() . '/.gitignore')) {
                        copy(lang_path() . '/.gitignore', $package . '/.gitignore');
                    }
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
        $packages = $this->getJsonTranslationsRegistered();

        foreach ($packages as $package) {
            // Check if file exists in package
            if (File::exists("{$package}/{$code}.json")) {
                // Delete file
                File::delete("{$package}/{$code}.json");
            }
            // Check if file exists in package lang.orig
            if (File::exists(str_replace('/lang/', '/lang.orig/', "{$package}/{$code}.json"))) {
                // Delete file
                File::delete(str_replace('/lang/', '/lang.orig/', "{$package}/{$code}.json"));
            }
        }
    }
}
