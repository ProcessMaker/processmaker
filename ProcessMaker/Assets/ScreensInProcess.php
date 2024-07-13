<?php

namespace ProcessMaker\Assets;

use DOMXPath;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Providers\WorkflowServiceProvider;

class ScreensInProcess
{
    public $type = Screen::class;

    public $owner = Process::class;

    /**
     * Get screens references used in a process
     *
     * @param Process $process
     * @param array $screens
     *
     * @return array
     */
    public function referencesToExport(Process $process, array $references = [])
    {
        // Screens used in BPMN
        $xpath = new DOMXPath($process->getDefinitions());
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);
        $xpath->registerNamespace('bpmn', 'http://www.omg.org/spec/BPMN/20100524/MODEL');
        // Used in screenRef
        $nodes = $xpath->query("//*[@pm:screenRef!='']");
        foreach ($nodes as $node) {
            $ref = $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef');
            $references[] = [Screen::class, $ref];
        }
        // Used in interstitialScreenRef
        $nodes = $xpath->query("//*[@pm:interstitialScreenRef!='']");
        foreach ($nodes as $node) {
            $ref = $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'interstitialScreenRef');
            $references[] = [Screen::class, $ref];
        }
        // Used abe email screen
        $nodes = $xpath->query("//*[@pm:screenEmailRef!='']");
        foreach ($nodes as $node) {
            $ref = $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenEmailRef');
            $references[] = [Screen::class, $ref];
        }
        // Used abe email screen for completed
        $nodes = $xpath->query("//*[@pm:screenCompleteRef!='']");
        foreach ($nodes as $node) {
            $ref = $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenCompleteRef');
            $references[] = [Screen::class, $ref];
        }
        // Add cancel screen
        if ($process->cancel_screen_id) {
            $references[] = [Screen::class, $process->cancel_screen_id];
        }
        // Add request detail screen
        if ($process->request_detail_screen_id) {
            $references[] = [Screen::class, $process->request_detail_screen_id];
        }

        return $references;
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
        $xpath->registerNamespace('bpmn', 'http://www.omg.org/spec/BPMN/20100524/MODEL');

        // Used in screenRef
        $nodes = $xpath->query("//*[@pm:screenRef!='']");
        foreach ($nodes as $node) {
            $oldRef = $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef');
            if (array_key_exists($oldRef, $references[Screen::class])) {
                $newRef = $references[Screen::class][$oldRef]->getKey();
                $node->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef', $newRef);
            } else {
                $node->removeAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef');
            }
        }
        // interstitialScreenRef
        $nodes = $xpath->query("//*[@pm:interstitialScreenRef!='']");
        foreach ($nodes as $node) {
            $oldRef = $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'interstitialScreenRef');
            if (!\is_numeric($oldRef)) {
                // Skip screens referenced by package key
                continue;
            }
            $newRef = $references[Screen::class][$oldRef]->getKey();
            $node->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'interstitialScreenRef', $newRef);
        }
        // cancel
        if ($process->cancel_screen_id) {
            $oldRef = $process->cancel_screen_id;
            $process->cancel_screen_id = $references[Screen::class][$oldRef]->getKey();
        }
        // Add request detail screen
        if ($process->request_detail_screen_id) {
            $oldRef = $process->request_detail_screen_id;
            $process->request_detail_screen_id = $references[Screen::class][$oldRef]->getKey();
        }
        $process->bpmn = $definitions->saveXML();
        $process->save();
    }
}
