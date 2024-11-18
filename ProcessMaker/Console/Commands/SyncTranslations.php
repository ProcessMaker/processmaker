<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use ProcessMaker\Managers\PackageManager;

class SyncTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:sync-translations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize translations when processmaker is updated.';

    private $files = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Check if exists resources core
        $translationsCore = app()->basePath() . '/resources-core';
        $existsLangOrig = $this->fileExists(app()->langPath() . '.orig');
        if ($this->fileExists($translationsCore)) {
            //Search files
            $this->listFiles($translationsCore . '/lang');

            //updating languages by default
            foreach ($this->files as $pathFile) {
                if (!(str_contains($pathFile, '.json') || str_contains($pathFile, '.php'))) {
                    continue;
                }
                // updating resources/lang
                $this->syncFile(str_replace('/resources-core/', '/resources/', $pathFile), $pathFile);
                if ($existsLangOrig) {
                    // updating resources/lang.orig
                    $this->syncFile(str_replace(['/resources-core/', '/lang/'], ['/resources/', '/lang.orig/'], $pathFile), $pathFile);
                }
            }

            // updating all languages with new labels
            $this->files = [];
            $translationsCore = app()->basePath() . '/resources/lang';
            $this->listFiles($translationsCore);
            $filesIgnore = ['/fr/', '/de/', '/en/', '/es/', '.gitignore', '/en.json', '/es.json', '/de.json', '/fr.json'];
            foreach ($this->files as $pathFile) {
                // ignore languages by default
                foreach ($filesIgnore as $value) {
                    if (str_contains($pathFile, $value)) {
                        continue 2;
                    }
                }
                // updating resources/lang
                $backup = str_replace('/resources/', '/resources-core/', preg_replace('/(?<=lang).+?(?=json)/', '/en.', $pathFile));
                $path1 = explode('/lang/', $backup);
                $path2 = explode('/', $path1[1]);
                if (is_array($path2)) {
                    $backup = str_replace('/' . $path2[0] . '/', '/en/', $backup);
                }

                $this->syncFile($pathFile, $backup);
                if ($existsLangOrig) {
                    // updating resources/lang.orig
                    $this->syncFile(str_replace('/lang/', '/lang.orig/', $pathFile), $backup);
                }
            }
        } else {
            $this->error('The folder resources-core not exists.');
        }

        // check if exists backups of translations package
        $translationsPackage = app()->basePath() . '/resources-package';
        if ($this->fileExists($translationsPackage)) {
            // get All packages installed
            $packages = App::make(PackageManager::class)->getJsonTranslationsRegistered();
            // review all packages by translations
            foreach ($packages as $pathPackage) {
                $package = explode('/src/', $pathPackage);
                $package = explode('/', $package[0]);

                if ($this->fileExists($translationsPackage . '/lang-' . last($package))) {
                    $this->files = [];
                    $this->listFiles($translationsPackage . '/lang-' . last($package));
                    $existsLangOrig = $this->fileExists($translationsPackage . '/lang.orig-' . last($package));
                    foreach ($this->files as $pathFile) {
                        if (!str_contains($pathFile, '.json')) {
                            continue;
                        }
                        // updating resources/lang
                        $this->syncFile($pathFile, $pathPackage . '/en.json', true);
                        if ($existsLangOrig) {
                            // updating resources/lang.orig
                            $this->syncFile(str_replace('/lang-', '/lang.orig-', $pathFile), $pathPackage . '/en.json', true);
                        }
                    }

                    File::copyDirectory($translationsPackage . '/lang-' . last($package), $pathPackage);
                    if ($existsLangOrig) {
                        File::copyDirectory($translationsPackage . '/lang.orig-' . last($package), $pathPackage . '.orig');
                    }
                } else {
                    $this->error('Backup no exists: ' . last($package));
                }
            }
        } else {
            $this->error('The folder resources-package not exists.');
        }
    }

    private function listFiles($dir)
    {
        $files = scandir($dir);

        foreach ($files as $value) {
            $path = $dir . '/' . $value;
            if (!is_dir($path)) {
                $this->files[] = $path;
            } elseif ($value != '.' && $value != '..') {
                $this->listFiles($path);
            }
        }
    }

    private function fileExists($path)
    {
        return File::exists($path);
    }

    private function parseFile($path)
    {
        $pathInfo = pathinfo($path);

        $lines = [];

        try {
            if ($pathInfo['extension'] === 'json') {
                $lines = json_decode(file_get_contents($path));
            } elseif ($pathInfo['extension'] === 'php') {
                $lines = include $path;
            }
        } catch (\Exception $e) {
            $lines = [];
            $this->error($path . '. Not found.');
        }

        $lines = Arr::dot($lines);

        return collect($lines);
    }

    private function syncFile($target, $backup, $package = false)
    {
        $pathInfo = pathinfo($target);
        $targetTranslations = $this->parseFile($target);
        $origin = $this->parseFile($backup);

        if ($package) {
            $filesIgnore = ['/en.json'];
        } else {
            $filesIgnore = ['/fr/', '/de/', '/en/', '/es/', '/en.json', '/es.json', '/de.json', '/fr.json'];
        }
        $clear = true;
        foreach ($filesIgnore as $value) {
            if (str_contains($target, $value)) {
                $clear = false;
                continue;
            }
        }

        if ($clear) {
            $diff = $origin->diffKeys($targetTranslations);

            $targetTranslations = $diff->map(function () {
                return '';
            });
        }

        $merged = $origin->merge($targetTranslations);
        // search empty values
        // send values to openAI.
        $contents = $this->generateFile($merged, $pathInfo['extension']);

        file_put_contents($target, $contents);
        $this->info($target . '   Saved');

        if ($pathInfo['extension'] == 'php') {
            $this->clearCache();
        }

        return true;
    }

    private function generateFile($lines, $type)
    {
        $array = [];

        foreach ($lines as $key => $line) {
            $array[$key] = $line;
        }

        if ($type === 'json') {
            return json_encode($array, JSON_PRETTY_PRINT);
        } elseif ($type === 'php') {
            $contents = "<?php\n\nreturn [\n\n";
            foreach ($array as $key => $value) {
                $key = addslashes($key);
                $value = addslashes($value);
                $contents .= "\t'$key' => '$value',\n";
            }
            $contents .= '];';

            return $contents;
        }
    }

    private function clearCache()
    {
        if (function_exists('opcache_reset')) {
            return opcache_reset();
        }
    }
}
