<?php

namespace ProcessMaker\Assets;

use ProcessMaker\Managers\ExportManager;
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
                    $scriptId = (string) $item['script_id'];
                    $id = (string) ($item['script']['id'] ?? '');
                    if (!str_starts_with($scriptId, 'data_source-') && !str_starts_with($id, 'data_source-')) {
                        $scripts[] = [Script::class, $item['script_id']];
                    }
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
    public function updateReferences(Screen $screen, array $references, ExportManager $exportManager)
    {
        $watches = $screen->watchers;
        if (is_array($watches)) {
            foreach ($watches as &$watcher) {
                $scriptId = (string) ($watcher['script_id'] ?? '');
                $id = (string) ($watcher['script']['id'] ?? '');
                if (str_starts_with($scriptId, 'data_source-') || str_starts_with($id, 'data_source-')) {
                    continue;
                }
                $oldRef = $scriptId;
                if (isset($references[Script::class][$oldRef])) {
                    $newRef = $references[Script::class][$oldRef]->getKey();
                } else {
                    $newRef = null;
                    $exportManager->addLogMessage(
                        'ScriptsInScreen:references',
                        __(
                            'Imported file does not contain the script #:script assigned to a watcher',
                            ['script' => $oldRef]
                        ),
                        false,
                        __("Missing watcher's script")
                    );
                }
                $watcher['script_id'] = $newRef;
                $watcher['script']['id'] = "script-$newRef";
                if ($newRef) {
                    $watcher['script']['title'] = $references[Script::class][$oldRef]->title;
                }
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
     * @param array $path
     *
     * @return void
     */
    private function findInArray(array $array, callable $callback, array $path = [])
    {
        call_user_func($callback, $array, implode('.', $path));
        foreach ($array as $key => $item) {
            if (is_array($item)) {
                $this->findInArray($item, $callback, array_merge($path, [$key]));
            } else {
                call_user_func($callback, $item, implode('.', array_merge($path, [$key])));
            }
        }
    }
}
