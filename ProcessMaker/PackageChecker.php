<?php
namespace ProcessMaker;

class PackageChecker {

    private $name;

    private $errors = [];

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function check($fn)
    {
        $message = $fn();
        if ($message) {
            $this->errors[] = $message;
        }
    }

    public function getErrors() {
        return $this->errors;
    }

    public function hasEnvVars($vars)
    {
        foreach($vars as $var) {
            if (getenv($var) === false) {
                $this->errors[] = "Environment variable missing $var";
            }
        }
    }

    public function hasDatabaseEntry($class, $name, $column = 'key')
    {
        if (!$class::where($column, $name)->exists()) {
            $model = new $class;
            $this->errors[] = "{$model->getTable()} table does not have $name in the $column column";
        }
    }
    
    public function checkPublishedAssets()
    {
        foreach ($this->publishedFolders() as $local => $remote) {
            if ($this->hashFolder($local) !== $this->hashFolder($remote)) {
                $this->errors[] = "Package assets not published";
            }
        }
    }

    public function checkRequiredPackages()
    {
        $installedCorePackages = $this->composerPackages();
        foreach ($this->requiredPackages() as $package => $version) {
            if (!isset($installedCorePackages[$package])) {
                $this->errors[] = "Package missing: $package";
            } elseif ($installedCorePackages[$package] !== '') {
                // TODO: Check semver satisfies. See Composer\Semver package
            }
        }
    }

    public function artisanOutput($artisan)
    {
        if (count($this->errors) > 0) {
            foreach ($this->errors as $error) {
                $artisan->error($error);
            }
        } else {
            $artisan->info("Package installed and configured correctly");
        }
    }
    
    private function requiredPackages()
    {
        return (array) $this->packageComposer()->require;
    }

    private function composerPackages()
    {
        exec('composer show | awk \'{ print $1 "|" $2 }\'', $out);
        $lines = collect($out);
        return $lines->mapWithKeys(function($line) {
            $parts = explode("|", $line);
            return [$parts[0] => $parts[1]];
        });
    }

    private function publishedFolders()
    {
        $folders = [];
        foreach ($this->packageProviders() as $provider) {
            $folders = array_merge($folders, $provider::$publishes[$provider]);
        }
        return $folders;
    }

    public function packageProviders()
    {
        return $this->packageComposer()->extra->laravel->providers;
    }

    private function packageComposer()
    {
        $packageComposerPath = base_path("vendor/processmaker/{$this->name}/composer.json");
        return json_decode(file_get_contents($packageComposerPath));
    }

    private function hashFolder($folder)
    {
        $cmd = "ls -alR --ignore='.*' $folder | awk '{ print $5 $9 }' | sha1sum";
        exec($cmd, $out, $ret);
        if ($ret !== 0) {
            throw new \Exception(implode("\n", $out));
        }
        return $out[0];
    }
}