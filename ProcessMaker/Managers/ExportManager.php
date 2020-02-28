<?php

namespace ProcessMaker\Managers;

use DOMXPath;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Providers\WorkflowServiceProvider;
use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Models\Script;

class ExportManager
{
    private $dependencies = [];

    /**
     * Get the value of dependencies
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Set the value of dependencies
     *
     * @return  self
     */
    public function setDependencies($dependencies)
    {
        $this->dependencies = $dependencies;

        return $this;
    }

    public function addDependencie(array $dependencie)
    {
        $this->dependencies[] = $dependencie;
    }

    /**
     * Get dependencies of a $modelClass type
     *
     * @param string $modelClass
     * @param Model $owner
     * @param array $references
     *
     * @return array
     */
    public function getDependenciesOfType($modelClass, $owner, array $references = [])
    {
        $references = $this->reviewDependenciesOf($owner, $references);
        $ids = [];
        foreach ($references as $ref) {
            list($class, $id) = explode(':', $ref);
            $class === $modelClass ? $ids[] = $id : null;
        }
        return $ids;
    }

    /**
     * Review all the dependencies of the $owner
     *
     * @param Model $owner
     * @param array $references
     * @param array $reviewed
     *
     * @return array
     */
    private function reviewDependenciesOf(Model $owner, array $references = [], array $reviewed = [])
    {
        $key = get_class($owner) . ':' . $owner->getKey();
        if (in_array($key, $reviewed)) {
            return $references;
        }
        $reviewed[] = $key;
        $newReferences = [];
        foreach ($this->dependencies as $dependencie) {
            if (is_a($owner, $dependencie['owner'])) {
                $newReferences = call_user_func($dependencie['callback'], $owner, $newReferences);
            }
        }
        $newReferences = array_unique(array_diff($newReferences, $references));
        $references = array_merge($references, $newReferences);
        // Find recurcively dependencies
        foreach ($newReferences as $ref) {
            list($class, $id) = explode(':', $ref);
            $nextOwner = $class::find($id);
            if ($nextOwner) {
                $references = $this->reviewDependenciesOf($nextOwner, $references, $reviewed);
            }
        }
        return $references;
    }

    /**
     * Get screens used in a process
     *
     * @param Process $process
     * @param array $screens
     *
     * @return array
     */
    public function screensUsedInProcess(Process $process, array $screens = [])
    {
        // Screens used in BPMN
        $xpath = new DOMXPath($process->getDefinitions());
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);
        // Used in screenRef
        $nodes = $xpath->query("//*[@pm:screenRef!='']");
        foreach ($nodes as $node) {
            $screens[] = Screen::class . ':' . $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef');
        }
        // Used in interstitialScreenRef
        $nodes = $xpath->query("//*[@pm:interstitialScreenRef!='']");
        foreach ($nodes as $node) {
            $screens[] = Screen::class . ':' . $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'interstitialScreenRef');
        }
        // Add cancel screen
        if ($process->cancel_screen_id) {
            $screens[] = Screen::class . ':' . $process->cancel_screen_id;
        }
        // Add request detail screen
        if ($process->request_detail_screen_id) {
            $screens[] = Screen::class . ':' . $process->request_detail_screen_id;
        }
        return $screens;
    }

    /**
     * Get the screens (nested) used in a screen
     *
     * @param Screen $screen
     * @param array $screens
     *
     * @return array
     */
    public function screensUsedInScreen(Screen $screen, array $screens = [])
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
     * Get scripts used in a process
     *
     * @param Process $process
     * @param array $scripts
     *
     * @return array
     */
    public function scriptsUsedInProcess(Process $process, array $scripts = [])
    {
        // Scripts used in BPMN
        $xpath = new DOMXPath($process->getDefinitions());
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);
        // Used in scriptRef
        $nodes = $xpath->query("//*[@pm:scriptRef!='']");
        foreach ($nodes as $node) {
            $scripts[] = Script::class . ':' . $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRef');
        }
        return $scripts;
    }

    /**
     * Get the scripts (ex. watchers) used in a screen
     *
     * @param Screen $screen
     * @param array $scripts
     *
     * @return array
     */
    public function scriptsUsedInScreen(Screen $screen, array $scripts = [])
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
     * Find recursively in an array
     *
     * @param array $array
     * @param callable $callback
     *
     * @return void
     */
    private function findInArray(array $array, callable $callback)
    {
        //array_walk_recursive();
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
