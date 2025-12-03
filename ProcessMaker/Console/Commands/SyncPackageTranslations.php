<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use ProcessMaker\Managers\PackageManager;

class SyncPackageTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:sync-package-translations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize custom translations of packages to CORE';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Synchronize translations of packages to CORE');
        // get All packages installed
        $packages = App::make(PackageManager::class)->getJsonTranslationsRegistered();
        // review all packages by translations
        foreach ($packages as $pathLangPackage) {
            $package = explode('/src/', $pathLangPackage);
            $package = explode('/', $package[0]);

            // verify if exists folder lang.orig
            $langOrig = $pathLangPackage . '/../lang.orig';
            if ($this->fileExists($langOrig)) {
                // get all files type json of langOrig
                $files = File::files($langOrig);
                foreach ($files as $file) {
                    // get name of file
                    $nameFile = basename($file);

                    if ($this->fileExists($pathLangPackage . '/' . $nameFile)) {
                        try {
                            $origTranslations = $this->parseFile($file);
                            $langTranslations = $this->parseFile($pathLangPackage . '/' . $nameFile);
                        } catch (\Exception $e) {
                            $this->error('Error parse file: ' . $e->getMessage());
                            continue;
                        }

                        $diff = $langTranslations->diff($origTranslations);

                        if ($this->fileExists(lang_path() . '/' . $nameFile) && $diff->isNotEmpty()) {
                            $targetLang = $this->parseFile(lang_path() . '/' . $nameFile);

                            // Add new keys to targetLang
                            foreach ($diff as $key => $value) {
                                $targetLang[$key] = $value;
                            }

                            try {
                                $contents = $this->generateFile($targetLang, 'json');

                                // Validate content before saving
                                if (empty($contents)) {
                                    throw new \Exception('Generated content is empty');
                                }

                                // Use atomic file writing
                                $tempFile = lang_path() . '/' . $nameFile . '.tmp';
                                if (file_put_contents($tempFile, $contents) === false) {
                                    throw new \Exception('Failed to write temporary file');
                                }

                                if (!rename($tempFile, lang_path() . '/' . $nameFile)) {
                                    unlink($tempFile);
                                    throw new \Exception('Failed to move temporary file');
                                }
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                    }
                }
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
                $lines = json_decode(file_get_contents($path), true);
            } elseif ($pathInfo['extension'] === 'php') {
                $lines = include $path;
            }
        } catch (\Exception $e) {
            $lines = [];
        }

        $lines = Arr::dot($lines);

        return collect($lines);
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
}
