<?php

namespace ProcessMaker\Managers;

use DOMXPath;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Providers\WorkflowServiceProvider;

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
     * Get dependencies of a $type
     */
    public function getDependenciesOfType($type, $target, array $references = [])
    {
        foreach ($this->dependencies as $dependencie) {
            if (is_a($target, $dependencie['owner']) && $dependencie['type'] === $type) {
                $references = call_user_func($dependencie['callback'], $references, $target);
            }
        }
        return array_unique($references);
    }

    /**
     * Get the screens used in process
     */
    public function screensUsedInProcess(array $screens, Process $process)
    {
        // Screens used in BPMN
        $xpath = new DOMXPath($process->getDefinitions());
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);
        // Used in screenRef
        $nodes = $xpath->query("//*[@pm:screenRef!='']");
        foreach ($nodes as $node) {
            $screens[] = $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef');
        }
        // Used in interstitialScreenRef
        $nodes = $xpath->query("//*[@pm:interstitialScreenRef!='']");
        foreach ($nodes as $node) {
            $screens[] = $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'interstitialScreenRef');
        }
        // Add cancel screen
        if ($process->cancel_screen_id) {
            $screenIds[] = $this->process->cancel_screen_id;
        }
        return $screens;
    }

    public function screensUsedInScreen(array $screens, Screen $screen)
    {
        $this->findInArray($screen->config, function ($item) use (&$screens) {
            if (is_array($item) && isset($item['component']) && $item['component'] === 'FormNestedScreen' && !empty($item['config']['screen'])) {
                $screens[] = $item['config']['screen'];
            }
        });
        return $screens;
    }

    private function findInArray(array $array, $callback)
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
