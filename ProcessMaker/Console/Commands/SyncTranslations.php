<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

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
        $translationsCore = base_path() . '/resources-core';
        $existsLangOrig = $this->fileExists(lang_path() . '.orig');

        if (!$this->fileExists($translationsCore)) {
            $this->error('The folder resources-core not exists.');

            return;
        }
        //Search files
        $this->listFiles($translationsCore . '/lang');

        // updating languages by default
        foreach ($this->files as $pathFile) {
            if (!(str_contains($pathFile, '.json') || str_contains($pathFile, '.php')) || str_contains($pathFile, '.bak.')) {
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
        $translationsCore = lang_path();
        $this->listFiles($translationsCore);
        foreach ($this->files as $pathFile) {
            if (!(str_contains($pathFile, '.json') || str_contains($pathFile, '.php')) || str_contains($pathFile, '.bak.')) {
                continue;
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
                $lines = json_decode(file_get_contents($path), true);
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

    /**
     * Synchronize translations between target and backup files
     *
     * @param string $target Path to target file
     * @param string $backup Path to backup file
     * @return bool
     * @throws \Exception
     */
    private function syncFile($target, $backup)
    {
        if (str_contains($target, '.bak.')) {
            // Clean up old backup if everything succeeded
            if (file_exists($target)) {
                unlink($target);
                $this->info('Removed bak: ' . $target);
            }
            $this->info("Skipping backup file: {$target}");

            return true;
        }
        // Create backup before modifications
        $backupPath = $target . '.bak.' . date('Y-m-d-His');
        try {
            if (!copy($target, $backupPath)) {
                $this->error("Failed to create backup file: {$backupPath}");

                return false;
            }
            $this->info("Backup created: {$backupPath}");
        } catch (\Exception $e) {
            $this->error('Error creating backup: ' . $e->getMessage());

            return false;
        }

        $pathInfo = pathinfo($target);

        try {
            $targetTranslations = $this->parseFile($target);
            $origin = $this->parseFile($backup);
        } catch (\Exception $e) {
            $this->error('Error parsing files: ' . $e->getMessage());

            return false;
        }

        // Get keys that are in origin but not in target
        $diff = $origin->diffKeys($targetTranslations);

        if ($diff->isNotEmpty()) {
            $this->info('Found ' . $diff->count() . " new translations to add in {$target}");

            // only files en.json to en.json have translations others are empty
            $clear = true;
            if (str_contains($target, 'en.json') && str_contains($backup, 'en.json')) {
                $clear = false;
            }

            // Add new keys to targetTranslations
            foreach ($diff as $key => $value) {
                $targetTranslations[$key] = $clear ? '' : $value;
            }
        }

        try {
            $contents = $this->generateFile($targetTranslations, $pathInfo['extension']);

            // Validate content before saving
            if (empty($contents)) {
                throw new \Exception('Generated content is empty');
            }

            // Use atomic file writing
            $tempFile = $target . '.tmp';
            if (file_put_contents($tempFile, $contents) === false) {
                throw new \Exception('Failed to write temporary file');
            }

            if (!rename($tempFile, $target)) {
                unlink($tempFile);
                throw new \Exception('Failed to move temporary file');
            }

            $this->info("Successfully updated: {$target}");

            // Clean up old backup if everything succeeded
            if (file_exists($backupPath)) {
                unlink($backupPath);
                $this->info('Removed backup file after successful update');
            }

            if ($pathInfo['extension'] == 'php') {
                $this->clearCache();
            }

            return true;
        } catch (\Exception $e) {
            // Restore from backup if something went wrong
            if (file_exists($backupPath)) {
                copy($backupPath, $target);
                $this->info('Restored from backup due to error');
            }
            $this->error('Error saving file: ' . $e->getMessage());

            return false;
        }
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
