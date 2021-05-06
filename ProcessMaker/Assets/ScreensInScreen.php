<?php

namespace ProcessMaker\Assets;

use DOMXPath;
use Illuminate\Support\Arr;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Providers\WorkflowServiceProvider;
use ProcessMaker\Exception\MaximumRecursionException;
use ProcessMaker\Managers\ExportManager;

class ScreensInScreen
{
    public $type = Screen::class;
    public $owner = Screen::class;
    private $recursion = 0;

    /**
     * Get the screens (nested) used in a screen
     *
     * @param Screen $screen
     * @param array $screens
     *
     * @return array
     */
    public function referencesToExport(Screen $screen, array $screens = [], ExportManager $manager = null, bool $recursive = true)
    {
        if ($this->recursion > 10) {
            throw new MaximumRecursionException(
                "Max screen recursion depth of 10 exceeded. Is a child screen referencing its parent?"
            );
        }

        $config = $screen->config;
        if (is_array($config)) {
            $this->findInArray($config, function ($item) use (&$screens, $manager, $recursive) {
                if (is_array($item) && isset($item['component']) && $item['component'] === 'FormNestedScreen' && !empty($item['config']['screen'])) {
                    $screens[] = [Screen::class, $item['config']['screen']];
                    if ($recursive) {
                        $screen = app(Screen::class)->findOrFail($item['config']['screen']);
                        $this->recursion++;
                        $screens = $this->referencesToExport($screen, $screens, $manager, $recursive);
                        $this->recursion--;
                    }
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
            $this->findInArray($config, function ($item, $key) use ($references, &$config) {
                if (is_array($item) && isset($item['component']) && $item['component'] === 'FormNestedScreen' && !empty($item['config']['screen'])) {
                    $oldRef = $item['config']['screen'];
                    $newRef = $references[Screen::class][$oldRef]->getKey();
                    Arr::set($config, "$key.config.screen", $newRef);
                }
            });
            $screen->config = $config;
            $screen->save();
        }
    }

    /**
     * Find recursively in an array
     *
     * @param array $array
     * @param callable $callback
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
