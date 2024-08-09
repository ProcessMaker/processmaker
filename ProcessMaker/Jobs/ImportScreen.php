<?php

namespace ProcessMaker\Jobs;

use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;

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
            $newScreen = $this->saveScreen($screen);

            $this->status['screens']['id'] = $newScreen->id;

            $new[Screen::class][$screen->id] = $newScreen;
            //determine if the screen has watchers
            if (property_exists($screen, 'watchers')) {
                $names = [];
                if ($screen->watchers) {
                    foreach ($screen->watchers as $watcher) {
                        $names[] = $watcher->name;
                    }
                    $this->status['screens']['info'] = __('Please assign a run script user to: ') . implode(', ', $names);
                }
            }
        }
        $this->finishStatus('screens');

        if (!isset($this->file->scripts)) {
            $this->findWatcherScripts($new[Screen::class]);
        }

        if (isset($this->file->scripts)) {
            $this->prepareStatus('scripts', count($this->file->screens));
            foreach ($this->file->scripts as $script) {
                $newScript = $this->saveScript($script);
                $this->status['scripts']['id'] = $newScript->id;
                $new[Script::class][$script->id] = $newScript;
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
     * @return bool
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
                    'message' => __('Starting'), ];

                return $this->{$method}();
            }
        }

        //Return false by default
        return false;
    }
}
