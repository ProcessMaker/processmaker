<?php

namespace ProcessMaker\Assets;

use DOMXPath;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Providers\WorkflowServiceProvider;

class ScreensInScreen
{
    public $type = Screen::class;
    public $owner = Screen::class;

    /**
     * Get the screens (nested) used in a screen
     *
     * @param Screen $screen
     * @param array $screens
     *
     * @return array
     */
    public function referencesToExport(Screen $screen, array $screens = [])
    {
        $config = $screen->config;
        if (is_array($config)) {
            $this->findInArray($config, function ($item) use (&$screens) {
                if (is_array($item) && isset($item['component']) && $item['component'] === 'FormNestedScreen' && !empty($item['config']['screen'])) {
                    $screens[] = Screen::class . ':' . $item['config']['screen'];
                }
            });
        }
        return $screens;
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
        $config = $screen->config;
        if (is_array($config)) {
            $this->findInArray($config, function (&$item) use (&$references) {
                if (is_array($item) && isset($item['component']) && $item['component'] === 'FormNestedScreen' && !empty($item['config']['screen'])) {
                    $oldRef = Screen::class . ':' . $item['config']['screen'];
                    $newRef = $references[Screen::class][$oldRef]->getKey();
                    $item['config']['screen'] = $newRef;
                    \Log::info("update screen: $oldRef -> $newRef");
                }
            });
        }
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
    private function findInArray(array &$array, callable $callback)
    {
        call_user_func($callback, $array);
        foreach ($array as &$item) {
            if (is_array($item)) {
                $this->findInArray($item, $callback);
            } else {
                call_user_func($callback, $item);
            }
        }
    }
}
