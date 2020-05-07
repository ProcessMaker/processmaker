<?php

namespace ProcessMaker\Jobs;

use ProcessMaker\Models\Screen;
use ProcessMaker\Managers\ExportManager;

class ExportScreen extends ExportProcess
{

    /**
     * The screen we will export.
     *
     * @var object
     */
    public $screen;

    /**
     * Create a new job instance, set the screen, and
     * set the file path.
     *
     * @return void
     */
    public function __construct(Screen $screen, $filePath = null)
    {
        $this->screen = $screen;
        $this->filePath = $filePath;
    }

    /**
     * Run through each step of the packaging process. We specify a file type
     * and a file version in case of future changes to the file format.
     *
     * @return void
     */
    private function packageFile()
    {
        $this->package['type'] = 'screen_package';
        $this->package['version'] = '2';
        $this->packageScreens();
    }

    /**
     * Execute the job.
     *
     * @return boolean|string
     */
    public function handle()
    {
        $this->manager = app(ExportManager::class);

        // Package up our process
        $this->packageFile();

        // Encode the file
        $this->encodeFile();

        // If a specific file path is specified,
        // export to it and return true. Otherwise,
        // save to our cache and return the saved key.
        if ($this->filePath) {
            return $this->saveFile();
        } else {
            return $this->cacheFile();
        }
    }
}
