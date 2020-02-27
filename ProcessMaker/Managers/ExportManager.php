<?php

namespace ProcessMaker\Managers;

use DOMXPath;
use ProcessMaker\Models\Process;
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
    public function getDependenciesOfType($type, $target)
    {
        $references = [];
        foreach ($this->dependencies as $dependencie) {
            if (is_a($target, $dependencie['owner']) && $dependencie['type'] === $type) {
                $references = call_user_func($dependencie['callback'], $references, $target);
            }
        }
        return array_unique($references);
    }

    /**
     * Get nodes from BPMN definitions
     */
    public function screensInBpmn(array $screens, Process $process)
    {
        $xpath = new DOMXPath($process->getDefinitions());
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);
        $nodes = $xpath->query("//*[@pm:screenRef!='']");
        foreach ($nodes as $node) {
            $screens[] = $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef');
        }
        return $screens;
    }
}
