<?php

namespace ProcessMaker\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

abstract class SyncTranslationsBase
{
    /**
     * The storage disk for language files
     */
    protected $langDisk;

    /**
     * The path to resources-core language files
     */
    protected $resourcesCorePath;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->langDisk = Storage::disk('lang');
        $this->resourcesCorePath = Config::get('app.resources_core_path') . '/lang';
    }

    /**
     * Get all language codes from resources-core
     *
     * @return array
     */
    protected function getLanguageCodes(): array
    {
        $languages = [];

        // Get JSON files
        $jsonFiles = glob($this->resourcesCorePath . '/*.json');
        foreach ($jsonFiles as $file) {
            $filename = basename($file);
            if (preg_match('/^([a-z]{2})\.json$/', $filename, $matches)) {
                $languages[] = $matches[1];
            }
        }

        // Get PHP directories
        $directories = glob($this->resourcesCorePath . '/*', GLOB_ONLYDIR);
        foreach ($directories as $dir) {
            $dirname = basename($dir);
            if (preg_match('/^([a-z]{2})$/', $dirname, $matches)) {
                if (!in_array($matches[1], $languages)) {
                    $languages[] = $matches[1];
                }
            }
        }

        return array_unique($languages);
    }

    /**
     * Check if a file exists in the destination
     *
     * @param string $filename
     * @return bool
     */
    protected function destinationFileExists(string $filename): bool
    {
        return $this->langDisk->exists($filename);
    }

    /**
     * Copy a file from resources-core to destination
     *
     * @param string $filename
     * @return bool
     */
    protected function copyFileFromResourcesCore(string $filename): bool
    {
        $sourcePath = $this->resourcesCorePath . '/' . $filename;

        if (!file_exists($sourcePath)) {
            return false;
        }

        $content = file_get_contents($sourcePath);

        return $this->langDisk->put($filename, $content);
    }

    /**
     * Get file content from destination
     *
     * @param string $filename
     * @return string|null
     */
    protected function getDestinationContent(string $filename): ?string
    {
        if (!$this->langDisk->exists($filename)) {
            return null;
        }

        return $this->langDisk->get($filename);
    }

    /**
     * Get file content from resources-core
     *
     * @param string $filename
     * @return string|null
     */
    protected function getResourcesCoreContent(string $filename): ?string
    {
        $sourcePath = $this->resourcesCorePath . '/' . $filename;

        if (!file_exists($sourcePath)) {
            return null;
        }

        return file_get_contents($sourcePath);
    }

    /**
     * Save content to destination
     *
     * @param string $filename
     * @param string $content
     * @return bool
     */
    protected function saveToDestination(string $filename, string $content): bool
    {
        return $this->langDisk->put($filename, $content);
    }

    /**
     * Process all translation files
     *
     * @return array
     */
    abstract public function sync(): array;

    /**
     * Process a single translation file
     *
     * @param string $languageCode
     * @return array
     */
    abstract protected function processLanguageFile(string $languageCode): array;
}
