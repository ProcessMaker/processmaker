<?php

namespace ProcessMaker\Console\Commands;

use File;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Storage;
use Tests\Performance\RequestListingPerformanceData;

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
    protected $description = 'Sync translations when exists folder lang.orig';

    private $files = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $pathBackup = app()->langPath() . '.orig';

        // validate backup exists
        if (!$this->fileExists($pathBackup)) {
            $this->line('File Backup not exists');

            return;
        }
        //Search files
        $this->listFiles($pathBackup);

        foreach ($this->files as $pathFile) {
            $this->syncFile(str_replace('/lang.orig/', '/lang/', $pathFile), $pathFile);
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
            switch ($pathInfo['extension']) {
                case 'json':
                    $lines = json_decode(file_get_contents($path));
                    break;
                case 'php':
                    $lines = include $path;
                    break;
            }
        } catch (Exception $e) {
            $this->line($path . '. Not found.');
        }

        $lines = Arr::dot($lines);

        return collect($lines);
    }

    private function syncFile($custom, $backup)
    {
        $pathInfo = pathinfo($custom);
        $customTranslations = $this->parseFile($custom);
        $origin = $this->parseFile($backup);

        $merged = $origin->merge($customTranslations);

        $contents = $this->generateFile($merged, $pathInfo['extension']);

        $this->line($custom . ' Updated.');
        file_put_contents($custom, $contents);

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

        switch ($type) {
            case 'json':
                return json_encode($array);
                break;
            case 'php':
                $contents = "<?php\n\nreturn [\n\n";
                foreach ($array as $key => $value) {
                    $key = addslashes($key);
                    $value = addslashes($value);
                    $contents .= "\t'$key' => '$value',\n";
                }
                $contents .= '];';

                return $contents;
                break;
        }
    }

    private function clearCache()
    {
        if (function_exists('opcache_reset')) {
            return opcache_reset();
        }
    }
}
