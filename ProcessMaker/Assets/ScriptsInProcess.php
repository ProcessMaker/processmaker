<?php

namespace ProcessMaker\Assets;

use DOMXPath;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Script;
use ProcessMaker\Providers\WorkflowServiceProvider;

class ScriptsInProcess
{
    public $type = Script::class;
    public $owner = Process::class;

    /**
     * Get scripts used in a process
     *
     * @param Process $process
     * @param array $scripts
     *
     * @return array
     */
    public function referencesToExport(Process $process, array $scripts = [])
    {
        // Scripts used in BPMN
        $xpath = new DOMXPath($process->getDefinitions());
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);
        // Used in scriptRef
        $nodes = $xpath->query("//*[@pm:scriptRef!='']");
        foreach ($nodes as $node) {
            $scripts[] = [Script::class, $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRef')];
        }
        return $scripts;
    }

    /**
     * Update references used in an imported process
     *
     * @param Process $process
     * @param array $references
     *
     * @return void
     */
    public function updateReferences(Process $process, array $references = [])
    {
        $definitions = $process->getDefinitions();
        $xpath = new DOMXPath($definitions);
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);

        // Used in scriptRef
        $nodes = $xpath->query("//*[@pm:scriptRef!='']");
        foreach ($nodes as $node) {
            $oldRef = $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRef');
            $newRef = $references[Script::class][$oldRef]->getKey();
            $node->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRef', $newRef);
        }
        $process->bpmn = $definitions->saveXML();
        $process->save();
    }
}
