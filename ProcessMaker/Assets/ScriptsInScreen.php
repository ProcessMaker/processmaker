<?php

namespace ProcessMaker\Assets;

use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;

class ScriptsInScreen
{
    public $type = Script::class;
    public $owner = Screen::class;

    /**
     * Get the scripts (ex. watchers) used in a screen
     *
     * @param Screen $screen
     * @param array $scripts
     *
     * @return array
     */
    public function referencesToExport(Screen $screen, array $scripts = [])
    {
        $config = $screen->watchers;
        if (is_array($config)) {
            $this->findInArray($config, function ($item) use (&$scripts) {
                if (is_array($item) && !empty($item['script_id'])) {
                    $scripts[] = Script::class . ':' . $item['script_id'];
                }
            });
        }
        return $scripts;
    }

    /**
     * Update references used in an imported screen
     *
     * @param Screen $process
     * @param array $references
     *
     * @return void
     */
    public function updateReferences(Screen $screen, array $references = [])
    {
        $watches = $screen->watchers;
        if (is_array($watches)) {
            foreach($watches as &$watcher) {
                $oldRef = explode('-', $watcher['script_id'])[1];
                $newRef = $references[Script::class][$oldRef]->getKey();
                $watcher['script_id'] = $newRef;
                $watcher['script']['id'] = "script-$newRef";
            }
        }
        $screen->save();
    }
}
