<?php
namespace ProcessMaker\Jobs;

use Exception;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Managers\ExportManager;

class ImportScreen extends ImportProcess
{
    /**
     * Parse files with version 1
     *
     * @return array
     */
    private function parseFileV1()
    {
        $this->file->screens = [$this->file->screens];
        return $this->parseFileV2();
    }

    private function findWatcherScripts($screens)
    {
        foreach ($screens as $screen) {
            if (isset($screen->watchers) && is_array($screen->watchers)) {
                foreach ($screen->watchers as $watcher) {
                    if (is_array($watcher)) {
                        if (isset($watcher['script_id']) && is_array($watcher['script'])) {
                            $watcher['script']['id'] = explode('-', $watcher['script']['id'])[1];
                            $this->file->scripts[] = (object) $watcher['script'];
                        }
                    }
                }
            }
        }
    }

    /**
     * Parse files with version 2
     *
     * @return array
     */
    private function parseFileV2()
    {
        $new = [Screen::class => []];

        $this->prepareStatus('screens', count($this->file->screens));
        foreach ($this->file->screens as $screen) {
            $new[Screen::class][$screen->id] = $this->saveScreen($screen);
        }
        $this->finishStatus('screens');

        if (! isset($this->file->scripts)) {
            $this->findWatcherScripts($new[Screen::class]);
        }

        if (isset($this->file->scripts)) {
            $this->prepareStatus('scripts', count($this->file->screens));
            foreach ($this->file->scripts as $script) {
                $new[Script::class][$script->id] = $this->saveScript($script);
            }
            $this->finishStatus('scripts');
        }

        $manager = app(ExportManager::class);
        $manager->updateReferences($new);

        return $this->status;
    }

    /**
     * Execute the job.
     *
     * @return boolean
     */
    public function handle()
    {
        //First, decode the file
        $this->decodeFile();

        //Then, process it based on version number
        if ($this->file->type === 'screen_package') {
            if ($method = $this->getParser()) {
                $this->status = [];
                $this->status['screens'] = [
                    'label' => __('Screens'),
                    'success' => false,
                    'message' => __('Starting')];
                return $this->{$method}();
            }
        }

        //Return false by default
        return false;
    }
}
