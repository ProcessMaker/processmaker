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
                    $scripts[] = [Script::class, $item['script_id']];
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
        $screen->watchers = $watches;
        $screen->save();
    }

    /**
     * Find recursively in an array
     *
     * @param array $array
     * @param callable $callback
     *
     * @return void
     */
    private function findInArray(array $array, callable $callback)
    {
        call_user_func($callback, $array);
        foreach ($array as $item) {
            if (is_array($item)) {
                $this->findInArray($item, $callback);
            } else {
                call_user_func($callback, $item);
            }
        }
    }
}
